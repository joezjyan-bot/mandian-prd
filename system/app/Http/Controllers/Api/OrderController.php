<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PriceSnapshot;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => 'required|integer',
            'merchant_id' => 'required|integer',
            'store_id' => 'nullable|integer',
            'cooperation_mode' => 'required|in:self_operate,joint_venture,receivables_assignment',
            'product_name' => 'required|string',
            'device_value_cents' => 'required|integer|min:0',
            'deposit_cents' => 'nullable|integer|min:0',
            'periods' => 'required|integer|min:1',
            'period_rent_cents' => 'required|integer|min:0',
            'total_amount_cents' => 'nullable|integer|min:0',
            'price_snapshot_id' => 'nullable|integer',
        ]);

        $order = $this->orderService->create($data);

        // 返回完整订单（内部/运营视角）
        return response()->json(['order' => $order], 201);
    }

    public function sign(Order $order): JsonResponse
    {
        $order = $this->orderService->sign($order);
        return response()->json(['order' => $order]);
    }

    /**
     * 首期支付。
     * 首期实付金额(§8)取自订单冻结的报价快照 first_pay_cents;
     * 若订单未挂快照,允许请求显式传 first_pay_cents 兜底;两者都无则报错(不擅自猜金额)。
     */
    public function pay(Order $order, Request $request): JsonResponse
    {
        $firstPayCents = null;

        if ($order->price_snapshot_id) {
            $snapshot = PriceSnapshot::find($order->price_snapshot_id);
            if ($snapshot) {
                $firstPayCents = (int) $snapshot->first_pay_cents;
            }
        }

        if ($firstPayCents === null) {
            $validated = $request->validate([
                'first_pay_cents' => 'required|integer|min:0',
            ]);
            $firstPayCents = (int) $validated['first_pay_cents'];
        }

        $result = $this->orderService->payFirstBill($order, $firstPayCents);
        return response()->json($result);
    }

    /**
     * 订单详情——C 端白名单输出。
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json(['order' => $order->toCustomerArray()]);
    }
}
