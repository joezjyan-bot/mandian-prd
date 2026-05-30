<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\External\Contracts\EsignServiceInterface;
use App\Services\External\Contracts\PaymentServiceInterface;
use App\Services\External\Contracts\DeviceLockServiceInterface;
use App\Services\External\Contracts\IdVerifyServiceInterface;
use App\Services\External\Mock;
use App\Services\External\Real;

/**
 * 把外部对接接口绑定到 Mock\* 或 Real\* 实现。
 * 由 config('external.mode') 决定。这是"一键切换模拟/真实对接"的核心。
 *
 * TODO[团队]: 在 bootstrap/providers.php 注册本 Provider。
 */
class ExternalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $mode = config('external.mode', 'mock');

        $map = [
            EsignServiceInterface::class => [
                'mock' => Mock\MockEsignService::class,
                'real' => Real\RealEsignService::class,
            ],
            PaymentServiceInterface::class => [
                'mock' => Mock\MockPaymentService::class,
                'real' => Real\RealPaymentService::class,
            ],
            DeviceLockServiceInterface::class => [
                'mock' => Mock\MockDeviceLockService::class,
                'real' => Real\RealDeviceLockService::class,
            ],
            IdVerifyServiceInterface::class => [
                'mock' => Mock\MockIdVerifyService::class,
                'real' => Real\RealIdVerifyService::class,
            ],
        ];

        foreach ($map as $contract => $impls) {
            $impl = $impls[$mode] ?? $impls['mock'];
            $this->app->bind($contract, $impl);
        }
    }
}
