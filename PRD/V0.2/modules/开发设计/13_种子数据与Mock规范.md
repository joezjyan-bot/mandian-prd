# 种子数据与 Mock 规范

> 所属：开发设计 / 测试验收
> 目标：给开发、测试、Claude 和后续自动化用例提供统一的假数据规则。所有示例都必须是占位数据，不能混入真实手机号、身份证、订单号、通道密钥或客户资料。

---

## 1. 基本原则

1. 种子数据必须覆盖三类订单：门店订单、分红订单、平台订单。
2. 种子数据必须覆盖三类主体：平台、商家/门店、渠道。
3. 金额必须能算平：订单快照、账单、支付、分账、钱包、提现、退款、冲正、对账要能串起来。
4. 所有客户资料使用占位值，不使用真实手机号、身份证、地址。
5. 第三方通道使用 mock provider，不写真实第三方密钥、可信网络配置或数据库连接凭据。
6. Mock 回调必须能模拟成功、失败、重复、乱序、验签失败。

---

## 2. 命名规则

| 对象 | 编号规则 | 示例 |
|---|---|---|
| 商家 | `merchant_demo_{序号}` | `merchant_demo_001` |
| 门店 | `store_demo_{序号}` | `store_demo_001` |
| 员工 | `staff_demo_{序号}` | `staff_demo_001` |
| 渠道 | `channel_demo_{序号}` | `channel_demo_001` |
| 商品 | `prod_demo_{品类}_{序号}` | `prod_demo_ev_001` |
| 规格 | `sku_demo_{condition}_{序号}` | `sku_demo_new_001` |
| 设备 | `device_demo_{序号}` | `device_demo_001` |
| 报价 | `quote_demo_{序号}` | `quote_demo_001` |
| 订单 | `order_demo_{type}_{序号}` | `order_demo_store_001` |
| 账单 | `bill_demo_{序号}` | `bill_demo_001` |
| 支付 | `pay_demo_{序号}` | `pay_demo_001` |
| 分账 | `alloc_demo_{序号}` | `alloc_demo_001` |
| 钱包流水 | `wallet_entry_demo_{序号}` | `wallet_entry_demo_001` |
| 回调事件 | `callback_demo_{序号}` | `callback_demo_001` |

订单展示编号如需模拟，只使用 `DEMO-ORDER-001`，不使用生产样式长编号。

---

## 3. 主体种子数据

### 3.1 平台

| 字段 | 值 |
|---|---|
| `tenant_id` | `tenant_demo_main` |
| `platform_id` | `platform_demo_main` |
| `default_commission_rate` | `0.02` |
| `default_currency` | `CNY` |

### 3.2 商家和门店

| 字段 | 商家 A | 商家 B |
|---|---|---|
| `merchant_id` | `merchant_demo_001` | `merchant_demo_002` |
| `store_id` | `store_demo_001` | `store_demo_002` |
| `merchant_name` | 示例商家 A | 示例商家 B |
| `store_name` | 示例门店 A | 示例门店 B |
| `status` | active | active |
| `capabilities` | store/dividend/platform | store/platform |
| `receive_account_status` | verified | authorization_required |
| `esign_auth_status` | completed | pending |
| `channel_id` | `channel_demo_001` | 空 |

### 3.3 角色账号

| 账号 | 角色 | 数据范围 |
|---|---|---|
| `ops_admin_demo` | `ops_admin` | 全平台 |
| `review_staff_demo` | `review_staff` | 分配商家和订单 |
| `finance_staff_demo` | `finance_staff` | 财务数据 |
| `merchant_owner_demo` | `merchant_owner` | `merchant_demo_001` |
| `merchant_staff_demo` | `merchant_staff` | `store_demo_001` |
| `channel_user_demo` | `channel_user` | `channel_demo_001` |
| `customer_user_demo` | `customer_user` | 本人订单 |

---

## 4. 商品和设备种子数据

### 4.1 商品

| 字段 | 示例电动车 | 示例手机 |
|---|---|---|
| `product_id` | `prod_demo_ev_001` | `prod_demo_phone_001` |
| `category` | electric_vehicle | phone |
| `rent_modes` | long / short | long |
| `assistant_sync_enabled` | true | true |
| `service_ids` | `svc_notary`, `svc_device_manage` | `svc_notary` |
| `review_status` | approved | approved |

### 4.2 规格

| `sku_id` | 商品 | 成色 | 计费单位 | 是否需要设备 |
|---|---|---|---|---|
| `sku_demo_new_001` | `prod_demo_ev_001` | new | month/day | true |
| `sku_demo_used_001` | `prod_demo_ev_001` | used | month/day/hour | true |
| `sku_demo_new_002` | `prod_demo_phone_001` | new | month | false |
| `sku_demo_used_002` | `prod_demo_phone_001` | used | month | false |

### 4.3 设备

| `device_id` | 商品 | 设备码 | 状态 | 仓库 |
|---|---|---|---|---|
| `device_demo_001` | `prod_demo_ev_001` | `DEVICE-DEMO-001` | in_stock | `warehouse_store_demo` |
| `device_demo_002` | `prod_demo_ev_001` | `DEVICE-DEMO-002` | locked_for_order | `warehouse_store_demo` |
| `device_demo_003` | `prod_demo_ev_001` | `DEVICE-DEMO-003` | in_repair | `warehouse_store_demo` |

