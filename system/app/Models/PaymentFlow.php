<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentFlow extends Model
{
    protected $table = 'yzz_payment_flows';

    protected $fillable = [
        'bill_id', 'order_id', 'channel', 'channel_trade_no', 'amount_cents',
        'fee_cents', 'status', 'callback_event_id', 'paid_time',
    ];

    protected $casts = [
        'paid_time' => 'datetime',
    ];
}
