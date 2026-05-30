<?php

namespace App\Services\External\Real;

use App\Services\External\Contracts\IdVerifyServiceInterface;

/**
 * 真实实名认证对接。注意:人脸只存比对结果/评分,不建可检索人脸库。
 * TODO[团队]: 对接实名/人脸认证厂商。
 */
class RealIdVerifyService implements IdVerifyServiceInterface
{
    public function verify(array $payload): array
    {
        // TODO[团队]: 调用实名认证接口,返回 verified / score / trace_no。
        throw new \RuntimeException('RealIdVerifyService::verify 待团队对接');
    }
}
