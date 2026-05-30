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

### 模块 F:第三方回调统一处理
依据:`全局/07_订单接口事件与补偿动作` §1 / §2-§6 / §7 / §8 / §9

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Webhook/WebhookEventService.php` | **骨架** | 回调动作:record(入库)/verify(验签)/isDuplicate(幂等)/dispatch(分发到领域服务)/pushToExceptionQueue(异常队列)/replay(人工重放)。 |

WebhookEventService 关键设计:
1. 通用回调契约(§1):先入库→验签→幂等→处理;同一回调幂等;失败进异常队列可重试/重放。
2. 只做"回调收口 + 分发",业务委托给领域服务(支付→FinancePostingService、监管锁→DeliveryService::verifyLock、合同→ContractService、签收→DeliveryService::confirmReceipt),**不耦合业务细节**。
3. 不改现有 PaymentCallbackController / FinancePostingService(已实现支付回调幂等);接入时让支付回调也先经 record/verify/dedupe。
4. 人工补偿守 §8 边界:只允许重放回调,禁止人工改支付成功/分账成功/绕过实名/删日志。

### 模块 G:逾期费用
依据:`运营端/订单管理/11_逾期费用账单与规则配置`;C端12 §7

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Penalty/PenaltyService.php` | **骨架** | 逾期动作:dailyAmountCents(日金额计算)/dailyAccrual(每日累计)/stopAccrual(停止累计)/editAmount(改额)/reduce(减免)/waive(全免)/outstandingForSettlement(结清前必清检查)。 |

PenaltyService 关键设计:
1. 独立账单(order_penalty)与主账单两条线并行;全归平台不分账;规则快照不受后续改规则影响。
2. 减免不可超过原始金额;手动操作必填原因 + 留痕;提前结清/归还前必清(EndOfTermService 编排调用)。
3. **合规边界(§9.4)**:违约不加速全部未到期费用到期;设备灭失/拒还按设备确认价 + 已到期欠费 + 已生成逾期费用 + 可举证实际损失,不按剩余全部租金。
4. **计算精度口径待财务确认**:§4.3 比例示例存在"日金额先四舍五入再×天" vs "精确值×天再取整"的 2 分差异;骨架不擅自固化,dailyAmountCents 待财务定口径后实现。

### 模块 H:平台订单门店分配
依据:`运营端/订单管理/07_平台订单门店分配`;全局/02 §6.12

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Order/StoreAssignService.php` | **骨架** | 分配动作:assignableStores(候选门店)/assign(首次分配)/reassign(改派)/revoke(撤销)。 |

StoreAssignService 关键设计:
1. 仅平台订单使用;严格顺序(资金来源分配在前,§2.2);核心防"打款打错门店"=多维度匹配 + 工号二次确认 + 操作日志。
2. 分配字段(§3.1)+ order_store_assign_log(§3.2)由团队按文档建/补,骨架不擅自建表。
3. 改派权限分级(§9):合同发起前主管可改派,合同已签不可改派只能撤单重下。
4. C 端红线(§6.2):分配过程/商家主体/收款账户/资金来源/合同模板均不暴露,客户签约后只显示"提货门店:XXX"。
5. **状态口径冲突待确认**(见下方第五节)。

### 模块 I:交付签收照片证据
依据:`运营端/订单管理/02_交付签收照片证据` §2 / §4 / §5

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Delivery/DeliveryEvidenceService.php` | **骨架** | 证据动作:uploadEvidence(证据上传)/appendAnomalyNote(异常追加)/customerConfirm(客户确认)/requestFaceMatchAssist(人脸AI辅助核验)/manualVerify(人工核验)/recordReturnInspection(归还验收)。 |

DeliveryEvidenceService 关键设计:
1. 与 DeliveryService 互补,只管"证据链",不重复管订单状态流转(由 Controller 编排,避免双写)。
2. 业务规则:车辆类必填设备识别码才能交付;门店只能传自己/被授权订单照片;客户签收后交付前照片不可删;全程操作日志。
3. **人脸隐私红线**:AI 只辅助、不自动放行/拒绝(人工最终核验);不建可检索人脸库、只存结果/评分/流水号;C 端不出现"人脸比对/风控"字眼;失败/超时转人工;阈值待运营/算法确认,骨架不擅自设;不实现任何人脸特征提取/检索逻辑。

