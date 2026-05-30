<?php

/**
 * 外部对接配置。
 *
 * 核心:EXTERNAL_MODE 控制所有外部对接走"模拟"还是"真实"。
 * 业务代码通过依赖注入拿接口(Contracts),由 ExternalServiceProvider 按此配置
 * 绑定到 Mock\* 或 Real\* 实现。切换对接只改这里,不动业务代码。
 */

return [

    // mock = 模拟模式(演示);real = 真实对接(团队填 Real\*)
    'mode' => env('EXTERNAL_MODE', 'mock'),

    // 电子签
    'esign' => [
        'app_id'     => env('ESIGN_APP_ID'),
        'app_secret' => env('ESIGN_APP_SECRET'),
        'base_url'   => env('ESIGN_BASE_URL'),
    ],

    // 支付
    'pay' => [
        'mch_id'     => env('PAY_MCH_ID'),
        'api_key'    => env('PAY_API_KEY'),
        'notify_url' => env('PAY_NOTIFY_URL'),
    ],

    // 监管锁(苹果类设备)
    'lock' => [
        'base_url' => env('LOCK_API_BASE_URL'),
        'api_key'  => env('LOCK_API_KEY'),
    ],

    // 实名认证
    'idverify' => [
        'app_id'     => env('IDVERIFY_APP_ID'),
        'app_secret' => env('IDVERIFY_APP_SECRET'),
    ],

    // 中控台(Java)— HTTP + Webhook,无 SDK
    'kongzhong' => [
        'base_url'       => env('KONGZHONG_BASE_URL', 'http://localhost:9000'),
        'webhook_secret' => env('KONGZHONG_WEBHOOK_SECRET'),
    ],

];