---

## 5. 三类订单种子数据

### 5.1 门店订单

| 字段 | 值 |
|---|---|
| `order_id` | `order_demo_store_001` |
| `order_type` | `store` |
| `merchant_id` | `merchant_demo_001` |
| `store_id` | `store_demo_001` |
| `review_owner` | merchant |
| `gross_amount` | 100000 |
| `platform_commission_rate` | 0.02 |
| `merchant_net_amount` | 98000 |
| `channel_commission` | 0 |

预期：商家自审，平台只抽佣，不分配资方，不生成渠道佣金。

### 5.2 分红订单

| 字段 | 值 |
|---|---|
| `order_id` | `order_demo_dividend_001` |
| `order_type` | `dividend` |
| `device_price` | 500000 |
| `funding_ratio` | 0.80 |
| `merchant_funding_amount` | 100000 |
| `funder_funding_amount` | 400000 |
| `merchant_share_ratio` | 0.20 |
| `funder_share_ratio` | 0.80 |
| `platform_commission_rate` | 0.02 |

预期：客户每笔还款先按 20/80 分给门店和资方，再分别扣平台抽佣。

### 5.3 平台订单

| 字段 | 值 |
|---|---|
| `order_id` | `order_demo_platform_001` |
| `order_type` | `platform` |
| `merchant_id` | `merchant_demo_001` |
| `review_owner` | platform |
| `funder_id` | `funder_demo_001` |
| `merchant_share_ratio` | 0 |
| `funder_share_ratio` | 1 |
| `platform_commission_rate` | 0.02 |

预期：运营审核和资方主控，商家端只能查看进度和联系客服。

---

## 6. 财务种子数据

### 6.1 账单计划

| `bill_id` | 订单 | 期数 | 类型 | 应付 |
|---|---|---|---|---|
| `bill_demo_001` | `order_demo_store_001` | 1 | first | 100000 |
| `bill_demo_002` | `order_demo_dividend_001` | 1 | first | 100000 |
| `bill_demo_003` | `order_demo_platform_001` | 1 | first | 100000 |

### 6.2 分账预期

| 订单 | 支付金额 | 门店毛额 | 资方毛额 | 平台抽佣 | 钱包入账 |
|---|---:|---:|---:|---:|---:|
| 门店订单 | 100000 | 100000 | 0 | 2000 | 门店 98000 |
| 分红订单 | 100000 | 20000 | 80000 | 门店 400 + 资方 1600 | 门店 19600 / 资方 78400 |
| 平台订单 | 100000 | 0 | 100000 | 2000 | 资方 98000 |

### 6.3 异常财务种子

| 场景 | 数据 |
|---|---|
| 分账金额不平 | 支付 100000，分账合计 99000 |
| 退款超过可退 | 可退 50000，申请 60000 |
| 提现余额不足 | 可用 10000，提现 20000 |
| 重复对账批次 | 同一通道、同一日期、同一范围重复创建 |

---

## 7. Mock Provider

| Provider | 用途 | 支持结果 |
|---|---|---|
| `mock_payment_provider` | 支付、部分支付、退款 | success / failed / duplicate / invalid_signature |
| `mock_payout_provider` | 提现打款 | success / failed / delayed |
| `mock_contract_provider` | 合同、补充合同 | signing / completed / failed / timeout |
| `mock_notary_provider` | 公证 | processing / completed / failed / timeout |
| `mock_risk_provider` | 风控、征信授权 | approved / manual_review / rejected / timeout |
| `mock_logistics_provider` | 物流签收 | shipped / signed / exception |
| `mock_complaint_provider` | 投诉同步 | pending_reply / replied / closed / unmatched |

Mock 回调字段必须包含：

- `mock_event_id`
- `provider`
- `business_type`
- `business_id`
- `event_status`
- `event_time`
- `signature_status`
- `payload`

---

## 8. 回调场景

| 场景 | 输入 | 预期 |
|---|---|---|
| 正常支付回调 | success + valid_signature | 账单核销，触发分账 |
| 验签失败 | success + invalid_signature | 只入异常日志，不推进状态 |
| 重复回调 | 相同 `mock_event_id` 两次 | 第二次标记重复 |
| 乱序回调 | completed 早于 signing | worker 按状态机处理，不倒退 |
| 超时回调 | 发起后超过配置时间 | 进入异常队列 |
| 失败后重试成功 | failed 后 success | 以最终有效状态推进，并保留历史 |

---

## 9. 测试数据隔离

1. 所有种子数据必须带 `tenant_id = tenant_demo_main`。
2. 所有 Mock 数据必须带 `is_mock = true`。
3. 测试环境禁止调用真实支付、合同、公证、短信、物流和投诉通道。
4. 导出测试文件必须使用脱敏数据。
5. 自动化测试跑完后可按 `tenant_id` 和 `is_mock` 清理。

---

## 10. 首批自动化建议

优先生成以下自动化用例数据：

1. `E2E-005` 门店订单完整链路。
2. `E2E-006` 分红订单完整链路。
3. `E2E-007` 平台订单完整链路。
4. `CNP-006` 支付回调验签失败。
5. `FIN-002` 分红订单分账。
6. `SEC-001` 店员访问钱包被拒绝。
7. `FUL-004` 交付照片缺失。
8. `MER-005` 收款账户非实名授权。
