<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\EsignContract;
use App\Contracts\PaymentContract;
use App\Contracts\DeviceLockContract;
use App\Contracts\IdVerifyContract;
use App\Services\External\Mock\MockEsignService;
use App\Services\External\Mock\MockPaymentService;
use App\Services\External\Mock\MockDeviceLockService;
use App\Services\External\Mock\MockIdVerifyService;
use App\Services\External\Real\RealEsignService;
use App\Services\External\Real\RealPaymentService;
use App\Services\External\Real\RealDeviceLockService;
use App\Services\External\Real\RealIdVerifyService;

/**
 * 根据 config('external.mode') 绑定 Mock* 或 Real* 到接口。
 * 这是“模拟模式一键切换”的唯一绑定点。
 * 业务代码 type-hint 接口即可，不感知具体实现。
 *
 * 注：记得在 config/app.php 的 providers 数组里注册本 Provider。
 */
class ExternalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $isReal = config('external.mode') === 'real';

        $this->app->bind(EsignContract::class, fn () => $isReal ? new RealEsignService() : new MockEsignService());
        $this->app->bind(PaymentContract::class, fn () => $isReal ? new RealPaymentService() : new MockPaymentService());
        $this->app->bind(DeviceLockContract::class, fn () => $isReal ? new RealDeviceLockService() : new MockDeviceLockService());
        $this->app->bind(IdVerifyContract::class, fn () => $isReal ? new RealIdVerifyService() : new MockIdVerifyService());
    }
}
