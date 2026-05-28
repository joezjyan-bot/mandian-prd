# CODEX V0.2.1 Cleanup Report

> 生成时间：2026-05-27
> 范围：目录重号归档、OpenAPI V0.2.1 复核、根目录历史文档归档。
> 原则：不删除历史文档；通过顶部归档标识降低误读风险；接口只按 V0.2.1 口径补约束和示例。

---

## 1. 目录重号归档批次

### 1.1 运营端 / 订单管理

采用顶部归档标识方式处理，未改文件名。

| 重号 | 归档文件 | 当前主文档 |
|---|---|---|
| 02 | `02_状态字典与订单状态机.md` | `modules/全局/02_状态字典与订单状态机.md` |
| 03 | `03_订单关闭与系统费用.md` | `05_订单关闭退款与售后.md`、`10_订单撤单与补充合同.md` |
| 04 | `04_订单价格调整.md` | `04_待审核与资方分配.md`、`06_改价补资料与客服IM.md` |
| 05 | `05_订单取消重下流程.md` | `10_订单撤单与补充合同.md`、`05_订单关闭退款与售后.md` |
| 06 | `06_修改还款日.md` | `08_长租订单全生命周期与客服操作.md`、`09_C端订单状态与账单支付.md` |

保留为当前主文档的重号文件：

- `02_交付签收照片证据.md`
- `03_订单详情.md`
- `04_待审核与资方分配.md`
- `05_订单关闭退款与售后.md`
- `06_改价补资料与客服IM.md`

### 1.2 运营端 / 财务管理

采用顶部归档标识方式处理，未改文件名。

| 重号 | 归档文件 | 当前主文档 |
|---|---|---|
| 02 | `02_钱包充值与提现.md` | `02_钱包分账提现对账.md` |
| 03 | `03_退款单与责任划分.md` | `03_退款冲正与打款通道.md`、`07_门店结算账户与资金穿透架构.md`、`订单管理/10_订单撤单与补充合同.md` |
| 04 | `04_后台余额变动.md` | `03_退款冲正与打款通道.md`、`04_对账中心与通道流水.md`、`06_财务流水模型与对账规则.md` |
| 05 | `05_门店欠款列表.md` | `02_钱包分账提现对账.md`、`07_门店结算账户与资金穿透架构.md` |
| 06 | `06_对账与汇总.md` | `04_对账中心与通道流水.md`、`06_财务流水模型与对账规则.md` |

保留为当前主文档的重号文件：

- `02_钱包分账提现对账.md`
- `03_退款冲正与打款通道.md`
- `04_对账中心与通道流水.md`
- `05_财务配置与结算账期.md`
- `06_财务流水模型与对账规则.md`

### 1.3 运营端 / 配置管理

采用顶部归档标识方式处理，未改文件名。

| 重号 | 归档文件 | 当前主文档 |
|---|---|---|
| 04 | `04_商户抽佣配置.md` | `财务管理/05_财务配置与结算账期.md`、`02_链路配置中心.md` |
| 05 | `05_合同模板管理.md` | `05_合同公证与授权配置.md` |
| 06 | `06_电子签通道配置.md` | `05_合同公证与授权配置.md`、`07_通道接口配置与回调日志.md` |

保留为当前主文档的重号文件：

- `04_增值服务配置.md`
- `05_合同公证与授权配置.md`
- `06_审核策略与风控规则.md`

---

## 2. OpenAPI V0.2.1 复核

文件：`PRD/V0.2/openapi/core-api.v0.2.yaml`

### 2.1 提现接口

已修订：

- `info.version` 更新为 `0.2.1`。
- `CreateWithdrawalRequest.amount` 增加：
  - `minimum: 50000`
  - `maximum: 2000000`
  - 说明为 amount in cents，对应普通提现单笔 500-20000 元。
- `/wallets/{wallet_id}/withdrawals` 增加错误码示例：
  - `WITHDRAW_AMOUNT_BELOW_MIN`
  - `WITHDRAW_AMOUNT_ABOVE_MAX`
  - `WITHDRAW_DAILY_LIMIT_EXCEEDED`
  - `WITHDRAW_MANUAL_REVIEW_REQUIRED`
- 每日最多 3 次写入接口说明和 409 业务规则拦截示例。

### 2.2 C 端返回字段

已修订：

- `OrderCreated` 移除 C 端返回的 `order_type`。
- `OrderCreated` 新增 `product_line`：
  - `long_rent`
  - `experience_rent`
- 该字段仅表达 C 端业务入口，不返回内部审核或资金配置字段。

保留说明：

- `AssignFunderRequest`、`FunderAssignmentResult`、`FundingPreview` 等仍属于运营端/办单助手/内部审核链路，不作为 C 端响应字段。
- 底层英文枚举和内部接口路径保持兼容，未强行改名。

### 2.3 签收确认字段

已修订：

- `CreateShipmentRequest` 新增 `receipt_confirmation`。
- 新增 `ReceiptConfirmationConfig`：
  - `method`: `electronic_receipt` / `ai_qa`
  - `electronic_template_id`
  - `ai_question_set_id`
  - `fallback_method`
  - `ai_questions`
- 新增 `AcceptanceQuestion`：
  - `question_no`
  - `question_text`
  - `expected_answer_type`
  - `abnormal_answer_action`
- 新增 `CustomerReceiptTask`：
  - `task_id`
  - `method`
  - `task_status`
  - `customer_action_url`
- `ShipmentResult` 增加 `customer_receipt_task`，用于返回客户待办任务。

---

## 3. 根目录历史文档归档

采用顶部 `[历史归档]` 标识方式处理，未移动文件。

| 文件 | 归档原因 | 当前入口 |
|---|---|---|
| `00_完整产品需求文档.md` | 已被 V0.2.1 开发冻结总 PRD 替代 | `00_V0.2.1_开发冻结版总PRD.md` |
| `01_重构决策基线.md` | 早期决策背景 | `00_核心决策表.md`、`README.md` |
| `05_需求一致性检查报告.md` | 早期一致性检查 | `CODEX_V0.2.1_FREEZE_FIXUP_REPORT.md`、`modules/测试验收/V1_端到端验收矩阵.md` |
| `06_Claude阅读检查与开发思路.md` | 早期 Claude 阅读话术 | `README.md`、`00_V0.2.1_开发冻结版总PRD.md` |
| `09_Claude开发交接包.md` | 早期交接包 | `README.md`、`00_V0.2.1_开发冻结版总PRD.md` |
| `_待办_IM客服体系.md` | 已被 IM 客服主文档承接 | `modules/运营端/客服中心/00_IM客服体系总览.md`、`modules/全局/12_账号身份与跨端统一.md` |

---

## 4. 验证结果

- `ruby -e 'require "yaml"; YAML.load_file(...)'`：OpenAPI YAML 解析通过。
- `git diff --check`：无空白或补丁格式问题。
- `PRD/.DS_Store`：仍为未跟踪文件，未纳入提交。

---

## 5. 结论

V0.2.1 收尾批次已完成：

1. 三个运营端目录的重号旧文件已加归档入口。
2. OpenAPI 已补齐提现规则、C 端返回字段边界、签收确认配置。
3. 根目录早期文档已统一标记为历史归档。
4. 当前开发阅读入口继续以 `README.md`、`00_V0.2.1_开发冻结版总PRD.md`、`00_核心决策表.md` 为准。
