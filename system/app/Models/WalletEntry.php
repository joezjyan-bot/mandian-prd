<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletEntry extends Model
{
    protected $fillable = [
        'merchant_id', 'direction', 'entry_type', 'amount_cents', 'order_id',
        'balance_before_cents', 'balance_after_cents', 'status', 'description',
    ];
}
