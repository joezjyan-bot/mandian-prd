<?php

namespace App\Services\External\Mock;

use App\Services\External\Contracts\IdVerifyServiceInterface;
use Illuminate\Support\Str;

/**
 * 模拟实名:演示时恒返回通过(高分)。
 */
class MockIdVerifyService implements IdVerifyServiceInterface
{
    public function verify(array $payload): array
    {
        return [
            'verified' => true,
            'score'    => 0.99,
            'trace_no' => 'MOCK-IDV-' . Str::upper(Str::random(12)),
        ];
    }
}
