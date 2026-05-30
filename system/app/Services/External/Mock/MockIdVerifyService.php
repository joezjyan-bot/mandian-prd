<?php

namespace App\Services\External\Mock;

use App\Contracts\IdVerifyContract;

/**
 * 【演示模式】实名认证——返回通过。
 */
class MockIdVerifyService implements IdVerifyContract
{
    public function verify(array $payload): array
    {
        return ['passed' => true, 'score' => 99, 'verified_at' => now()->toIso8601String()];
    }
}
