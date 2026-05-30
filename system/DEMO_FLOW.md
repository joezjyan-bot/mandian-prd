# yzz 系统 · 可演示链路调用清单(DEMO FLOW)

> 目的:把"已实现并验证"的主链路串成一条可照着跑的 API 调用序列,供团队本地 `php artisan serve` 后用 curl/Postman 验证链路连通。
> 范围:仅覆盖**已实现**的环节(算价→下单→签约→首期支付→交付→签收结算→到期申请购买)。
> 审核(ReviewService)、合同(ContractService)等**骨架服务尚未接 Controller**,本清单暂不含,待团队实现骨架后补。
> 金额单位:分(cents)。演示模式需 `.env` 设 `EXTERNAL_MODE=mock`(支付/电子签/监管锁走 Mock 桩)。

---

## 0. 准备

```bash
cd system
composer install
cp .env.example .env   # 设 EXTERNAL_MODE=mock,配置 DB
php artisan key:generate
php artisan migrate     # 跑全部 migration(含本轮补的字段/枚举修正)
php artisan db:seed     # 若有 seeder(办单助手默认费率表 seeder 待团队补,见 CHANGELOG)
php artisan serve       # 默认 http://127.0.0.1:8000
```

> ⚠️ 办单助手 config(费率表等)目前**无默认 seeder**(CHANGELOG 已列为未做项)。
> 演示算价前,需先手动插一条 yzz_calculator_configs 记录(带 rate_table / first_rent_cents 等),
> 或团队补 seeder。下方算价步骤假设已存在 config_id=1。

---

## 1. 健康检查

```bash
curl http://127.0.0.1:8000/api/health
```

## 2. 办单助手试算(不落库)

依据 办单助手02 §1.2;校验值见 §12 示例(iPhone 8510 元 / 首付30% / 6期 / 管理费50)。

```bash
curl -X POST http://127.0.0.1:8000/api/calculator/quote \
  -H "Content-Type: application/json" \
  -d '{
    "config_id": 1,
    "device_value_cents": 851000,
    "periods": 6,
    "down_payment_ratio": 30,
    "selected_services": ["device_mgmt_fee"]
  }'
```

预期(与文档 §12 一致):first_pay_cents=260300、period_rent_cents=150116、
buyout_total_cents=1005880、bill_by_period[1]=1000、buyout_by_period[6]=254400。

## 3. 生成报价快照(下单二维码)

```bash
curl -X POST http://127.0.0.1:8000/api/calculator/snapshot \
  -H "Content-Type: application/json" \
  -d '{
    "config_id": 1, "device_value_cents": 851000, "periods": 6,
    "down_payment_ratio": 30, "selected_services": ["device_mgmt_fee"], "store_id": 1
  }'
```

返回 snapshot_no、order_qr_url、quote。记下快照(下单时用 price_snapshot_id 关联)。

## 4. 下单(冻结快照,进入 PENDING_CUSTOMER_SUBMIT)

依据 办单助手02 §8:下单只创建订单冻结快照,不建账单。

```bash
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1, "merchant_id": 1, "store_id": 1,
    "cooperation_mode": "self_operate", "product_name": "iPhone 17 Pro Max 256G",
    "device_value_cents": 851000, "deposit_cents": 254300,
    "periods": 6, "period_rent_cents": 150116,
    "total_amount_cents": 1005880, "price_snapshot_id": 1
  }'
```

返回 order(status=PENDING_CUSTOMER_SUBMIT)。记下 order id。

> ⚠️ 真实流程此处后续是 审核(PENDING_REVIEW)→ 签约。审核为骨架未接,
> 演示时如需继续,可由团队在实现 ReviewService 后接入;
> 当前 sign 端点直接演示签约动作(见下),用于打通后段链路。

## 5. 签约(PENDING_SIGN → PENDING_FIRST_PAYMENT)

> 注:正式应在审核通过(PENDING_REVIEW→PENDING_SIGN)后签约。
> OrderService::sign 当前为简化实现,演示直接调用。

