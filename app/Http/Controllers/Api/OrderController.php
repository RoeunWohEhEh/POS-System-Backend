<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        return response()->json(
            Order::with('items.product', 'payments')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',

            'items' => 'required|array|min:1',

            'items.*.product_id' =>
                'required|exists:products,id',

            'items.*.quantity' =>
                'required|integer|min:1',
        ]);

        $discount = $validated['discount'] ?? 0;
        $tax = $validated['tax'] ?? 0;

        $order = DB::transaction(function () use (
            $validated,
            $discount,
            $tax
        ) {
            $subtotal = 0;

            $items = [];

            foreach ($validated['items'] as $item) {

                $product = Product::findOrFail(
                    $item['product_id']
                );

                if (!$product->status) {
                    abort(422, "Product {$product->name} is inactive.");
                }

                if ($product->stock < $item['quantity']) {
                    abort(422, "Not enough stock for {$product->name}.");
                }

                $itemSubtotal =
                    $product->price * $item['quantity'];

                $subtotal += $itemSubtotal;

                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $total = $subtotal - $discount + $tax;

            if ($total < 0) {
                abort(422, 'Order total cannot be negative.');
            }

            $order = Order::create([
                'order_number' =>
                    'ORD-' . strtoupper(uniqid()),

                'user_id' => auth()->id(),

                'subtotal' => $subtotal,

                'discount' => $discount,

                'tax' => $tax,

                'total' => $total,

                // Important
                'status' => 'pending',
            ]);

            foreach ($items as $item) {

                $order->items()->create([
                    'product_id' =>
                        $item['product']->id,

                    'quantity' =>
                        $item['quantity'],

                    'unit_price' =>
                        $item['unit_price'],

                    'subtotal' =>
                        $item['subtotal'],
                ]);
            }

            return $order;
        });

        return response()->json([
            'message' => 'Order created successfully.',
            'order' => $order->load('items.product'),
        ], 201);
    }

    public function show(Order $order)
    {
        return response()->json(
            $order->load(
                'items.product',
                'payments'
            )
        );
    }
}