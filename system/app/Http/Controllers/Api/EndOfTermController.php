<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\EndOfTermService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 到期三选一：归还 / 续租 / 申请购买。
 */
class EndOfTermController extends Controller
{
    public function __construct(private EndOfTermService $service) {}

    public function startReturn(Order $order): JsonResponse
    {
        return response()->json(['order' => $this->service->startReturn($order)]);
    }

    public function renew(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['extra_periods' => 'required|integer|min:1|max:60']);
        return response()->json(['order' => $this->service->renew($order, $data['extra_periods'])]);
    }

    public function applyBuyout(Order $order): JsonResponse
    {
        return response()->json($this->service->applyBuyout($order));
    }
}
