# 满点租赁系统 · UI 原型

> ⚠️ **本目录属于「满点租赁系统重构」项目（mandian-prd / 即将落地 mandian-rental PHP 项目）**。
>
> **不是中控台**。中控台是另一个独立项目，仓库在 [`mandian-zhongkong`](https://github.com/joezjyan-bot/mandian-zhongkong)，它只做"客户调用第三方接口的统一入口 + 计费引擎 + 运营管理后台"，**不包含分配资方 / 分红订单 / 留购 / 催收**等租赁业务概念。

本目录存放 PRD V0.2 配套的可视化 UI 原型，作为飞书 PRD 的补充材料。

> **定位**：高保真原型，不是最终设计稿。让 PRD 评审能直接看到"列表即工作台"等设计理念落到屏幕上是什么样，方便：
> - PM ↔ 研发对齐字段口径
> - 研发评估实现难度
> - UI 设计师在此之上做最终视觉打磨

---

## 两个项目的边界（防止混淆）

| 项目 | 仓库 | 业务范围 | 用户 |
|---|---|---|---|
| **满点租赁系统重构** | `mandian-prd`（本仓库）+ 未来的 `mandian-rental` | 手机/电车租赁全流程：商品 / 订单 / 资方 / 合同 / 分期 / 催收 / 留购 / 分账 | C 端租客 / 商家 / 资方 / 满点运营 |
| **满点中控台** | `mandian-zhongkong` | 第三方接口聚合 + 客户接入 + 计费 / 限流 / Webhook + 员工后台查询 | B 端付费客户 / 满点员工 |

**两个项目都属于 Hudson，但用户、场景、数据模型完全不同**：

- 租赁系统里的"客户"= C 端租手机的小王同学
- 中控台里的"客户"= B 端来调中控台 API 的某科技公司
- 租赁系统的"订单"= 一笔租机合同（DZ20260524000183）
- 中控台的"调用日志"= 一次 API 调用（LOG-20260523-000001）

本目录的原型**只针对租赁系统**。中控台后台的 UI 在 `mandian-zhongkong/frontend/src/views/` 已经用 Vue 3 实现了 Stage 1+2，不再单独画原型。

---

## 当前原型清单

| # | 文件 | 配套 PRD 章节 | 状态 | 在线预览 |
|---|---|---|---|---|
| 1 | [`operations-order-list.html`](operations-order-list.html) | [V0.2 §7 订单列表 UI 规则](../PRD/V0.2/04_UI%E9%A3%8E%E6%A0%BC%E4%B8%8E%E9%A1%B5%E9%9D%A2%E5%8E%9F%E5%9E%8B%E8%A7%84%E8%8C%83.md) | ✅ 已交付 | [打开 →](https://htmlpreview.github.io/?https://github.com/joezjyan-bot/mandian-prd/blob/main/prototypes/operations-order-list.html) |
| 2 | [`operations-order-detail.html`](operations-order-detail.html) | [V0.2 §7.3 详情页边界](../PRD/V0.2/04_UI%E9%A3%8E%E6%A0%BC%E4%B8%8E%E9%A1%B5%E9%9D%A2%E5%8E%9F%E5%9E%8B%E8%A7%84%E8%8C%83.md) | ✅ 已交付 | [打开 →](https://htmlpreview.github.io/?https://github.com/joezjyan-bot/mandian-prd/blob/main/prototypes/operations-order-detail.html) |
| 3 | [`operations-workbench-home.html`](operations-workbench-home.html) | [V0.2 §5.1 运营端首页](../PRD/V0.2/04_UI%E9%A3%8E%E6%A0%BC%E4%B8%8E%E9%A1%B5%E9%9D%A2%E5%8E%9F%E5%9E%8B%E8%A7%84%E8%8C%83.md) | ✅ 已交付 | [打开 →](https://htmlpreview.github.io/?https://github.com/joezjyan-bot/mandian-prd/blob/main/prototypes/operations-workbench-home.html) |

> 📌 **数据连贯性**：原型 #1 #2 #3 共享同一笔订单 `DZ20260524000183`（小王同学 · iPhone 15 Pro Max · 待审核 + 风控触发），方便对照工作台 → 列表 → 详情整个工作流。

---

## 推荐查看顺序

按 **"租赁运营员一天的工作流"** 看，体验最连贯：

```
1️⃣ 工作台首页 (#3)
   ↓ 看到 "8 单待审核 · 含 3 单风控触发"
2️⃣ 订单列表 (#1)
   ↓ 点开 DZ20260524000183 这单
3️⃣ 订单详情抽屉 (#2)
   ↓ 审核通过 / 拒绝 / 风控复核 / 分配资方
```

---

## 如何查看

任选一种方式：

### 方式 A：在线预览（最快）

直接点上面表格里的 "打开 →" 链接，通过 [htmlpreview.github.io](https://htmlpreview.github.io/) 在浏览器里渲染。

### 方式 B：GitHub Pages（推荐用于评审分享）

1. 仓库 Settings → Pages → Source 选 `Deploy from a branch` → Branch 选 `main` / `(root)` → Save
2. 等 1-2 分钟部署完成
3. 永久 URL 格式：`https://joezjyan-bot.github.io/mandian-prd/prototypes/<filename>.html`

这种链接发给评审同事更专业、加载更快。

### 方式 C：本地打开

```bash
git clone https://github.com/joezjyan-bot/mandian-prd.git
open mandian-prd/prototypes/operations-order-list.html
# 或者 Windows
start mandian-prd/prototypes/operations-order-list.html
```

---

## 视觉基线

所有原型基于 [`joezjyan-bot/finance-zhongtai`](https://github.com/joezjyan-bot/finance-zhongtai) 的 `static/admin-v2/css/tokens.css` design tokens：

| 类别 | Token |
|---|---|
| 主色 | `#2B5CE6`（蓝）|
| 状态色 | `success #10B981` / `warning #F59E0B` / `danger #EF4444` |
| 灰阶 | `gray-50 #FAFBFC` → `gray-900 #111827` |
| 字体 | Inter + PingFang SC，13-14px 基础 |
| 圆角 | 4 / 6 / 8 / 12px |
| 间距 | 4 / 8 / 12 / 16 / 20 / 24 / 32px |
| 表格 | 表头 `#F9FAFB`，行高 56px，hover `#F4F5F7` |
| 状态 pill | 圆角 999px，24px 高，light bg + 主色文字 |

图标库：[Tabler Icons](https://tabler-icons.io/) CDN 引入，无需本地依赖。

---

## 设计原则（来自 PRD §6）

原型严格遵守 PRD 给出的"该有什么 / 不该有什么"约束：

✅ **该有的**：
- 高信息密度表格 / 列表
- 一屏看到关键信号（状态、风控、备注、回调）
- 操作按钮主次分明（主操作蓝色 / 危险操作红色 outline）
- 数字脱敏（手机 / 身份证 / 地址）
- 等宽字体 + tnum 让数字对齐
- 所有 KPI / 卡片都直接可点 → 跳到对应工作流

❌ **不该有的**：
- 营销风大首屏 / 渐变背景
- 折叠隐藏关键信息的卡片
- 通用 OK/Cancel 模态（必须给出业务上下文）
- 危险操作和普通按钮挨在一起
- 任何"钱相关"按钮不提示二次确认

---

## 待补充原型清单（参考 PRD §5.1）

> 范围严格按 [`PRD/V0.2/04_UI风格与页面原型规范.md`](../PRD/V0.2/04_UI%E9%A3%8E%E6%A0%BC%E4%B8%8E%E9%A1%B5%E9%9D%A2%E5%8E%9F%E5%9E%8B%E8%A7%84%E8%8C%83.md) §5 列出的页面，**全部属于租赁系统**，不要把中控台的页面塞进来。

### P1 - 评审最需要看到的（建议接下来 3-5 个）

| 页面 | 配套 PRD 模块 | 进度 |
|---|---|---|
| ~~订单详情抽屉~~ | ~~运营端订单详情~~ | ✅ 已交付（#2） |
| ~~运营工作台首页~~ | ~~PRD §5.1 第 1 项~~ | ✅ 已交付（#3） |
| 商品列表 | PRD §5.1 第 2 项 | ⏸ |
| 门店 H5 办单助手 | PRD §5.3 第 5 项 | ⏸ |
| 客户登录页 | `modules/全局/01_登录页.md` | ⏸ |

### P2 - 业务核心补充

| 页面 |
|---|
| 待审核工作台（订单列表筛选预设）|
| 逾期管理 |
| 归还续租 |
| 资方分配工作台 |
| 财务分账明细 |
| 商家 PC 工作台 |

### P3 - 长尾页面

其余 PRD §5.1 - §5.4 列出的 30+ 个页面。

---

## 添加新原型的工作流

1. 在本目录新增 `<page-name>.html`（standalone，零依赖，直接打开就能渲染）
2. 顶部加 banner（深色 + "原型" tag + 链回 PRD 章节）
3. 更新本 README 的"当前原型清单"表格
4. **严格按 PRD 写的字段画**，不要自己添加 PRD 没提到的概念（如果 PRD 漏了关键概念，先回 PRD 补充，再画 UI）
5. （可选）开 PR review，合并后自动出现在 GitHub Pages

**HTML 文件体积建议**：
- 单文件 < 40KB（GitHub MCP push 在 25-37KB 实测可成功；50KB+ 极可能 4 分钟超时）
- 重复结构用 JS 数据驱动渲染（参考 `operations-order-list.html` 末尾的 `orders` 数组）

**设计 token 引用**：复制 `finance-zhongtai/static/admin-v2/css/tokens.css` 关键变量到 `:root` 段，不要 import 远程 CSS（保证 standalone）。

---

## 命名约定

```
<端>-<功能>-<视图>.html

例：
operations-order-list.html          运营端 + 订单 + 列表
operations-order-detail.html        运营端 + 订单 + 详情
operations-workbench-home.html      运营端 + 工作台 + 首页
merchant-product-list.html          商家 PC 端 + 商品 + 列表
mobile-staff-workbench.html         门店 H5 + 员工 + 工作台
global-login.html                   全局 + 登录
```

> 命名只用租赁系统的端口分类：`operations / merchant / mobile / channel / funder / global`。
> **不会有** `zhongkong-*` 这类前缀——中控台的 UI 不在这个目录。

---

## 与 PRD 的关系

```
飞书 PRD（V0.2，租赁系统）
    │
    │  内容流向
    ↓
docs/PRD/V0.2/*.md
    │
    │  视觉化
    ↓
prototypes/*.html   ← 你现在在这
    │
    │  最终落地
    ↓
mandian-rental（PHP 项目，独立仓库）
```

原型**不是合同**——最终视觉以满点 UI 设计师在 Figma / Sketch 中产出的设计稿为准。本目录的原型只服务于：

1. 让 PRD 评审能"看见"，而不只是"读到"
2. 让研发评估实现成本时有可拆字段、可点的目标
3. 让 UI 设计师有"高保真的灰底版"作为视觉打磨起点

---

## 联系

- 产品负责人：Hudson
- 仓库：https://github.com/joezjyan-bot/mandian-prd
