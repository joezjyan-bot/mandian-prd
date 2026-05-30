# yzz 租赁系统 · 本轮改动记录(交接用)

> 本文件记录本轮"严格照产品文档对齐代码"的所有改动,供技术团队接手参考。
> 原则:① 产品文档(PRD/V0.2)是唯一业务依据;② 金额公式经文档示例校验;
>       ③ 尽量叠加式改动、降低耦合;④ 合规敏感(资方/风控)只留骨架,不预写实现。
> 所有金额一律以「分」整数运算。

---

## 一、已实现并验证(可运行,团队可直接用/微调)

### 模块 A:订单状态机
依据:`全局/02_状态字典与订单状态机` §0.1 / §0.2 / §5.1 / §6.7

| 文件 | 说明 |
|---|---|
| `app/Support/OrderStatus.php` | 26 个长租主状态(§0.1)、合法流转 TRANSITIONS(§5.1)、结算硬前置常量、C 端 8 Tab 映射(§0.2)。替换了之前自造的旧状态枚举。 |
| `app/Services/Order/DeliveryService.php` | 交付→签收确认→监管锁校验(上锁+激活锁)→平台结算→履约中。实现结算硬前置:未满足三条件不自动打款;无锁品类按第4章不卡;非法流转抛错。 |
| `app/Services/Order/EndOfTermService.php` | 到期三选一。申请购买完成置 EARLY_RETAINED(completeBuyout)、bill_type=purchase、留购 A 口径不重算;归还/续租不单设主状态(用申请记录,TODO 交对应模块)。 |
| `app/Models/Order.php` | customerFacingStatus 改用 §0.2 映射;补 need_lock/received_at/purchase_applied_at;C 端白名单 toCustomerArray 未变(红线)。 |
| `app/Services/External/Mock/MockDeviceLockService.php` | 演示模式 status() 返回 locked+active_lock,避免需锁订单监管锁校验卡死;真实校验由 Real 实现按中控台回调。 |
| migration `..._000009_add_lock_fields_to_yzz_orders` | yzz_orders 补 need_lock、received_at。 |
| migration `..._000010_add_purchase_applied_at_to_yzz_orders` | yzz_orders 补 purchase_applied_at。 |

### 模块 B:办单助手算价
依据:`门店手机端/办单助手/02_计算器字段与账单公式表` §1.2-§1.5 / §11 / §12

| 文件 | 说明 |
|---|---|
| `app/Services/Calculator/CalculatorService.php` | 按 §1.2 全公式重写:未付×费率倍数、月付摊在 periods-1、保证金=首付-首期租金、逐期账单(首期租金+末期名义留购费)、留购价逐期、rate_base 三口径、二维费率表查询。**经 §12 示例校验全部命中**(首期实付 2603、月付 1501.16、留购总价 10058.80)。 |
| `app/Models/CalculatorConfig.php` | 补 rate_base / rate_table / remaining_multiplier_bps / first_rent_cents / nominal_buyout_fee_cents。 |
| migration `..._000011_add_formula_fields_to_yzz_calculator_configs` | 补上述公式字段。 |

### 模块 C:账单生成
依据:办单助手02 §1.3 / §8;状态字典 §6.7

| 文件 | 说明 |
|---|---|
| `app/Services/Bill/BillPlanService.php` | 消费算价结果生成逐期账单:第1期 first=首期租金、2..N rent=monthly;增值服务按 charge_in 入账;首期实付不重复进账单。 |
| migration `..._000012_fix_yzz_bills_bill_type_enum` | bill_type 枚举对齐 §6.7(first/rent/service/notary/purchase/diff),迁移旧值。修复 'purchase' 被旧 enum 拒绝的隐患。 |
| `app/Services/Order/OrderService.php` | 按 §8 调整:下单不再建账单(只冻结快照),新增 generateBillPlan() 供审核通过后调用;首期支付改用报价快照 first_pay_cents。 |
| `app/Http/Controllers/Api/OrderController.php` | pay() 同步 payFirstBill 新签名(取快照首期实付)。 |

---

## 二、骨架 / 接口契约(待业务团队实现)

> 共同特点:方法体均为 `throw 未实现` 占位;每个方法注释写明"对应文档章节 + 该置什么状态 + 该校验什么 + TODO";
> 不直接耦合其它服务(由 Controller 编排);零数据库改动、不擅自建表/加字段;合规敏感部分(资方/风控)只留扩展位不实现。
> 团队接手时,这些文件本身就是按文档填充的 ToDo 清单。

