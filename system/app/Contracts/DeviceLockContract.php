<?php

namespace App\Contracts;

/**
 * 监管锁（中控台）接口。适用于苹果类设备。
 */
interface DeviceLockContract
{
    /** 上锁 */
    public function lock(string $deviceCode, array $context = []): array;

    /** 解锁 */
    public function unlock(string $deviceCode, array $context = []): array;

    /** 查锁状态 */
    public function status(string $deviceCode): array;
}
