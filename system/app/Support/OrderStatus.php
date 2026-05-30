<?php

namespace App\Support;

/**
 * 长租订单主状态机。
 * 唯一依据:产品文档 全局/02 状态字典与订单状态机 §0.1(V0.2.2 唯一有效主状态)+ §5.1(流转图)。
 *
 * 重要(§0.1 说明):历史状态 PENDING_PAYMENT / PENDING_SHIP / PENDING_RECEIVE /
 * PURCHASE_PENDING / RENTING / COMPLETED / RETAINED 等仅作兼容,新开发必须用本表主状态码。
 * 其中 RENTING→IN_FULFILLMENT,COMPLETED→NORMAL_SETTLED,RETAINED→EARLY_RETAINED。
 *
 * 本状态机仅用于长租(business_line = assurance_rent)。
 * 短租使用独立 SR_* 状态机(§3.2),不得与本枚举混用。
 */
class OrderStatus
{
    // —— §0.1 长租订单主状态(26 个)——
    public const DRAFT = 'DRAFT';                                         // 草稿
    public const PENDING_CUSTOMER_SUBMIT = 'PENDING_CUSTOMER_SUBMIT';     // 待客户提交资料
    public const PENDING_REVIEW = 'PENDING_REVIEW';                       // 待风控审核
    public const REVIEW_REJECTED = 'REVIEW_REJECTED';                     // 审核驳回
    public const PENDING_SUPPLEMENT = 'PENDING_SUPPLEMENT';               // 待补充资料
    public const PENDING_SIGN = 'PENDING_SIGN';                           // 待签约
    public const SIGN_FAILED = 'SIGN_FAILED';                             // 签约失败
    public const PENDING_FIRST_PAYMENT = 'PENDING_FIRST_PAYMENT';         // 待支付首付
    public const FIRST_PAYMENT_FAILED = 'FIRST_PAYMENT_FAILED';           // 首付支付失败
    public const PENDING_DELIVERY = 'PENDING_DELIVERY';                   // 待门店交付
    public const PENDING_RECEIPT_CONFIRM = 'PENDING_RECEIPT_CONFIRM';     // 待客户签收确认
    public const PENDING_LOCK_VERIFY = 'PENDING_LOCK_VERIFY';             // 待监管锁校验
    public const LOCK_VERIFY_FAILED = 'LOCK_VERIFY_FAILED';               // 监管锁失败
    public const PENDING_PLATFORM_SETTLEMENT = 'PENDING_PLATFORM_SETTLEMENT'; // 待平台结算
    public const PAYOUT_FAILED = 'PAYOUT_FAILED';                         // 打款失败
    public const IN_FULFILLMENT = 'IN_FULFILLMENT';                       // 履约中
    public const NORMAL_SETTLED = 'NORMAL_SETTLED';                       // 正常结清
    public const EARLY_RETAINED = 'EARLY_RETAINED';                       // 提前留购/提前结清
    public const REFUNDING = 'REFUNDING';                                 // 退款中
    public const REFUNDED = 'REFUNDED';                                   // 已退款
    public const OVERDUE = 'OVERDUE';                                     // 逾期中
    public const COLLECTION_IN_PROGRESS = 'COLLECTION_IN_PROGRESS';       // 催收中
    public const LEGAL_IN_PROGRESS = 'LEGAL_IN_PROGRESS';                 // 法诉中
    public const BAD_DEBT_WRITTEN_OFF = 'BAD_DEBT_WRITTEN_OFF';           // 坏账核销
    public const BAD_DEBT_RECOVERED = 'BAD_DEBT_RECOVERED';               // 已回收完成
    public const CLOSED = 'CLOSED';                                       // 已关闭
    public const CANCELLED = 'CANCELLED';                                 // 已取消

