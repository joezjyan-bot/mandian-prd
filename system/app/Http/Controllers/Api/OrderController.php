<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Order\OrderService;
use App\Models\Order;

/**
 * C 端 / 门店端下单相关接口。
 * ⚠️ C端红线:返回客户的数据一律走 Order::toCustomerArray(),不泄露内部字段。
 */
class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    /** 创建订单(订单确认页提交) */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'          => 'required|integer',
            'merchant_id'          => 'required|integer',
            'store_id'             => 'nullable|integer',
            'product_id'           => 'required|integer',
            'cooperation_mode'     => 'required|in:self_operate,joint_venture,receivables_assignment',
            'device_value_cents'   => 'required|integer|min:0',
            'deposit_cents'        => 'required|integer|min:0',
            'first_payment_cents'  => 'required|integer|min:0',
            'period_payment_cents' => 'required|integer|min:0',
            'periods'              => 'required|integer|min:1',
            'quote_snapshot_id'    => 'nullable|integer',
            'snapshot'             => 'nullable|array',
        ]);

        $order = $this->orders->create($data);

        // 演示:下单后即发起签约(模拟)
        $sign = $this->orders->startSigning($order);

        return response()->json([
            'order'    => $order->toCustomerArray(),
            'sign_url' => $sign['sign_url'] ?? null,
        ], 201);
    }

    /** 发起首期支付 */
    public function pay(Order $order): JsonResponse
    {
        $pay = $this->orders->startFirstPayment($order);
        return response()->json(['pay_url' => $pay['pay_url'] ?? null]);
    }

    /** 到期三选一(客户主动选) */
    public function expiryChoice(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['choice' => 'required|in:return,renew,buyout']);
        $result = $this->orders->handleExpiryChoice($order, $data['choice']);
        return response()->json($result);
    }

    /** C 端查订单(白名单输出) */
    public function showForCustomer(Order $order): JsonResponse
    {
        return response()->json($order->toCustomerArray());
    }
}
