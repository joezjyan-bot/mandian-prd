# OpenAPI 新增接口示例

> 所属：开发设计
> 关联文件：`PRD/V0.2/openapi/core-api.v0.2.yaml`
> 目标：给本轮新增的商品、合同、公证、履约、财务、渠道、租后、客诉接口补典型请求响应，方便后端和 Claude 继续落 controller/service。

---

## 1. 创建商品和规格

请求：

```json
{
  "owner_type": "platform",
  "owner_id": "platform_main",
  "name": "示例电动车",
  "category_id": "cat_ev",
  "rent_modes": ["long", "short"],
  "assistant_sync_enabled": true,
  "service_ids": ["svc_notary", "svc_device_manage"],
  "skus": [
    {
      "condition": "new",
      "sku_name": "全新 标准版",
      "device_required": true,
      "enabled_billing_units": ["month", "day"]
    },
    {
      "condition": "used",
      "sku_name": "二手 标准版",
      "device_required": true,
      "enabled_billing_units": ["month", "day", "hour"]
    }
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
    "product_id": "prod_demo",
    "status": "draft",
    "sku_count": 2,
    "assistant_sync_enabled": true
  },
  "warnings": []
}
```

关键规则：

- 全新和二手是同一商品下的规格，不要求拆成两个商品。
- 是否同步办单助手由商品级开关控制，具体可用租赁单位由规格控制。
- 商家创建商品后需要提交运营审核，运营平台商品可复制给商家。

---

## 2. 复制商品给商家

请求：

```json
{
  "merchant_ids": ["merchant_demo_a", "merchant_demo_b"],
  "copy_scope": "product_sku_service",
  "assistant_sync_enabled": false
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "product_id": "prod_demo",
    "copied_count": 2,
    "skipped_count": 0
  },
  "warnings": []
}
```

关键规则：

- 复制后形成商家商品副本，商家可按自身经营情况增减和同步办单助手。
- 运营端商品列表需要支持按商家筛选，避免所有商家商品混在一起。

---

## 3. 发起合同和补充合同

请求：

```json
{
  "contract_type": "main",
  "template_id": "tpl_main_lease",
  "trigger_reason": "review_approved",
  "customer_todo_required": true
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "contract_id": "contract_demo",
    "contract_status": "signing",
    "customer_todo_id": "todo_contract_demo"
  },
  "warnings": []
}
```

关键规则：

- 审核通过、改价、补套餐、补充协议都可以触发合同待办。
- 客户在小程序订单详情完成签署，第三方回调只入库并进入 worker。

---

## 4. 发起公证

请求：

```json
{
  "notary_type": "lease_notary",
  "provider": "default_notary",
  "customer_todo_required": true
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "notary_case_id": "notary_demo",
    "notary_status": "processing",
    "customer_todo_id": "todo_notary_demo"
  },
  "warnings": []
}
```

关键规则：

- 公证由运营端或系统发起，客户侧只处理待办。
- 公证失败需要进入异常队列，不直接把订单主状态改乱。

---

## 5. 创建发货或交付任务

请求：

```json
{
  "shipper_type": "store",
  "delivery_type": "offline_handover",
  "receiver_name": "客户姓名占位",
  "receiver_mobile_masked": "138****0000",
  "receiver_address": "客户地址占位",
  "evidence_required": ["device_photo", "person_device_photo", "accessory_list"]
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "shipment_id": "shipment_demo",
    "ship_status": "pending_materials",
    "evidence_required": ["device_photo", "person_device_photo", "accessory_list"]
  },
  "warnings": []
}
```

关键规则：

- 默认门店发货，但链路配置可切换商家、平台、资方或线上物流。
- 交付照片和配件清单进入附件中心，并和订单、设备绑定。

---

## 6. 订单记录设备识别码

请求：

```json
{
  "device_identifier": "IMEI-SN-VIN-DEMO",
  "identifier_type": "SN",
  "record_reason": "delivery"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "device_identifier": "IMEI-SN-VIN-DEMO",
    "identifier_type": "SN",
    "order_id": "order_demo"
  },
  "warnings": []
}
```