    /**
     * 合法状态流转(严格按 §5.1 mermaid 流转图)。
     * key = 原状态,value = 允许进入的下一状态集合。
     */
    public const TRANSITIONS = [
        self::DRAFT => [self::PENDING_CUSTOMER_SUBMIT, self::CANCELLED],
        self::PENDING_CUSTOMER_SUBMIT => [self::PENDING_REVIEW, self::CANCELLED],
        self::PENDING_REVIEW => [self::PENDING_SUPPLEMENT, self::REVIEW_REJECTED, self::PENDING_SIGN, self::REFUNDING],
        self::PENDING_SUPPLEMENT => [self::PENDING_REVIEW],
        self::REVIEW_REJECTED => [self::CLOSED],
        self::PENDING_SIGN => [self::SIGN_FAILED, self::PENDING_FIRST_PAYMENT, self::REFUNDING],
        self::SIGN_FAILED => [self::PENDING_SIGN],
        self::PENDING_FIRST_PAYMENT => [self::FIRST_PAYMENT_FAILED, self::PENDING_DELIVERY, self::REFUNDING],
        self::FIRST_PAYMENT_FAILED => [self::PENDING_FIRST_PAYMENT],
        self::PENDING_DELIVERY => [self::PENDING_RECEIPT_CONFIRM, self::REFUNDING],
        self::PENDING_RECEIPT_CONFIRM => [self::PENDING_LOCK_VERIFY],
        self::PENDING_LOCK_VERIFY => [self::LOCK_VERIFY_FAILED, self::PENDING_PLATFORM_SETTLEMENT],
        self::LOCK_VERIFY_FAILED => [self::PENDING_LOCK_VERIFY],
        self::PENDING_PLATFORM_SETTLEMENT => [self::PAYOUT_FAILED, self::IN_FULFILLMENT],
        self::PAYOUT_FAILED => [self::PENDING_PLATFORM_SETTLEMENT],
        self::IN_FULFILLMENT => [self::NORMAL_SETTLED, self::EARLY_RETAINED, self::OVERDUE],
        self::OVERDUE => [self::COLLECTION_IN_PROGRESS, self::IN_FULFILLMENT],
        self::COLLECTION_IN_PROGRESS => [self::LEGAL_IN_PROGRESS],
        self::LEGAL_IN_PROGRESS => [self::BAD_DEBT_WRITTEN_OFF],
        self::BAD_DEBT_WRITTEN_OFF => [self::BAD_DEBT_RECOVERED],
        self::REFUNDING => [self::REFUNDED],
        // 终态无后继:NORMAL_SETTLED / EARLY_RETAINED / REFUNDED / BAD_DEBT_RECOVERED / CLOSED / CANCELLED
    ];

    /**
     * 结算硬前置(§0.1 V0.2.2 补丁 + §5.1):
     * 客户签收确认 + 监管锁已上锁 + 激活锁已激活,三者满足才允许进入待平台结算。
     * 未满足时进入待平台结算/人工复核,不得自动打款。
     */
    public const SETTLEMENT_PREREQUISITES = [
        self::PENDING_RECEIPT_CONFIRM,
        self::PENDING_LOCK_VERIFY,
        self::PENDING_PLATFORM_SETTLEMENT,
    ];

    /**
     * §0.2 C 端 8 Tab 映射:主状态 → C 端 Tab。
     * 内部状态(锁校验/结算/打款/坏账/审核子状态/资金来源)不暴露具体子状态。
     */
    public const CUSTOMER_TAB_MAP = [
        // 审核中
        self::PENDING_CUSTOMER_SUBMIT => '审核中',
        self::PENDING_REVIEW => '审核中',
        self::PENDING_SUPPLEMENT => '审核中',
        // 待付款
        self::PENDING_FIRST_PAYMENT => '待付款',
        self::FIRST_PAYMENT_FAILED => '待付款',
        // 待签约
        self::PENDING_SIGN => '待签约',
        self::SIGN_FAILED => '待签约',
        // 待发货/自提
        self::PENDING_DELIVERY => '待发货',
        // 待收货/签收
        self::PENDING_RECEIPT_CONFIRM => '待收货',
        // 履约中(含锁校验/结算/打款/逾期/法诉/坏账等内部状态,统一对外履约中)
        self::PENDING_LOCK_VERIFY => '履约中',
        self::LOCK_VERIFY_FAILED => '履约中',
        self::PENDING_PLATFORM_SETTLEMENT => '履约中',
        self::PAYOUT_FAILED => '履约中',
        self::IN_FULFILLMENT => '履约中',
        self::OVERDUE => '履约中',
        self::COLLECTION_IN_PROGRESS => '履约中',
        self::LEGAL_IN_PROGRESS => '履约中',
        self::BAD_DEBT_WRITTEN_OFF => '履约中',
        self::BAD_DEBT_RECOVERED => '履约中',
        // 已完成
        self::NORMAL_SETTLED => '已完成',
        self::EARLY_RETAINED => '已完成',
        self::REFUNDING => '已完成',
        self::REFUNDED => '已完成',
        self::REVIEW_REJECTED => '已完成',
        self::CLOSED => '已完成',
        self::CANCELLED => '已完成',
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * C 端展示状态(§0.2)。DRAFT 不展示给客户,返回空串由上层处理。
     */
    public static function customerTab(string $status): string
    {
        return self::CUSTOMER_TAB_MAP[$status] ?? '';
    }
}
