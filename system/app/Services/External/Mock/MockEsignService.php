<?php

namespace App\Services\External\Mock;

use App\Services\External\Contracts\EsignServiceInterface;
use Illuminate\Support\Str;

/**
 * 模拟电子签:不调真实通道,直接返回"签署成功",用于演示整套流程。
 * 演示时:前端拿到 sign_url 可以是一个假的"点击即签"页;querySignStatus 恒返回已签。
 */
class MockEsignService implements EsignServiceInterface
{
    public function createSignFlow(array $payload): array
    {
        $flowId = 'MOCK-SIGN-' . Str::upper(Str::random(12));

        return [
            'success'      => true,
            'sign_flow_id' => $flowId,
            'sign_url'     => url("/mock/esign/{$flowId}"),
            'raw'          => ['mock' => true, 'payload' => $payload],
        ];
    }

    public function querySignStatus(string $signFlowId): array
    {
        return [
            'signed'    => true,
            'signed_at' => now()->toIso8601String(),
            'pdf_url'   => url("/mock/esign/{$signFlowId}/contract.pdf"),
        ];
    }
}