```bash
curl -X POST http://127.0.0.1:8000/api/orders/1/sign
```

## 6. 首期支付(PENDING_FIRST_PAYMENT → PENDING_DELIVERY)

首期实付取报价快照 first_pay_cents(§8);走 Mock 支付 + 四账记账(幂等)。

```bash
curl -X POST http://127.0.0.1:8000/api/orders/1/pay
```

返回 payment + posted=true(已入账)。订单进入 PENDING_DELIVERY。

## 7. 商家交付(PENDING_DELIVERY → PENDING_RECEIPT_CONFIRM)

need_lock=true 走监管锁上锁(Mock 桩)。手机类填 true,无锁品类填 false。

```bash
curl -X POST http://127.0.0.1:8000/api/orders/1/deliver \
  -H "Content-Type: application/json" \
  -d '{ "device_code": "IMEI356789012345678", "need_lock": true }'
```

## 8. 签收 → 监管锁校验 → 结算(一键演示)

依据 全局/02 §5.1 结算硬前置:签收 + 监管锁上锁 + 激活锁,三者满足才结算。
Mock 监管锁桩 status() 返回 locked,演示可顺利通过;真实由中控台回调驱动。

```bash
curl -X POST http://127.0.0.1:8000/api/orders/1/sign-for
```

返回 settled=true、order.status=IN_FULFILLMENT(履约中)。

> 分步驱动(真实/调试用):
> `POST /orders/1/confirm-receipt` → `POST /orders/1/verify-lock` → `POST /orders/1/settle`
> 若监管锁校验未过,verify-lock 后停在 LOCK_VERIFY_FAILED,settle 会拒绝(不自动打款)。

## 9. 查看订单详情(C 端白名单)

```bash
curl http://127.0.0.1:8000/api/orders/1
```

只返回 C 端可见字段(订单号/商品/金额/客户向状态),
不含 cooperation_mode/资方/服务费拆分等内部字段(红线)。

## 10. 到期三选一(履约中可操作)

```bash
# 申请购买(A 口径:剩余未付租金 + 保证金)
curl -X POST http://127.0.0.1:8000/api/orders/1/buyout/quote   # 试算
curl -X POST http://127.0.0.1:8000/api/orders/1/buyout/apply   # 发起申请(生成 purchase 账单)

# 续租(追加账单)
curl -X POST http://127.0.0.1:8000/api/orders/1/renew \
  -H "Content-Type: application/json" -d '{ "extra_periods": 3 }'

# 归还(校验前置,登记入口;归还验收落库属对应模块)
curl -X POST http://127.0.0.1:8000/api/orders/1/return
```

## 11. 支付回调(幂等验证)

同一 callback_event_id 重复发只入账一次(posted 第二次为 false)。

```bash
curl -X POST http://127.0.0.1:8000/api/callbacks/payment \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 1, "merchant_id": 1, "amount_cents": 150116,
    "channel_trade_no": "TRX20260530001", "callback_event_id": "EVT0001"
  }'
# 再发一次同样的 → 返回 posted=false(幂等,不重复入账)
```

---

## 链路状态流转速查(已实现段)

```
PENDING_CUSTOMER_SUBMIT (下单)
   → [审核 骨架未接] →
PENDING_SIGN (签约前置)
   → sign → PENDING_FIRST_PAYMENT
   → pay  → PENDING_DELIVERY
   → deliver → PENDING_RECEIPT_CONFIRM
   → confirm-receipt → PENDING_LOCK_VERIFY
   → verify-lock → PENDING_PLATFORM_SETTLEMENT(或 LOCK_VERIFY_FAILED)
   → settle → IN_FULFILLMENT(履约中)
   → buyout/apply + 支付成功 → EARLY_RETAINED(申请购买完成)
```

> 注:本清单只覆盖已实现并可演示的环节。审核、合同、退款、逾期、门店分配、交付证据、
> 改价补资料、撤单补充合同均为骨架(见 CHANGELOG_yzz.md 第二节),实现后再补入本清单。
