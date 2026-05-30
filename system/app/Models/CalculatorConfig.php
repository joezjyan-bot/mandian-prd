<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculatorConfig extends Model
{
    protected $table = 'yzz_calculator_configs';

    protected $fillable = [
        'scope', 'merchant_id', 'product_id', 'periods', 'down_payment_ratios',
        'rate_basis', 'rate_bps', 'rate_base', 'rate_table', 'remaining_multiplier_bps',
        'first_rent_cents', 'nominal_buyout_fee_cents',
        'value_added_services', 'config_version_id', 'status',
    ];

    protected $casts = [
        'periods' => 'array',
        'down_payment_ratios' => 'array',
        'rate_table' => 'array',
        'value_added_services' => 'array',
        'remaining_multiplier_bps' => 'integer',
        'first_rent_cents' => 'integer',
        'nominal_buyout_fee_cents' => 'integer',
    ];
}
