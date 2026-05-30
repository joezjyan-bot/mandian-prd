<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 交付 / 签收 / 监管锁校验 / 结算 接口。
 * 对应 DeliveryService 的文档三阶段(全局/02 §5.1 结算硬前置):
 *   交付 deliver → 签收 confirmReceipt → 监管锁校验 verifyLock → 平台结算 settle。
 * 既提供分步端点(便于真实流程/中控台回调驱动),也提供 signFor 一键串联(便于演示)。
 */
class DeliveryController extends Controller
{
    public function __construct(private DeliveryService $delivery) {}

    /** 商家交付:录设备码 + (需锁品类)上监管锁 */
    public function deliver(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'device_code' => 'required|string|max:64',
            'need_lock' => 'nullable|boolean',
        ]);
        $order = $this->delivery->deliver($order, $data['device_code'], $data['need_lock'] ?? false);
        return response()->json(['order' => $order]);
    }

    /** 客户签收确认(PENDING_RECEIPT_CONFIRM → PENDING_LOCK_VERIFY) */
    public function confirmReceipt(Order $order): JsonResponse
    {
        $order = $this->delivery->confirmReceipt($order);
        return response()->json(['order' => $order]);
    }

    /** 监管锁校验(PENDING_LOCK_VERIFY → PENDING_PLATFORM_SETTLEMENT / LOCK_VERIFY_FAILED) */
    public function verifyLock(Order $order): JsonResponse
    {
        $order = $this->delivery->verifyLock($order);
        return response()->json(['order' => $order]);
    }

    /** 平台结算(PENDING_PLATFORM_SETTLEMENT → IN_FULFILLMENT) */
    public function settle(Order $order): JsonResponse
    {
        $order = $this->delivery->settle($order);
        return response()->json(['order' => $order]);
    }

    /**
     * 一键签收到结算(演示用):串联签收 → 监管锁校验 → 平台结算。
     * 严格走 DeliveryService 三阶段,每步内部仍按文档结算硬前置校验:
     * 若监管锁校验未通过(LOCK_VERIFY_FAILED),停在该状态、不会进入结算(不自动打款),返回当前订单状态。
     */
    public function signFor(Order $order): JsonResponse
    {
        // 1) 客户签收确认
        $order = $this->delivery->confirmReceipt($order);

        // 2) 监管锁校验(无锁品类直接通过;需锁品类校验上锁+激活锁)
        $order = $this->delivery->verifyLock($order);

        // 3) 仅当监管锁校验通过(进入 PENDING_PLATFORM_SETTLEMENT)才结算;否则按硬前置停住不打款
        if ($order->status === \App\Support\OrderStatus::PENDING_PLATFORM_SETTLEMENT) {
            $order = $this->delivery->settle($order);
            return response()->json(['order' => $order, 'settled' => true]);
        }

        return response()->json([
            'order' => $order,
            'settled' => false,
            'message' => '监管锁校验未通过,订单停在 ' . $order->status . ',按结算硬前置不自动打款',
        ]);
    }
}
