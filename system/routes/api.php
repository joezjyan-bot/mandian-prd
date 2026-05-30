<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentCallbackController;
use App\Http\Controllers\Api\BuyoutController;

/*
|--------------------------------------------------------------------------
| API 路由
|--------------------------------------------------------------------------
| 这里只是脚手架示范。团队按模块扩充（商家端/运营端/短租等）。
*/

Route::get('/health', [HealthController::class, 'index']);

Route::prefix('orders')->group(function () {
    Route::post('/', [OrderController::class, 'store']);          // 下单
    Route::post('/{order}/sign', [OrderController::class, 'sign']); // 签约（模拟/真实）
    Route::post('/{order}/pay', [OrderController::class, 'pay']);   // 发起首期支付
    Route::get('/{order}', [OrderController::class, 'show']);       // 订单详情（C 端白名单）
});

// 购买价试算 + 申请购买
Route::post('/orders/{order}/buyout/quote', [BuyoutController::class, 'quote']);
Route::post('/orders/{order}/buyout', [BuyoutController::class, 'apply']);

// 支付回调（幂等）
Route::post('/callbacks/payment', [PaymentCallbackController::class, 'handle']);
