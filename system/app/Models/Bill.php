<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $table = 'yzz_bills';

    protected $fillable = [
        'order_id', 'period_no', 'bill_type', 'amount_due_cents',
        'amount_paid_cents', 'amount_refunded_cents', 'due_time', 'paid_time', 'status',
    ];

    protected $casts = [
        'due_time' => 'datetime',
        'paid_time' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
