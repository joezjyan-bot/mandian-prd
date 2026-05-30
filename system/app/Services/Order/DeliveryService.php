<?php

namespace App\Services\Order;

use App\Contracts\DeviceLockContract;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 交付 / 签收 / 结算 服务。
 * 监管锁走 DeviceLockContract（mock/real 不感知）。
 */
class DeliveryService
{
    public function __construct(private DeviceLockContract $deviceLock) {}

    /**
     * 商家交付：录设备唯一码、（苹果类）上监管锁。
     */
    public function deliver(Order $order, string $deviceCode, bool $needLock = false): Order
    {
        if ($order->status !== OrderStatus::DELIVERING) {
            throw new RuntimeException("订单状态不允许交付：{$order->status}");
        }

        return DB::transaction(function () use ($order, $deviceCode, $needLock) {
            $order->device_code = $deviceCode;
            $order->delivered_at = now();

            if ($needLock) {
                // 监管锁上锁（演示模式返回成功）
                $this->deviceLock->lock($deviceCode, ['order_no' => $order->order_no]);
            }

            $order->status = OrderStatus::SIGNED_FOR;
            $order->save();

            return $order;
        });
    }

    /**
     * 客户签收 + 平台结算 → 进入履约中。
     */
    public function signForAndSettle(Order $order): Order
    {
        if ($order->status !== OrderStatus::SIGNED_FOR) {
            throw new RuntimeException("订单状态不允许结算：{$order->status}");
        }

        $order->status = OrderStatus::ACTIVE;
        $order->settled_at = now();
        $order->save();

        return $order;
    }
}
