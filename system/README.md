# 满点租赁系统 · 后端脚手架(Laravel)

> 本目录是 **可演示 + 可接手** 的后端脚手架,不是上线成品。
> 目标:让整套系统能跑起来演示(签约/支付/监管锁先走**模拟模式**),团队在此基础上替换为真实对接并补全业务。
> 对应产品文档:`docs/满点租赁系统_完整产品文档_V0.2.2.html`、`PRD/V0.2/`。

---

## 一、这套脚手架的设计原则(先读这个)

1. **模拟模式开关(最重要)**:所有外部对接(电子签、支付、监管锁、实名、银行赊销)都抽象成接口(`app/Services/External/Contracts`),有两套实现:
   - `Mock\*`:模拟实现,返回"成功",用于演示和联调前期。
   - `Real\*`:真实实现,**目前是空壳 + TODO**,团队拿真实通道密钥来填。
   - 用 `.env` 的 `EXTERNAL_MODE=mock|real` 一键切换(见 `config/external.php`)。**业务代码只依赖接口,不关心走哪套**——这是"后期直接换对接"能实现的关键。

2. **金额一律用整数"分"**:数据库金额字段全部 `bigint`,单位为分,禁止浮点。算价以分为单位整数运算(与办单助手 `phone-rent` 蓝本一致)。

3. **四账分离**:`orders`(订单业务账)、`payment_flows`(支付流水账)、`wallet_entries`(钱包账)、`ledger_entries`(总账分录)分别建表,互相关联但不混表。资金动作要求可追溯、可审计、可回滚、可幂等。

4. **合作模式贯穿**:订单上带 `cooperation_mode`(self_operate 商家 / joint_venture 联营 / receivables_assignment 平台),分账、权限、审核差异都由它驱动。

5. **C 端红线**:返回给 C 端的 DTO 必须走白名单,严禁泄露资方、合作模式、服务费拆分、利润、风控结论、黑灰名单。见各 Controller 注释。

6. **申请购买价 A 口径可切换**:当前 = 剩余应付租金 + 保证金(`config/business.php` 的 `buyout_formula=A`)。⚠️ 合规敏感、待法务确认;二期切折旧余值口径时只改这一处策略类,不动业务流程。

---

## 二、团队接手前必读

详见仓库根目录 `docs/技术团队接手清单.md`。开工前确认:框架(已定 Laravel)、数据库选型、中控台(Java)HTTP/Webhook 契约、模拟模式约定。

---

## 三、目录结构

```
system/
├── README.md                      ← 本文件
├── composer.json                  ← 依赖(Laravel 11)
├── .env.example                   ← 环境变量样例(含 EXTERNAL_MODE、MOCK 开关)
├── config/
│   ├── external.php               ← 模拟/真实 对接开关与各通道配置
│   └── business.php               ← 业务口径(服务费率、逾期费、最低服务期、购买价口径)
├── routes/
│   └── api.php                    ← 接口路由(C端/商家端/运营端分组)
├── app/
│   ├── Models/                    ← Eloquent 模型(订单/账单/支付/钱包/总账/设备/合同…)
│   ├── Http/Controllers/Api/      ← 控制器(下单、支付回调、到期处理…)
│   ├── Services/
│   │   ├── External/
│   │   │   ├── Contracts/         ← 外部对接接口(签约/支付/监管锁/实名)
│   │   │   ├── Mock/              ← 模拟实现(演示用,返回成功)
│   │   │   └── Real/              ← 真实实现(空壳+TODO,团队填)
│   │   ├── Order/                 ← 下单、算价、状态机、到期三选一
│   │   └── Finance/              ← 四账记账、分账、购买价计算(A口径策略)
└── database/
    ├── migrations/                ← 建表(四账分离 schema)
    └── seeders/                   ← 演示数据
```

## 四、怎么跑起来(团队操作)

```bash
cd system
composer install
cp .env.example .env
php artisan key:generate
# 配置 .env 的数据库连接,确认 EXTERNAL_MODE=mock
php artisan migrate --seed
php artisan serve
# 访问 http://localhost:8000/api/health 应返回 {"status":"ok","external_mode":"mock"}
```

演示阶段保持 `EXTERNAL_MODE=mock`:下单→签约→支付→交付签收→到期三选一 全流程可走通,签约/支付/锁都返回模拟成功。

## 五、接手 TODO 的找法

全仓搜 `// TODO[团队]` 会列出所有需要团队补全/对接的地方,按模块分布。重点:
- `app/Services/External/Real/*` —— 真实通道对接
- `app/Services/Finance/*` —— 资金核对、对账、幂等边界的真实校验
- 各 Controller 里标 `C端红线` 的 DTO 白名单需逐字段复核

---

⚠️ **再次提醒**:本脚手架可演示、结构可接手,但**碰钱、碰合规、碰真实通道的部分必须由团队联调+测试+灰度后才能上线**。申请购买价 A 口径为合规敏感项,以法务终审为准。
