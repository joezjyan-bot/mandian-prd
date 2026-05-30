<?php

namespace App\Services\External\Contracts;

/**
 * 实名认证接口(身份证 + 人脸)。
 * 注意:人脸只存比对结果/评分,不建可检索人脸库;C 端不出现"人脸/风控"字眼。
 */
interface IdVerifyServiceInterface
{
    /**
     * @param  array  $payload  ['name'=>string,'id_no'=>string,'face_image'=>?string]
     * @return array  ['verified'=>bool,'score'=>float,'trace_no'=>string]
     */
    public function verify(array $payload): array;
}
