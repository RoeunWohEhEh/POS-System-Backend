<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'image' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product->load('category'),
        ], 201);
    }

    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product->load('category'),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'cost' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'image' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product->load('category'),
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }
}