<?php

namespace App\Services\External\Contracts;

/**
 * 监管锁接口(苹果类设备远程管控)。
 * ⚠️ 改锁状态是高危操作,真实实现必须配合权限+审批+审计(见权限矩阵)。
 */
interface DeviceLockServiceInterface
{
    /** 上锁 @return array ['success'=>bool,'lock_status'=>string,'raw'=>array] */
    public function lock(string $deviceSn, array $context = []): array;

    /** 解锁 */
    public function unlock(string $deviceSn, array $context = []): array;

    /** 查询锁与激活状态 @return array ['locked'=>bool,'activation_locked'=>bool,'online'=>bool] */
    public function queryStatus(string $deviceSn): array;
}
