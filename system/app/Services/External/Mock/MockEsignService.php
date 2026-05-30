<?php

namespace App\Services\External\Mock;

use App\Contracts\EsignContract;
use Illuminate\Support\Str;

/**
 * 【演示模式】电子签约——直接返回签署成功。
 */
class MockEsignService implements EsignContract
{
    public function sign(array $payload): array
    {
        return [
            'sign_id' => 'MOCK-SIGN-' . Str::upper(Str::random(10)),
            'status' => 'signed',
            'signed_at' => now()->toIso8601String(),
            'contract_url' => 'https://mock.local/contract/demo.pdf',
        ];
    }

    public function status(string $signId): array
    {
        return ['sign_id' => $signId, 'status' => 'signed'];
    }
}
