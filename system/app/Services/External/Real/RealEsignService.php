<?php

namespace App\Services\External\Real;

use App\Services\External\Contracts\EsignServiceInterface;
use Illuminate\Support\Facades\Http;

/**
 * 真实电子签对接(如 e签宝 / 法大大)。
 * TODO[团队]: 按所选电子签厂商的 OpenAPI 实现以下方法。
 */
class RealEsignService implements EsignServiceInterface
{
    public function createSignFlow(array $payload): array
    {
        // TODO[团队]: 调用电子签厂商"创建签署流程"接口。
        //  1. 用 config('external.esign') 的 app_id/secret 取 token
        //  2. 上传/选择合同模板,填充签署方与快照字段
        //  3. 返回 sign_flow_id 与 sign_url
        // 参考:Http::withToken($token)->post(config('external.esign.base_url').'/...', [...]);
        throw new \RuntimeException('RealEsignService::createSignFlow 待团队对接');
    }

    public function querySignStatus(string $signFlowId): array
    {
        // TODO[团队]: 查询签署状态,返回 signed / signed_at / pdf_url。
        throw new \RuntimeException('RealEsignService::querySignStatus 待团队对接');
    }
}
