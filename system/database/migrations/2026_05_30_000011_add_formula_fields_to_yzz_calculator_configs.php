<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 给 yzz_calculator_configs 补办单助手公式字段。
 * 依据:门店手机端/办单助手/02 计算器字段与账单公式表 §1.2/§1.4/§1.5/§11。
 * - rate_base:费率计算方式三口径(§1.5)unpaid_x_rate / price_x_rate / remaining_multiplier。
 *   原 rate_basis(两枚举)保留作兼容,新逻辑读 rate_base。
 * - rate_table:二维费率表 rates[期数][首付成数](§11),JSON 存。
 * - remaining_multiplier_bps:remaining_multiplier 方案的系数(万分比)。
 * - first_rent_cents:首期租金(§1.2,默认 1000 分=10 元)。
 * - nominal_buyout_fee_cents:名义留购费(§1.4,默认 100 分=1 元)。
 * 这些是 §1.2 账单/留购公式的直接输入,与办单助手算价直接相关。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('yzz_calculator_configs', function (Blueprint $table) {
            $table->enum('rate_base', ['unpaid_x_rate', 'price_x_rate', 'remaining_multiplier'])
                  ->default('unpaid_x_rate')->after('rate_basis')
                  ->comment('费率计算方式(§1.5):未付额×费率/设备价×费率/剩余应付系数');
            $table->json('rate_table')->nullable()->after('rate_base')
                  ->comment('二维费率表 rates[期数][首付成数](§11),倍数');
            $table->unsignedInteger('remaining_multiplier_bps')->default(0)->after('rate_table')
                  ->comment('remaining_multiplier方案系数(万分比)');
            $table->unsignedInteger('first_rent_cents')->default(1000)->after('remaining_multiplier_bps')
                  ->comment('首期租金(分,默认1000=10元,§1.2)');
            $table->unsignedInteger('nominal_buyout_fee_cents')->default(100)->after('first_rent_cents')
                  ->comment('名义留购费(分,默认100=1元,§1.4末期留购)');
        });
    }

    public function down(): void
    {
        Schema::table('yzz_calculator_configs', function (Blueprint $table) {
            $table->dropColumn(['rate_base', 'rate_table', 'remaining_multiplier_bps', 'first_rent_cents', 'nominal_buyout_fee_cents']);
        });
    }
};