### 模块 J:订单关闭 / 退款 / 售后
依据:`运营端/订单管理/05_订单关闭退款与售后` §4 / §5 / §6 / §8 / §9

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Refund/OrderCloseRefundService.php` | **骨架** | 动作:closeOrder(关闭)/initiateRefund(发起退款)/simpleRefund(简单退款)/createRefundWorkflow(退款工单)/reverse(冲正)/requestEarlyReturn(提前归还)/recordInspectionAndFees(归还检测费用)。 |

OrderCloseRefundService 关键设计:
1. 高风险动作需权限 + 2FA + 原因 + 日志;涉钱必生成流水,不能只改状态(§2)。
2. 退款路径分流(§5.3):未分账→简单退款原路退;已分账→退款工单(从门店追回→退客户→内部清算,不可原路退)。
3. 冲正只生成反向流水(refund_deduction),**不删原流水**(§6);资方台账/授信按 refunded_amount 还原(资方部分骨架不实现)。
4. 留购 A 口径(剩余租金+保证金),二期折旧余值口径挂起;提前归还**不默认要求结清全部未到期费用**(合规边界,同逾期§9.4)。
5. 归还检测费用"以实际发生 + 凭证为准",每项费用须挂对应凭证;资方内部清算后台不暴露 C 端。

### 模块 K:改价 / 改套餐 / 补资料
依据:`运营端/订单管理/06_改价补资料与客服IM` §2 / §3 / §4 / §6 / §8;办单助手02 §9

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Order/OrderAdjustService.php` | **骨架** | 动作:adjustPlan(改套餐改价)/confirmAdjustedPlan(客户确认新方案)/requestSupplementMaterials(发起补资料)/buildImContext(构造客服IM上下文)。 |

OrderAdjustService 关键设计:
1. 改价**生成新价格快照不覆盖原始**(复用 CalculatorService + makeSnapshot,与账单/合同同源);原快照不可删。
2. 不绕过审核状态机;金额变化超阈值需主管复核;合同已签改价走补充合同/重审;已支付改价走退款/补款/冲正。
3. 补资料生成客户/商家待办(不能只写备注),进 PENDING_SUPPLEMENT(与 ReviewService::requireSupplement 二者择一编排)。
4. **隐私边界(§6)**:IM 上下文脱敏,**不含完整身份证/银行卡/详细风控报告**,不含资方/分账等 C 端红线字段。

### 模块 L:撤单 / 退款工单推进 / 补充合同
依据:`运营端/订单管理/10_订单撤单与补充合同` §1 / §2 / §3 / §4 / §6;财务管理/07 §6

| 文件 | 状态 | 说明 |
|---|---|---|
| `app/Services/Refund/CancelAndSupplementService.php` | **骨架** | 动作:requestCancel(撤单申请)/approveCancel(撤单审核)/advanceRefundWorkflow(退款工单推进)/initiateSupplementContract(发起补充合同)/reviewSupplementContract(审核)/onSupplementSigned(签署回调)。 |

CancelAndSupplementService 关键设计:
1. **与模块 J 分工**:J 管关闭退款 + 生成退款工单;本服务管撤单受理 + 退款工单逐步推进(确认门店转账→退客户→退资方→完成)+ 补充合同。两者通过 refund_workflow 衔接,不互相内部调用。
2. 撤单费用(§2.6/§2.7):待签约 1.5%、履约中 30%,**平台收**;退款额 = MAX(0, 已付 − 撤单费用 − 不可退)。
3. 退款路径(§2.1):未分账原路退;已分账走退款工单(门店线下转账追回→退客户→退资方),不原路退。
4. **补充合同**:适用设备编码/姓名/规格/资方/门店变更,**严禁改金额/月供/留购价**;与主合同同等效力、一单多份递增、永久保留。
5. 资方清算属后台,不在 C 端暴露;资方台账/额度还原依赖财务07/资方管理,骨架不实现。

