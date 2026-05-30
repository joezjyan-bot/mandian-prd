<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 商品表（标准商品库）。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('yzz_products', function (Blueprint $table) {
            $table->id();
            $table->string('standard_name')->comment('商品标准名（启用后锁定）');
            $table->string('display_title')->nullable()->comment('C 端展示标题（可改）');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('category', 64)->nullable()->comment('手机/电动车/数码等');
            $table->bigInteger('device_value_cents')->default(0)->comment('设备价（分）默认参考');
            $table->boolean('support_long_rent')->default(true);
            $table->boolean('support_short_rent')->default(false);
            $table->string('image_url')->nullable();
            $table->string('status', 16)->default('on')->comment('on/off');
            $table->timestamps();

            $table->index('category');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yzz_products');
    }
};
