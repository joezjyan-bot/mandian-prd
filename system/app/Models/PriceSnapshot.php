<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceSnapshot extends Model
{
    protected $table = 'yzz_price_snapshots';

    protected $fillable = [
        'snapshot_no', 'product_id', 'merchant_id', 'store_id', 'cooperation_mode',
        'device_value_cents', 'periods', 'down_payment_ratio', 'deposit_cents',
        'first_pay_cents', 'period_rent_cents', 'total_amount_cents',
        'buyout_by_period', 'value_added_services', 'config_version_id', 'status',
    ];

    protected $casts = [
        'buyout_by_period' => 'array',
        'value_added_services' => 'array',
    ];
}
