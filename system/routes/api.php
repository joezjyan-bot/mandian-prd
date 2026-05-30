<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentCallbackController;
use App\Http\Controllers\Api\BuyoutController;

/**
 * 接口路由。演示阶段先开放,真实上线要加鉴权中间件(sanctum/jwt)与权限校验。
 * TODO[团队]: 按权限矩阵给各端接口加 auth 与 role/permission 中间件。
 */

Route::get('/health', [HealthController::class, 'index']);

// ---------- C 端 / 门店端 下单 ----------
Route::prefix('orders')->group(function () {
    Route::post('/', [OrderController::class, 'store']);                 // 下单
    Route::post('/{order}/pay', [OrderController::class, 'pay']);        // 首期支付
    Route::post('/{order}/expiry-choice', [OrderController::class, 'expiryChoice']); // 到期三选一
    Route::get('/{order}/customer', [OrderController::class, 'showForCustomer']);     // C端查单(白名单)
    Route::get('/{order}/buyout-quote', [BuyoutController::class, 'quote']);          // 申请购买报价
});

// ---------- 支付回调(模拟/真实通道都打到这)----------
Route::post('/callbacks/payment', [PaymentCallbackController::class, 'handle']);

// TODO[团队]: 商家端、运营端、IM、财务、风控等模块路由按 PRD 逐步补充。