关键规则：

- 商品只是展示和价格载体，真实短租和交付必须落到唯一设备码。
- 设备绑定后订单、库存、发货任务同时可追溯。

---

## 7. 归还验收

请求：

```json
{
  "inspection_result": "passed",
  "return_photos": ["file_return_demo"],
  "accessory_status": "complete",
  "damage_description": ""
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "inspection_id": "inspection_demo",
    "device_status": "in_stock",
    "order_status": "returned"
  },
  "warnings": []
}
```

关键规则：

- 验收通过设备回库，验收异常进入维修、争议或补扣流程。
- 归还验收要写设备库存日志和订单操作日志。

---

## 8. 发起退款

请求：

```json
{
  "refund_scope": "bill",
  "source_payment_id": "pay_demo",
  "amount": 10000,
  "reason": "customer_request",
  "remark": "退款原因占位"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "refund_id": "refund_demo",
    "refund_status": "pending_review",
    "amount": 10000
  },
  "warnings": []
}
```

关键规则：

- 退款必须关联原支付、账单和订单。
- 审核通过后才进入通道退款或钱包冲正，不能只改订单状态。

---

## 9. 发起冲正

请求：

```json
{
  "source_type": "allocation",
  "source_id": "alloc_demo",
  "amount": 10000,
  "reason": "allocation_error"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "reversal_id": "reversal_demo",
    "status": "created",
    "affected_wallet_entry_ids": ["wallet_entry_demo"]
  },
  "warnings": []
}
```

关键规则：

- 冲正要生成反向钱包流水，保留原流水，不覆盖历史。
- 冲正后对账中心必须能看到原单据和冲正单据。

---

## 10. 创建对账批次

请求：

```json
{
  "channel": "payment_provider_demo",
  "date_from": "2026-05-01",
  "date_to": "2026-05-01",
  "scope": "payment_refund_allocation"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "batch_id": "recon_demo",
    "status": "queued"
  },
  "warnings": []
}
```

关键规则：

- 对账要核对通道流水、业务流水、钱包流水。
- 金额不平进入财务异常队列，不能靠人工线下记账解决。

---

## 11. 创建渠道和推广码

创建渠道请求：

```json
{
  "channel_name": "渠道名称占位",
  "contact_name": "联系人占位",
  "commission_rule": {
    "rule_type": "fixed_or_ratio",
    "fixed_amount": 5000,
    "ratio": 0
  }
}
```

创建推广码响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "invite_code": "INVITE-DEMO",
    "landing_page": "https://example.invalid/store-settle?code=INVITE-DEMO"
  },
  "warnings": []
}
```

关键规则：

- 渠道只统计推广来的入驻商家，以及这些商家产生的联营订单和平台订单。
- 商家订单不计入渠道佣金。

---

## 12. 创建租后案件

请求：

```json
{
  "order_id": "order_demo",
  "case_type": "overdue",
  "overdue_days": 3,
  "source": "system"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "case_id": "post_rent_demo",
    "case_status": "open",
    "sync_status": "pending"
  },
  "warnings": []
}
```

关键规则：

- 租后先保留内部案件能力，后续可对接外部催收系统。
- 每个租后动作都要能回到原订单详情。

---

## 13. 创建客诉

请求：

```json
{
  "source": "alipay_complaint",
  "external_complaint_id": "complaint_demo",
  "order_id": "order_demo",
  "complaint_type": "service",
  "content": "投诉内容占位"
}
```

响应：

```json
{
  "code": "OK",
  "message": "success",
  "request_id": "req_demo",
  "data": {
    "complaint_id": "complaint_demo",
    "complaint_status": "pending_reply",
    "order_context_ready": true
  },
  "warnings": []
}
```

关键规则：

- 客诉必须带订单上下文、客户资料脱敏信息、订单状态、支付状态和历史处理记录。
- 回复投诉和关闭投诉都要写操作日志。