---

## 三、明确未做 / 留待对应模块(避免误以为已完成)

- **审核账单触发点自动挂接**:generateBillPlan 已就绪,但"审核通过后自动调用"需在 ReviewService 实现 + Controller 编排后才生效;当前可手动触发。
- **合同与签约的主状态推进衔接**:ContractService 骨架与 OrderService::sign 现有简化实现并存,正式接入时需二者择一推进 PENDING_SIGN→PENDING_FIRST_PAYMENT,避免重复。
- **资金来源分配(运营端04 §9)/ 风控评分**:依赖资方管理文档(01/03/04),未实现。
- **归还申请表 / 续租申请表**:EndOfTermService 留 TODO,关联字段 return_request_id / renewal_request_id 未建表。
- **首期支付单独立实体**(办单助手§8):当前首期支付沿用支付流水,未单独建实体。
- **门店风控管控**(办单助手§3.3 merchant_order_control)、**联营内部快照**(§7.2)、**§11 默认费率表数据初始化 seeder**:未做,字段结构已留。
- **运营端办单助手配置界面**(办单助手§3)、**公证服务对接**(合同公证02)、**财务对账/结算穿透**(财务管理07/08)、**资方管理全套**(资方01/03/04):未做。
- **各骨架的落库表**:webhook_event、异常队列、order_penalty(及减免日志、规则配置)、order_store_assign_log、交付证据表(含 AI 核验字段)、refund_application、refund_workflow、early_return_request、return_inspection_report、return_fee_item、purchase_requests、order_cancel_request、supplement_contract、价格快照新版本等,均由团队按对应文档建,骨架未擅自建表。

---

## 四、给接手同学的提示

1. 改任何金额公式,先跑办单助手02 §12 的校验示例,确保和文档一致。
2. 状态流转一律走 `OrderStatus::canTransition`,不要写裸字符串状态。
3. C 端任何输出都走 `Order::toCustomerArray()` 白名单,严禁泄露合作模式/资方/风控/服务费拆分等内部字段。
4. 演示模式(EXTERNAL_MODE=mock)用 Mock* 桩;接真实中控台/支付/电子签时实现 Real* 并按 Contract 接口对接。
5. 本轮所有提交 message 都标注了对应文档章节,可对照 PRD 复核。
6. 骨架文件填充时,先读对应文档,按方法注释里的"对应章节 + 状态 + 校验 + TODO"逐条实现;合规敏感处(资方/风控/合同三方/逾期赔偿口径/人脸隐私)严格守注释里的边界。
7. 退款相关有两个服务(OrderCloseRefundService 模块J、CancelAndSupplementService 模块L),分工见模块L关键设计1,通过 refund_workflow 表衔接,不互相内部调用,实现时由 Controller/财务流程编排。

---

## 五、需 Hudson / 业务团队确认的口径(开发前要定)

1. **门店分配状态口径冲突**:运营端07 §2 要新增主状态 `PENDING_STORE_ASSIGN`;但全局/02 §0.1(更新于07之后)未收录,§11.5 明确"门店分配不新增主状态,保持 PENDING_REVIEW 内部子状态"。
   → 建议以 §0.1 收敛口径为准(不单设主状态)。StoreAssignService 骨架未写死状态置位,待确认。
2. **逾期费用计算精度口径**:见模块 G,§4.3 比例算法存在 2 分差异,需与财务对齐"先取整再×天"还是"精确×天再取整"。
3. **留购价二期残值口径**:当前 A 口径(剩余租金+保证金)已实现;二期残值口径(C端12 §4.2 注)挂起,待法务/合规复核后切换。
4. **AI 人脸核验阈值**(运营端02 §5.1):high_match / suspect_mismatch 阈值待运营/算法确认。
5. **退款工单 SLA**(运营端05 §12 / 运营端10 §2.9):门店线下转账 3 天还是 7 天。
