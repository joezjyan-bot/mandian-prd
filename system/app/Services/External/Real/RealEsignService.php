<?php

namespace App\Services\External\Real;

use App\Contracts\EsignContract;
use RuntimeException;

/**
 * 【生产模式·空壳】真实电子签对接。
 * TODO[团队]：接入真实电子签通道（e签宝/法大大等）。
 * 配置在 config/external.php 的 esign 节点。用 Guzzle 调用。
 */
class RealEsignService implements EsignContract
{
    public function sign(array $payload): array
    {
        throw new RuntimeException('RealEsignService未实现：TODO[团队]接入真实电子签通道。演示阶段请用 EXTERNAL_MODE=mock。');
    }

    public function status(string $signId): array
    {
        throw new RuntimeException('RealEsignService::status 未实现。TODO[团队]');
    }
}
