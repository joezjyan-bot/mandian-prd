<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'yzz_products';

    protected $fillable = [
        'standard_name', 'display_title', 'brand', 'model', 'category',
        'device_value_cents', 'support_long_rent', 'support_short_rent', 'image_url', 'status',
    ];

    protected $casts = [
        'support_long_rent' => 'boolean',
        'support_short_rent' => 'boolean',
    ];
}
