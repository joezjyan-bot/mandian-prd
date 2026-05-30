<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 业务参数（可配置）
    |--------------------------------------------------------------------------
    */

    // 平台服务费率，默认 2%（以万分比存：200 = 2.00%）
    'platform_service_fee_bps' => env('PLATFORM_SERVICE_FEE_BPS', 200),

    // 逾期费用：0.05%/日，最低 10 元/日
    'overdue' => [
        'daily_rate_bps' => 5,        // 0.05% = 5 万分比
        'min_daily_fee_cents' => 1000, // 最低 10 元/日 = 1000 分
    ],

    // 最低服务期（安心用统一）：3 个月
    'min_service_months' => 3,

    /*
    | 申请购买价口径【合规敏感·待法务确认】
    | A = 剩余未付租金 + 保证金（一期口径，先上线）
    | B = 设备折旧余值（二期口径，与剩余租金脱钩，降低“名为租赁实为分期买断”认定风险）
    | 切换这里即可，业务代码不动。
    */
    'buyout_formula' => env('BUYOUT_FORMULA', 'A'),

    // 提现规则
    'withdrawal' => [
        'min_cents' => 50000,      // 单笔最低 500 元
        'max_cents' => 2000000,    // 单笔最高 20000 元
        'daily_times' => 3,        // 每日最多 3 次
        'fee_bps' => 20,           // 0.2%
        'fee_fixed_cents' => 50,   // + 0.5 元
    ],

    // 合作模式枚举
    'cooperation_modes' => [
        'self_operate',         // 商家订单
        'joint_venture',        // 联营订单
        'receivables_assignment', // 平台订单
    ],
];
