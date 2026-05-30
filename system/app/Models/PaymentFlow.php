<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentFlow extends Model
{
    protected $fillable = [
        'bill_id', 'order_id', 'channel', 'pay_flow_id', 'channel_trade_no',
        'amount_cents', 'fee_cents', 'status', 'callback_event_id', 'paid_time', 'raw',
    ];

    protected $casts = [
        'raw'       => 'array',
        'paid_time' => 'datetime',
    ];
}
