<?php

namespace App\Services\External\Mock;

use App\Contracts\DeviceLockContract;

/**
 * 【演示模式】监管锁——返回操作成功。
 * 演示模式下设备视为已上锁:status() 与 lock() 一致返回 locked,
 * 使结算硬前置(§0.1 监管锁校验)在演示中可走通完整链路;
 * 真实校验由 RealDeviceLockService 按中控台回调判断。
 */
class MockDeviceLockService implements DeviceLockContract
{
    public function lock(string $deviceCode, array $context = []): array
    {
        return ['device_code' => $deviceCode, 'lock_status' => 'locked', 'active_lock' => true, 'at' => now()->toIso8601String()];
    }

    public function unlock(string $deviceCode, array $context = []): array
    {
        return ['device_code' => $deviceCode, 'lock_status' => 'unlocked', 'active_lock' => false, 'at' => now()->toIso8601String()];
    }

    public function status(string $deviceCode): array
    {
        // 演示模式:设备视为已上锁且激活锁已激活,便于走通签收→锁校验→结算链路
        return ['device_code' => $deviceCode, 'lock_status' => 'locked', 'active_lock' => true];
    }
}
