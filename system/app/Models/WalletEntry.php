<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletEntry extends Model
{
    protected $table = 'yzz_wallet_entries';

    protected $fillable = [
        'merchant_id', 'order_id', 'direction', 'entry_type', 'amount_cents',
        'balance_before_cents', 'balance_after_cents', 'status', 'description',
    ];
}
