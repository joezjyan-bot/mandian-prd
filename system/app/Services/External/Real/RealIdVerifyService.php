<?php

namespace App\Services\External\Real;

use App\Contracts\IdVerifyContract;
use RuntimeException;

/**
 * 【生产模式·空壳】真实实名认证对接。
 * TODO[团队]：接入真实实名/人脸通道。人机比对只存结果和评分，不建人脸库。
 */
class RealIdVerifyService implements IdVerifyContract
{
    public function verify(array $payload): array
    {
        throw new RuntimeException('RealIdVerifyService未实现：TODO[团队]接入真实实名通道。演示阶段请用 EXTERNAL_MODE=mock。');
    }
}