### 模块 D:订单审核
依据:`运营端/订单管理/04_待审核与资方分配` §8 / §6 / §11 / §12;状态字典 §6.1 / §11.1

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Order/ReviewService.php` | **骨架** | 审核动作:claim(接单)/approveDataStage(资料审核)/passRiskControl(风控)/approve(通过)/reject(驳回)/requireSupplement(补资料)/escalate(转复核)。 |

ReviewService 关键设计:
1. 只负责审核状态流转 + 结论记录,**不直接调** OrderService / BillPlanService;通过后由 Controller 调 generateBillPlan。
2. 审核子状态建议用 review_sub_status 字段(§11.1),字段落库方式由团队决定,骨架未擅自建表。
3. **合规边界**:资金来源分配(§9)、风控评分算法(§9.3.1)依赖「资方管理/01、03」,骨架不实现,仅预留调用位。

### 模块 E:合同签署
依据:`运营端/合同公证/01_合同签署流程` §1-§8;状态字典 §6.3(合同状态)/ §6.4(公证状态)

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Contract/ContractService.php` | **骨架** | 合同动作:initiate(发起)/onCustomerSigned/onStoreSigned/platformSign(三方签署)/onAllSigned(完成)/initiateNotary(公证,可选)/handleTimeout(超时)/handleFailure(失败)。 |

ContractService 关键设计:
1. **合规约束(§2)**:合同三方=门店(甲/出租人)、客户(乙/承租人)、平台(丙/居间技术);**资方不出现,避免融资租赁定性**。
2. 合同模板按订单 contract_template_id 选择(§3),该字段审核阶段已锁定,本服务只读取使用,不决定资金来源。
3. 电子签走 EsignContract(mock/real 不感知);合同/公证子状态(§6.3/§6.4)字段落库方式由团队决定,骨架不建表。
4. 主状态推进(PENDING_SIGN → PENDING_FIRST_PAYMENT)与 OrderService::sign 现有逻辑择一,由 Controller 编排避免重复置位。

---

## 三、明确未做 / 留待对应模块(避免误以为已完成)

- **审核账单触发点自动挂接**:generateBillPlan 已就绪,但"审核通过后自动调用"需在 ReviewService 实现 + Controller 编排后才生效;当前可手动触发。
- **合同与签约的主状态推进衔接**:ContractService 骨架与 OrderService::sign 现有简化实现并存,正式接入时需二者择一推进 PENDING_SIGN→PENDING_FIRST_PAYMENT,避免重复。
- **资金来源分配(§9)/ 风控评分**:依赖资方管理文档,未实现。
- **归还申请表 / 续租申请表**:EndOfTermService 留 TODO,关联字段 return_request_id / renewal_request_id 未建表。
- **首期支付单独立实体**(办单助手§8):当前首期支付沿用支付流水,未单独建实体。
- **门店风控管控**(办单助手§3.3 merchant_order_control)、**联营内部快照**(§7.2)、**§11 默认费率表数据初始化 seeder**:未做,字段结构已留。
- **平台订单门店分配**(运营端07 PENDING_STORE_ASSIGN)、**逾期费用**(运营端11)、**运营端办单助手配置界面**(办单助手§3)、**公证服务对接**(合同公证02):未做。

---

## 四、给接手同学的提示

1. 改任何金额公式,先跑办单助手02 §12 的校验示例,确保和文档一致。
2. 状态流转一律走 `OrderStatus::canTransition`,不要写裸字符串状态。
3. C 端任何输出都走 `Order::toCustomerArray()` 白名单,严禁泄露合作模式/资方/风控/服务费拆分等内部字段。
4. 演示模式(EXTERNAL_MODE=mock)用 Mock* 桩;接真实中控台/支付/电子签时实现 Real* 并按 Contract 接口对接。
5. 本轮所有提交 message 都标注了对应文档章节,可对照 PRD 复核。
6. 骨架文件(ReviewService / ContractService)填充时,先读对应文档(运营端04 / 合同公证01),按方法注释里的"对应章节 + 状态 + 校验 + TODO"逐条实现;合规敏感处(资方/风控/合同三方)严格守注释里的边界。
