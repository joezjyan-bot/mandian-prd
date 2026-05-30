<?php

/**
 * 业务口径配置。把"可调的业务规则"集中在这里,避免散落在代码里写死。
 * 这些口径来自 PRD V0.2.2,部分为合规敏感项(已标注),以法务/财务终审为准。
 */

return [

    // 平台服务费率(基点 bps,200 = 2%)。后台可配,这里是默认值。
    'platform_service_fee_bps' => 200,

    // 逾期费用:0.05%/日,最低 10 元/日。单位:费率 bps + 最低分。
    // 注意:对外不得称"罚息/利息/贷款费用"。
    'overdue' => [
        'daily_rate_bps' => 5,        // 0.05%
        'daily_min_cents' => 1000,    // 10 元
    ],

    // 最低服务期(安心用长租统一,不分品类):月。
    'min_service_period_months' => 3,

    /**
     * 申请购买价口径。⚠️ 合规敏感,待法务确认。
     * A = 剩余应付租金 + 保证金(一期口径,系统现成、可马上开发)
     * B = 折旧余值口径(二期,与剩余租金脱钩)— 留待切换
     *
     * 切换口径时只改这里 + 对应策略类 BuyoutPriceCalculator,
     * 不动下单/到期/支付等业务流程。
     */
    'buyout_formula' => env('BUYOUT_FORMULA', 'A'),

    // 提现规则
    'withdrawal' => [
        'min_cents'       => 50000,    // 单笔最低 500 元
        'max_cents'       => 2000000,  // 单笔最高 20000 元
        'daily_max_times' => 3,        // 每日最多 3 次
        'fee_rate_bps'    => 20,       // 手续费 0.2%
        'fee_fixed_cents' => 50,       // + 0.5 元
        'manual_audit_over_cents' => 2000000, // 超 2 万人工审核
    ],

    // 合作模式枚举(贯穿订单/分账/权限)
    'cooperation_modes' => [
        'self_operate'           => '商家订单',
        'joint_venture'          => '联营订单',
        'receivables_assignment' => '平台订单',
    ],

    // 监管锁适用品类(其它品类不因无锁阻断业务)
    'lock_required_categories' => ['apple_phone', 'apple_pad', 'apple_watch'],
];
