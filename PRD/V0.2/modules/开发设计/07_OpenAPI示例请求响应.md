# OpenAPI 示例请求响应

> 所属：开发设计
> 关联文件：`PRD/V0.2/openapi/core-api.v0.2.yaml`
> 目标：给核心接口补最小可理解示例，方便前后端、Claude 和测试按同一字段理解。

---

## 1. 创建商家入驻申请

请求：

```json
{
  "mobile": "encrypted_or_masked_mobile",
  "password": "client_encrypted_password",
  "sms_code": "123456",
  "invite_code": "CHN_xxx",
  "agree_terms": true
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "application_id": "app_demo",
    "merchant_id": "mch_demo",
    "store_id": "store_demo",
    "next_step": "upload_qualification"
  },
  "warnings": []
}
```

---

## 2. 生成办单助手报价

请求：

```json
{
  "order_type": "dividend",
  "merchant_id": "mch_demo",
  "store_id": "store_demo",
  "product_id": "prod_demo",
  "sku_id": "sku_demo",
  "rent_mode": "long",
  "billing_unit": "month",
  "term": 6,
  "down_payment": 100000,
  "selected_service_ids": ["svc_notary"],
  "funding_ratio": 80
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "quote_snapshot_id": "quote_demo",
    "price_items": [
      {
        "code": "down_payment",
        "label": "首期实付",
        "amount": 100000
      },
      {
        "code": "monthly_rent",
        "label": "后期月付",
        "amount": 101000
      },
      {
        "code": "notary_fee",
        "label": "公证费",
        "amount": 8000
      }
    ],
    "bill_plan_preview": [
      {
        "period_no": 1,
        "bill_type": "first",
        "amount_due": 100000,
        "due_rule": "submit_order"
      },
      {
        "period_no": 2,
        "bill_type": "rent",
        "amount_due": 101000,
        "due_rule": "monthly_after_start"
      }
    ],
    "funding_preview": {
      "funding_ratio": 80,
      "store_ratio": 20,
      "funder_amount": 400000,
      "store_amount": 100000
    },
    "config_version_id": "cfg_demo",
    "expires_at": "2026-05-24T23:59:59+08:00"
  },
  "warnings": []
}
```

说明：

- 金额单位为分。
- `funding_preview` 只在联营订单需要展示。
- 首期和后续账单必须分开。

---

## 3. 客户扫码创建订单

请求：

```json
{
  "qrcode_payload": "signed_payload_demo",
  "quote_snapshot_id": "quote_demo",
  "customer_open_id": "openid_demo",
  "customer_source": "alipay"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "order_id": "ord_demo",
    "order_no": "masked_order_no",
    "order_type": "dividend",
    "main_status": "PENDING_SUBMIT",
    "pending_tasks": [
      "submit_profile",
      "risk_authorization",
      "pay_first_bill"
    ]
  },
  "warnings": []
}
```

---

## 4. 审核通过订单

请求：

```json
{
  "review_note": "资料齐全，审核通过",
  "confirm_token": "confirm_demo",
  "next_actions": [
    "create_contract",
    "create_notary",
    "create_payment",
    "create_ship_task"
  ]
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "order_id": "ord_demo",
    "main_status": "PENDING_SIGN",
    "review_status": "REVIEW_APPROVED",
    "generated_tasks": [
      "customer_sign_contract",
      "customer_finish_notary"
    ],
    "operation_log_id": "oplog_demo"
  },
  "warnings": []
}
```

---

## 5. 分配内部资金来源

请求：

```json
{
  "funder_id": "funder_demo",
  "funding_amount": 400000,
  "funding_ratio": 80,
  "allocation_rule": {
    "store_ratio": 20,
    "funder_ratio": 80,
    "platform_commission_rate": 2,
    "commission_mode": "deduct_after_split"
  }
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "assignment_id": "assign_demo",
    "funder_available_credit": 600000,
    "allocation_snapshot_id": "alloc_snap_demo"
  },
  "warnings": []
}
```

---

## 6. 发起提现

请求：

```json
{
  "amount": 500000,
  "receive_account_id": "acct_demo",
  "owner_type": "merchant",
  "owner_id": "mch_demo"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "withdraw_id": "wd_demo",
    "withdraw_status": "pending_review",
    "frozen_amount": 500000,
    "wallet_balance": 1200000
  },
  "warnings": []
}
```

---

## 7. 第三方回调接收

请求：

```json
{
  "external_event_id": "event_demo",
  "external_trade_no": "trade_demo",
  "status": "success",
  "amount": 100000,
  "paid_at": "2026-05-24T12:00:00+08:00"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "callback_event_id": "cb_demo",
    "verify_status": "pending",
    "process_status": "queued"
  },
  "warnings": []
}
```

说明：

- 回调响应只表示已接收。
- 验签和业务处理进入异步队列。
- 业务成功后再更新支付、合同、公证、物流等子状态。

---

## 8. 示例使用边界

1. 示例中的编号都是占位，不代表真实数据。
2. 真实手机号、身份证、订单号、交易号不写入文档。
3. 金额统一用分。
4. 前端展示文案从状态文案表取，不直接展示后端编码。
