<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'customer_id', 'merchant_id', 'store_id', 'product_id',
        'cooperation_mode', 'biz_line', 'device_value_cents', 'deposit_cents',
        'first_payment_cents', 'period_payment_cents', 'periods',
        'min_service_period_months', 'status', 'order_snapshot', 'quote_snapshot_id',
        'settled_at',
    ];

    protected $casts = [
        'order_snapshot' => 'array',
        'settled_at'     => 'datetime',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function paymentFlows(): HasMany
    {
        return $this->hasMany(PaymentFlow::class);
    }

    /**
     * C 端安全字段白名单。⚠️ C端红线:绝不输出 cooperation_mode、内部费用拆分、
     * merchant 结算价、风控结论等。返回给客户的接口只允许下列字段。
     */
    public function toCustomerArray(): array
    {
        return [
            'order_no'            => $this->order_no,
            'status'              => $this->status,
            'device_value_cents'  => $this->device_value_cents,
            'deposit_cents'       => $this->deposit_cents,
            'first_payment_cents' => $this->first_payment_cents,
            'period_payment_cents' => $this->period_payment_cents,
            'periods'             => $this->periods,
            'min_service_period_months' => $this->min_service_period_months,
            // 注意:不含 cooperation_mode / merchant_id / 内部快照等
        ];
    }
}
