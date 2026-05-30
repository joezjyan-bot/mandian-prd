# 满点租赁系统 · Laravel 后端脚手架(演示/可接手版）

> 本目录是给技术团队的**起点脚手架**，不是上线成品。
> 框架：**Laravel 11**（PHP 8.2+）。代码前缀沿用 `yzz`。
> 中控台（Java）通过 HTTP API + Webhook 对接，PHP 侧用 Guzzle 调用，无 SDK。

---

## 这套脚手架的核心设计：模拟模式（MOCK MODE）

产品阶段的目标是**先把整套系统的流程跑通、能演示**，外部对接（电子签、支付、监管锁、实名）先用“假接口”返回成功，后期再换真接口。

实现方式：**所有外部依赖都抽象成接口（Contract），有两套实现：**

| 接口 | Mock 实现（演示用） | Real 实现（团队对接） |
|---|---|---|
| `EsignContract` 电子签 | `MockEsignService` 直接返回签署成功 | `RealEsignService` ← TODO 团队接真实电子签 |
| `PaymentContract` 支付 | `MockPaymentService` 直接返回支付成功 | `RealPaymentService` ← TODO 团队接真实支付通道 |
| `DeviceLockContract` 监管锁 | `MockDeviceLockService` 返回上锁成功 | `RealDeviceLockService` ← TODO 团队接中控台 |
| `IdVerifyContract` 实名 | `MockIdVerifyService` 返回认证通过 | `RealIdVerifyService` ← TODO 团队接实名通道 |

**切换开关**：`.env` 里的 `EXTERNAL_MODE`
- `EXTERNAL_MODE=mock` → 演示模式，全部走 Mock
- `EXTERNAL_MODE=real` → 生产模式，全部走 Real（团队填完真实对接后）

绑定逻辑见 `app/Providers/ExternalServiceProvider.php`。**业务代码只依赖接口、不感知是 mock 还是 real**——这就是“后期直接换对接、不动业务代码”的关键。

---

## 怎么跑起来

```bash
cd system
composer install
cp .env.example .env
php artisan key:generate
# 配置好数据库连接后：
php artisan migrate
php artisan serve
# 健康检查：
curl http://127.0.0.1:8000/api/health
```

---

## 目录结构

```
system/
├── README.md                    # 本文件
├── composer.json                # Laravel 11 依赖
├── .env.example                 # 含 EXTERNAL_MODE 开关
├── config/
│   ├── external.php             # 模拟模式配置
│   └── business.php             # 业务参数（服务费率/逾期/购买价口径等）
├── routes/
│   └── api.php                  # 接口路由
├── database/migrations/         # 四账分离 + 核心表
├── app/
│   ├── Contracts/               # 外部依赖接口（4个）
│   ├── Services/
│   │   ├── External/Mock/       # 模拟实现（演示用）
│   │   ├── External/Real/       # 真实实现空壳（团队填）
│   │   ├── Finance/             # 四账记账 + 购买价计算
│   │   └── Order/               # 下单/签约/支付编排
│   ├── Models/                  # 订单/账单/流水/钱包/总账
│   ├── Http/Controllers/Api/    # 控制器
│   └── Providers/               # 模拟模式绑定
```

---

## 给团队的重要提醒（务必先读 `docs/技术团队接手清单.md`）

1. **金额一律整数“分”**：所有金额字段 bigint，存“分”，禁止浮点。
2. **资金回调必须幂等**：支付/分账/提现回调用唯一事件号去重，重复通知不重复入账。
3. **申请购买价 A 口径**（剩余租金+保证金）是**合规敏感项**，已做成可切换（见 `config/business.php` 的 `buyout_formula`），二期要换折旧余值口径，别写死。
4. **C 端红线**：客户端绝不能看到资方、合作模式、服务费拆分、利润、风控结论等。`Order::toCustomerArray()` 是白名单出口。
5. **四账分离**：订单业务账、支付流水账、钱包账、总账分录分表，互相关联但不能混表。
6. 本脚手架能跑通**演示流程**，但**上线需团队接真实对接 + 测试 + 资金对账 + 灰度**。
