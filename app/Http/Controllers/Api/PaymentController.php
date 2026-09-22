<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json(
            Payment::with('order')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request, \App\Services\KHQRService $khqrService)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',

            'method' =>
                'required|in:cash,khqr,card',

            'amount' =>
                'required|numeric|min:0',

            'transaction_reference' =>
                'nullable|string|max:255',

            'note' =>
                'nullable|string',
        ]);

        $result = DB::transaction(function () use ($validated, $khqrService) {

            $order = Order::with('items')
                ->lockForUpdate()
                ->findOrFail($validated['order_id']);

            if ($order->status !== 'pending') {
                abort(422, 'Order is not pending.');
            }

            if ($order->payments()->exists()) {
                abort(422, 'A payment already exists for this order.');
            }

            if ((float) $validated['amount'] !== (float) $order->total) {
                abort(422, 'Payment amount must equal order total.');
            }

            $paymentData = [
                'order_id' => $order->id,

                'payment_number' =>
                    'PAY-' . strtoupper(uniqid()),

                'method' =>
                    $validated['method'],

                'amount' =>
                    $validated['amount'],

                'status' =>
                    $validated['method'] === 'cash'
                        ? 'paid'
                        : 'pending',

                'transaction_reference' =>
                    $validated['transaction_reference']
                    ?? null,

                'note' =>
                    $validated['note']
                    ?? null,
            ];

            if ($validated['method'] === 'khqr') {
                $khqr = $khqrService->generate(
                    config('bakong.account_id'),
                    config('bakong.merchant_name'),
                    config('bakong.merchant_city'),
                    (float) $order->total
                );

                $paymentData['qr'] = $khqr['data']['qr'] ?? $khqr['qr'] ?? null;
                $paymentData['md5'] = $khqr['data']['md5'] ?? $khqr['md5'] ?? null;
            }

            $payment = Payment::create($paymentData);

            // Cash is paid immediately
            if ($payment->status === 'paid') {
                $this->completeOrder($order);
            }

            return [
                'payment' => $payment,
                'order' => $order->fresh()
                    ->load('items.product', 'payments'),
            ];
        });

        return response()->json([
            'message' =>
                'Payment created successfully.',

            'payment' =>
                $result['payment'],

            'order' =>
                $result['order'],
        ], 201);
    }

    public function verify(Payment $payment, \App\Services\KHQRService $khqrService)
    {
        if ($payment->method !== 'khqr') {
            return response()->json([
                'message' => 'Only KHQR payments can be verified.'
            ], 422);
        }

        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'Payment is already paid.',
                'payment' => $payment->load('order'),
            ]);
        }

        if (!$payment->md5) {
            return response()->json([
                'message' => 'KHQR MD5 is missing.'
            ], 422);
        }

        try {

            $result = $khqrService->verify(
                $payment->md5
            );

            \Log::info('Bakong Verify Result: ', $result);

            /*
            |--------------------------------------------------------------------------
            | Bakong responseCode
            | 0 = Success
            | 1 = Failed / Not found
            |--------------------------------------------------------------------------
            */

            if (($result['responseCode'] ?? 1) !== 0) {
                return response()->json([
                    'message' =>
                        $result['responseMessage']
                        ?? 'Payment has not been completed yet.',
                    'bakong_response' => $result,
                ], 422);
            }

            $transaction = $result['data'] ?? null;

            if (!$transaction) {
                return response()->json([
                    'message' => 'Bakong payment data not found.'
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Amount & Receiving Account
            |--------------------------------------------------------------------------
            */
            $actualAmount = (float) ($transaction['amount'] ?? 0);
            
            // Compare rounded amounts to prevent float precision issues
            if (round($actualAmount, 2) !== round((float)$payment->amount, 2)) {
                return response()->json([
                    'message' => 'Bakong transaction amount does not match the order total.'
                ], 422);
            }

            $toAccountId = $transaction['toAccountId'] ?? '';
            $expectedAccountId = config('bakong.account_id');
            
            if ($expectedAccountId && $toAccountId !== $expectedAccountId) {
                return response()->json([
                    'message' => 'Bakong transaction was not received by the expected account.'
                ], 422);
            }

            DB::transaction(function () use (
                $payment,
                $transaction
            ) {

                $payment = Payment::lockForUpdate()
                    ->findOrFail($payment->id);

                if ($payment->status === 'paid') {
                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Save transaction reference
                |--------------------------------------------------------------------------
                */

                $transactionReference =
                    $transaction['hash']
                    ?? $transaction['transactionHash']
                    ?? $transaction['reference']
                    ?? null;

                $payment->update([
                    'status' => 'paid',
                    'transaction_reference' =>
                        $transactionReference,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Complete order
                |--------------------------------------------------------------------------
                */

                $order = Order::lockForUpdate()
                    ->findOrFail($payment->order_id);

                if ($order->status === 'pending') {
                    $this->completeOrder($order);
                }
            });

            return response()->json([
                'message' => 'Payment verified successfully.',
                'payment' => $payment->fresh(),
                'order' => $payment->order->fresh([
                    'items.product',
                    'payments',
                ]),
                'bakong' => $transaction,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'Unable to verify Bakong payment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function completeOrder(Order $order)
    {
        foreach ($order->items as $item) {

            $product = Product::lockForUpdate()
                ->findOrFail($item->product_id);

            if ($product->stock < $item->quantity) {
                abort(
                    422,
                    "Not enough stock for {$product->name}."
                );
            }

            $stockBefore = $product->stock;

            $product->decrement(
                'stock',
                $item->quantity
            );

            $stockAfter =
                $stockBefore - $item->quantity;

            InventoryTransaction::create([
                'product_id' =>
                    $product->id,

                'type' => 'out',

                'quantity' =>
                    $item->quantity,

                'stock_before' =>
                    $stockBefore,

                'stock_after' =>
                    $stockAfter,

                'reference_type' => 'order',

                'reference_id' =>
                    $order->id,

                'note' =>
                    'Stock deducted after payment',

                'user_id' =>
                    $order->user_id ?? Auth::id(),
            ]);
        }

        $order->update([
            'status' => 'completed',
        ]);
    }

    public function show(Payment $payment)
    {
        return response()->json(
            $payment->load('order')
        );
    }
}