<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculatorConfig extends Model
{
    protected $table = 'yzz_calculator_configs';

    protected $fillable = [
        'scope', 'merchant_id', 'product_id', 'periods', 'down_payment_ratios',
        'rate_basis', 'rate_bps', 'value_added_services', 'config_version_id', 'status',
    ];

    protected $casts = [
        'periods' => 'array',
        'down_payment_ratios' => 'array',
        'value_added_services' => 'array',
    ];
}
