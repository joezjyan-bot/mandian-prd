<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status'        => 'ok',
            'service'       => '满点租赁系统',
            'external_mode' => config('external.mode'),
            'buyout_formula' => config('business.buyout_formula'),
            'time'          => now()->toIso8601String(),
        ]);
    }
}
