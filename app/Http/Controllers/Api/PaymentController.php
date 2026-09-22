<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\KHQRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Payment::with('order')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request, KHQRService $khqrService): JsonResponse
    {
        $validated = $request->validate([
            'order_id'              => 'required|exists:orders,id',
            'method'                => 'required|in:cash,khqr', // Strictly cash and khqr
            'amount'                => 'required|numeric|min:0',
            'transaction_reference' => 'nullable|string|max:255',
            'note'                  => 'nullable|string',
        ]);

        $order = Order::with('items')->findOrFail($validated['order_id']);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order is not pending.'], 422);
        }

        if ($order->payments()->where('status', 'paid')->exists()) {
            return response()->json(['message' => 'A successful payment already exists for this order.'], 422);
        }

        if (round((float) $validated['amount'], 2) !== round((float) $order->total, 2)) {
            return response()->json(['message' => 'Payment amount must equal order total.'], 422);
        }

        $qrData = [];

        // Generate KHQR before starting the DB transaction
        if ($validated['method'] === 'khqr') {
            $currencyCode = strtoupper($order->currency ?? config('bakong.currency.default', 'USD')) === 'KHR' ? 116 : 840;

            $accountId = config('bakong.account_id');
            $name      = config('bakong.merchant_name');
            $city      = config('bakong.merchant_city');

            $khqrResponse = $khqrService->generate(
                $accountId,
                $name,
                $city,
                (float) $order->total,
                $currencyCode
            );

            $qrData = $khqrResponse['data'] ?? [];
        }

        $result = DB::transaction(function () use ($validated, $order, $qrData) {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status !== 'pending') {
                abort(422, 'Order was already modified.');
            }

            $payment = Payment::create([
                'order_id'              => $lockedOrder->id,
                'payment_number'        => 'PAY-' . strtoupper(uniqid()),
                'method'                => $validated['method'],
                'amount'                => $validated['amount'],
                'status'                => $validated['method'] === 'cash' ? 'paid' : 'pending',
                'qr'                    => $qrData['qr'] ?? null,
                'md5'                   => $qrData['md5'] ?? null,
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'note'                  => $validated['note'] ?? null,
                'paid_at'               => $validated['method'] === 'cash' ? now() : null,
            ]);

            // Cash settles immediately
            if ($payment->status === 'paid') {
                $this->completeOrder($lockedOrder);
            }

            return [
                'payment' => $payment,
                'order'   => $lockedOrder->fresh()->load('items.product', 'payments'),
            ];
        });

        return response()->json([
            'message' => 'Payment created successfully.',
            'payment' => $result['payment'],
            'order'   => $result['order'],
        ], 201);
    }

    public function verify(Payment $payment, KHQRService $khqrService): JsonResponse
    {
        if ($payment->method !== 'khqr') {
            return response()->json(['message' => 'Only KHQR payments can be verified.'], 422);
        }

        if ($payment->isPaid()) {
            return response()->json([
                'status'  => 'PAID',
                'message' => 'Payment is already marked as paid.',
                'payment' => $payment->load('order'),
            ]);
        }

        if (!$payment->md5) {
            return response()->json(['message' => 'KHQR MD5 reference is missing.'], 422);
        }

        try {
            $result = $khqrService->verify($payment->md5);

            Log::info("Bakong check for Payment #{$payment->id}:", (array) $result);

            if (($result['responseCode'] ?? 1) !== 0) {
                return response()->json([
                    'status'          => 'PENDING',
                    'message'         => $result['responseMessage'] ?? 'Payment has not been completed yet.',
                    'bakong_response' => $result,
                ], 200);
            }

            $transaction = $result['data'] ?? null;

            if (!$transaction) {
                return response()->json([
                    'status'  => 'PENDING',
                    'message' => 'Transaction payload empty.',
                ], 200);
            }

            // Verify settled amount
            $actualAmount = (float) ($transaction['amount'] ?? 0);
            if (round($actualAmount, 2) !== round((float) $payment->amount, 2)) {
                return response()->json([
                    'message' => "Settled amount ({$actualAmount}) does not match payment total ({$payment->amount}).",
                ], 422);
            }

            // Verify receiving account (your @bkrt ID)
            $toAccountId = $transaction['toAccountId'] ?? '';
            $expectedAccountId = config('bakong.account_id');
            if ($expectedAccountId && strtolower($toAccountId) !== strtolower($expectedAccountId)) {
                return response()->json([
                    'message' => 'Bakong transaction was not received by the expected account.',
                ], 422);
            }

            // Settle payment and order
            DB::transaction(function () use ($payment, $transaction) {
                $lockedPayment = Payment::lockForUpdate()->findOrFail($payment->id);

                if ($lockedPayment->isPaid()) {
                    return;
                }

                $transactionReference = $transaction['hash']
                    ?? $transaction['transactionHash']
                    ?? $transaction['reference']
                    ?? null;

                $lockedPayment->markAsPaid($transactionReference, $transaction);

                $order = Order::lockForUpdate()->findOrFail($lockedPayment->order_id);
                if ($order->status === 'pending') {
                    $this->completeOrder($order);
                }
            });

            return response()->json([
                'status'  => 'PAID',
                'message' => 'Payment verified successfully.',
                'payment' => $payment->fresh(),
                'order'   => $payment->order->fresh(['items.product', 'payments']),
                'bakong'  => $transaction,
            ]);

        } catch (Throwable $e) {
            Log::error("Bakong verification error on payment {$payment->id}: " . $e->getMessage());

            return response()->json([
                'message' => 'Unable to verify Bakong payment.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function completeOrder(Order $order): void
    {
        $items = $order->items->sortBy('product_id');

        foreach ($items as $item) {
            $product = Product::lockForUpdate()->findOrFail($item->product_id);

            if ($product->stock < $item->quantity) {
                abort(422, "Insufficient stock for {$product->name}.");
            }

            $stockBefore = $product->stock;
            $stockAfter  = $stockBefore - $item->quantity;

            $product->update(['stock' => $stockAfter]);

            InventoryTransaction::create([
                'product_id'     => $product->id,
                'type'           => 'out',
                'quantity'       => $item->quantity,
                'stock_before'   => $stockBefore,
                'stock_after'    => $stockAfter,
                'reference_type' => 'order',
                'reference_id'   => $order->id,
                'note'           => 'Stock deducted after payment',
                'user_id'        => $order->user_id ?? Auth::id(),
            ]);
        }

        $order->update(['status' => 'completed']);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json($payment->load('order.items.product'));
    }
}
