<?php

namespace App\Support;

/**
 * 订单状态机常量（与文档 全局/02 状态字典 对齐）。
 * 业务代码统一引用这里，不要在各处写裸字符串。
 */
class OrderStatus
{
    public const CREATED = 'created';         // 已下单待完善
    public const SIGNING = 'signing';         // 待签约
    public const PAYING = 'paying';           // 待首期支付
    public const DELIVERING = 'delivering';   // 待交付
    public const SIGNED_FOR = 'signed_for';   // 客户已签收
    public const ACTIVE = 'active';           // 履约中
    public const OVERDUE = 'overdue';         // 逾期
    public const RETURNING = 'returning';     // 归还中
    public const BUYING_OUT = 'buying_out';   // 申请购买中
    public const RENEWING = 'renewing';       // 续租中
    public const COMPLETED = 'completed';     // 已完成
    public const CANCELLED = 'cancelled';     // 已取消

    /**
     * 允许的状态流转（示范，团队可按业务调整）。
     */
    public const TRANSITIONS = [
        self::CREATED => [self::SIGNING, self::CANCELLED],
        self::SIGNING => [self::PAYING, self::CANCELLED],
        self::PAYING => [self::DELIVERING, self::CANCELLED],
        self::DELIVERING => [self::SIGNED_FOR, self::CANCELLED],
        self::SIGNED_FOR => [self::ACTIVE],
        self::ACTIVE => [self::OVERDUE, self::RETURNING, self::BUYING_OUT, self::RENEWING, self::COMPLETED],
        self::OVERDUE => [self::ACTIVE, self::RETURNING, self::BUYING_OUT],
        self::RETURNING => [self::COMPLETED],
        self::BUYING_OUT => [self::COMPLETED],
        self::RENEWING => [self::ACTIVE],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }
}
