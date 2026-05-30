<?php

namespace App\Services\External\Real;

use App\Services\External\Contracts\DeviceLockServiceInterface;

/**
 * 真实监管锁对接。⚠️ 高危:改锁状态必须配合权限+审批+审计(见权限矩阵 §1.3)。
 * TODO[团队]: 对接设备管控平台 API。
 */
class RealDeviceLockService implements DeviceLockServiceInterface
{
    public function lock(string $deviceSn, array $context = []): array
    {
        // TODO[团队]: 调用管控平台上锁;落库锁记录与回调。
        throw new \RuntimeException('RealDeviceLockService::lock 待团队对接');
    }

    public function unlock(string $deviceSn, array $context = []): array
    {
        // TODO[团队]: 结清后解锁;记录审批与审计。
        throw new \RuntimeException('RealDeviceLockService::unlock 待团队对接');
    }

    public function queryStatus(string $deviceSn): array
    {
        // TODO[团队]: 查询真实锁/激活/在线状态(结算前置条件校验要用)。
        throw new \RuntimeException('RealDeviceLockService::queryStatus 待团队对接');
    }
}
