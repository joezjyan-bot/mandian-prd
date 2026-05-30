<?php

namespace App\Models;

use App\Support\OrderStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * 订单模型。
 * 重点:toCustomerArray() 是 C 端白名单输出,防止内部字段泄露。
 */
class Order extends Model
{
    protected $table = 'yzz_orders';

    protected $fillable = [
        'order_no', 'customer_id', 'merchant_id', 'store_id', 'cooperation_mode',
        'product_name', 'device_code', 'need_lock', 'device_value_cents', 'deposit_cents',
        'periods', 'period_rent_cents', 'total_amount_cents', 'price_snapshot_id',
        'status', 'esign_id', 'signed_at', 'delivered_at', 'received_at', 'settled_at',
    ];

    protected $casts = [
        'need_lock' => 'boolean',
        'signed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function bills()
    {
        return $this->hasMany(Bill::class, 'order_id');
    }

    /**
     * C 端白名单输出。
     * 【C 端红线】绝不输出:合作模式、资方、服务费拆分、商家结算价、
     * 平台利润、返点、风控结论、内部标签、黑灰名单。
     */
    public function toCustomerArray(): array
    {
        return [
            'order_no' => $this->order_no,
            'product_name' => $this->product_name,
            'device_value_yuan' => $this->device_value_cents / 100,
            'deposit_yuan' => $this->deposit_cents / 100,
            'periods' => $this->periods,
            'period_rent_yuan' => $this->period_rent_cents / 100,
            'total_amount_yuan' => $this->total_amount_cents / 100,
            'status' => $this->customerFacingStatus(),
            // 注意:绝不输出 cooperation_mode / merchant 结算 / 服务费 / 资方等
        ];
    }

    /**
     * 把内部状态映射成客户能看的文案。
     * 依据:全局/02 状态字典 §0.2 C 端 8 Tab 映射(由 OrderStatus::customerTab 统一维护)。
     * DRAFT 不展示给客户,兜底"处理中"。
     */
    protected function customerFacingStatus(): string
    {
        $tab = OrderStatus::customerTab($this->status);

        return $tab !== '' ? $tab : '处理中';
    }
}
