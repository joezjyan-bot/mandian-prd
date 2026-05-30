<?php

namespace App\Services\External\Real;

use App\Contracts\DeviceLockContract;
use RuntimeException;

/**
 * 【生产模式·空壳】真实监管锁对接（中控台 HTTP API）。
 * TODO[团队]：用 Guzzle 调中控台，地址在 config/external.php 的 zhongkong 节点。
 */
class RealDeviceLockService implements DeviceLockContract
{
    public function lock(string $deviceCode, array $context = []): array
    {
        throw new RuntimeException('RealDeviceLockService未实现：TODO[团队]接中控台。演示阶段请用 EXTERNAL_MODE=mock。');
    }

    public function unlock(string $deviceCode, array $context = []): array
    {
        throw new RuntimeException('RealDeviceLockService::unlock 未实现。TODO[团队]');
    }

    public function status(string $deviceCode): array
    {
        throw new RuntimeException('RealDeviceLockService::status 未实现。TODO[团队]');
    }
}
