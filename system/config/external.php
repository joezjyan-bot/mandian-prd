<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 外部对接模式
    |--------------------------------------------------------------------------
    | mock = 演示模式：签约/支付/监管锁/实名 全部返回模拟成功
    | real = 生产模式：走真实对接（需团队填完 Real* 实现）
    |
    | ExternalServiceProvider 会根据这个值绑定 Mock* 或 Real* 到接口。
    | 业务代码只依赖接口，不感知具体是哪套实现。
    */
    'mode' => env('EXTERNAL_MODE', 'mock'),

    'zhongkong' => [
        'base_url' => env('ZHONGKONG_BASE_URL', ''),
        'api_key' => env('ZHONGKONG_API_KEY', ''),
    ],

    'payment' => [
        'base_url' => env('PAYMENT_BASE_URL', ''),
        'merchant_id' => env('PAYMENT_MERCHANT_ID', ''),
        'secret' => env('PAYMENT_SECRET', ''),
    ],

    'esign' => [
        'base_url' => env('ESIGN_BASE_URL', ''),
        'app_id' => env('ESIGN_APP_ID', ''),
        'secret' => env('ESIGN_SECRET', ''),
    ],
];
