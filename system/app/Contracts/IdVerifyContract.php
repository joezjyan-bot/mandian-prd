<?php

namespace App\Contracts;

/**
 * 实名认证接口（身份证 + 人脸）。
 */
interface IdVerifyContract
{
    /**
     * 二要素/三要素 + 人脸核验。
     * 注：人机比对只存结果和评分，不建人脸库；C 端不出现“人脸/风控”字样。
     */
    public function verify(array $payload): array;
}
