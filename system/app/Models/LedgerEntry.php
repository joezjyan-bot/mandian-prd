<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $table = 'yzz_ledger_entries';

    protected $fillable = [
        'voucher_no', 'order_id', 'account_code', 'account_name',
        'dc', 'amount_cents', 'summary', 'booked_at',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
    ];
}
