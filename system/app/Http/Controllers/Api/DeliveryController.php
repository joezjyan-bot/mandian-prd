<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryController extends Controller
{
    public function __construct(private DeliveryService $delivery) {}

    /** 商家交付 */
    public function deliver(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'device_code' => 'required|string|max:64',
            'need_lock' => 'nullable|boolean',
        ]);
        $order = $this->delivery->deliver($order, $data['device_code'], $data['need_lock'] ?? false);
        return response()->json(['order' => $order]);
    }

    /** 客户签收 + 结算 */
    public function signFor(Order $order): JsonResponse
    {
        $order = $this->delivery->signForAndSettle($order);
        return response()->json(['order' => $order]);
    }
}
