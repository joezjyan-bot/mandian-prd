<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $fillable = [
        'subject_code', 'direction', 'amount_cents', 'order_id',
        'payment_flow_id', 'cooperation_mode', 'memo',
    ];
}
