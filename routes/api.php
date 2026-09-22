<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\AuthController;
use App\Services\KHQRService;
use Illuminate\Support\Facades\Route;

// ─── Public routes (no token required) ───────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Protected routes (Sanctum token required) ───────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Resources
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify']);

    // Debug endpoints (Remove before production)
    Route::get('/test-khqr', function (KHQRService $khqrService) {
        return $khqrService->generate(
            config('bakong.account_id', 'chamroeun_sek@bkrt'),
            'POS System',
            'Phnom Penh',
            3.75, // Test amount
            840 // USD
        );
    });

    Route::get('/test-bakong-account', function (Illuminate\Http\Request $request) {
        $account = $request->query('account', config('bakong.account_id', 'chamroeun_sek@bkrt'));
        return response()->json(
            \Piseth\BakongKhqr\BakongKHQR::checkBakongAccount($account)
        );
    });

    Route::post('/test-bakong-verify', function (Illuminate\Http\Request $request, KHQRService $khqrService) {
        $md5 = $request->input('md5');
        if (!$md5) return response()->json(['message' => 'md5 required'], 400);
        return response()->json($khqrService->verify($md5));
    });
});
