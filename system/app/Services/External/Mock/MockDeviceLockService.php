<?php

namespace App\Services\External\Mock;

use App\Services\External\Contracts\DeviceLockServiceInterface;

/**
 * 模拟监管锁:演示时直接返回"已上锁/已解锁/在线",不调真实管控平台。
 */
class MockDeviceLockService implements DeviceLockServiceInterface
{
    public function lock(string $deviceSn, array $context = []): array
    {
        return ['success' => true, 'lock_status' => 'locked', 'raw' => ['mock' => true, 'sn' => $deviceSn]];
    }

    public function unlock(string $deviceSn, array $context = []): array
    {
        return ['success' => true, 'lock_status' => 'unlocked', 'raw' => ['mock' => true, 'sn' => $deviceSn]];
    }

    public function queryStatus(string $deviceSn): array
    {
        // 演示:默认已上锁+已激活+在线,使结算前置条件可通过
        return ['locked' => true, 'activation_locked' => true, 'online' => true];
    }
}
