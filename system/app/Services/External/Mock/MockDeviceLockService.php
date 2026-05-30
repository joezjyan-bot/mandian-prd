<?php

namespace App\Services\External\Mock;

use App\Contracts\DeviceLockContract;

/**
 * 【演示模式】监管锁——返回操作成功。
 */
class MockDeviceLockService implements DeviceLockContract
{
    public function lock(string $deviceCode, array $context = []): array
    {
        return ['device_code' => $deviceCode, 'lock_status' => 'locked', 'at' => now()->toIso8601String()];
    }

    public function unlock(string $deviceCode, array $context = []): array
    {
        return ['device_code' => $deviceCode, 'lock_status' => 'unlocked', 'at' => now()->toIso8601String()];
    }

    public function status(string $deviceCode): array
    {
        return ['device_code' => $deviceCode, 'lock_status' => 'unlocked'];
    }
}
