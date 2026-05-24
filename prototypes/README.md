# 满点 PRD 配套 UI 原型

本目录存放 PRD V0.2 配套的可视化 UI 原型，作为飞书 PRD 的补充材料。

> **定位**：高保真原型，不是最终设计稿。让 PRD 评审能直接看到"列表即工作台"等设计理念落到屏幕上是什么样，方便：
> - PM ↔ 研发对齐字段口径
> - 研发评估实现难度
> - UI 设计师在此之上做最终视觉打磨

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

按 **"运营员一天的工作流"** 看，体验最连贯：

```
1️⃣ 工作台首页 (#3)
   ↓ 看到 "8 单待审核 · 含 3 单风控触发"
2️⃣ 订单列表 (#1)
   ↓ 点开 DZ20260524000183 这单
3️⃣ 订单详情抽屉 (#2)
   ↓ 审核通过 / 拒绝 / 风控复核
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

以下是 PRD 列出但尚未画原型的页面，按优先级排序：

### P1 - 评审最需要看到的（建议接下来 3-5 个）

| 页面 | 配套 PRD 模块 | 进度 |
|---|---|---|
| ~~订单详情抽屉~~ | ~~`modules/运营端/订单管理/02_订单详情.md`~~ | ✅ 已交付（#2） |
| ~~运营工作台首页~~ | ~~PRD §5.1 第 1 项~~ | ✅ 已交付（#3） |
| 商品列表 | 待补，PRD §5.1 第 2 项 | ⏸ |
| 门店 H5 办单助手 | 待补，与 PC 完全不同的视觉语言 | ⏸ |
| 客户登录页 | `modules/全局/01_登录页.md` | ⏸ |

### P2 - 业务核心补充

| 页面 |
|---|
| 待审核工作台（订单列表筛选预设）|
| 逾期管理 |
| 归还续租 |
| 资方分配工作台 |
| 财务分账明细 |

### P3 - 长尾页面

其余 PRD §5.1 列出的 30+ 个页面。

---

## 添加新原型的工作流

1. 在本目录新增 `<page-name>.html`（standalone，零依赖，直接打开就能渲染）
2. 顶部加 banner（深色 + "原型" tag + 链回 PRD 章节）
3. 更新本 README 的"当前原型清单"表格
4. （可选）开 PR review，合并后自动出现在 GitHub Pages

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
merchant-product-list.html          商家端 + 商品 + 列表
mobile-staff-workbench.html         门店 H5 + 员工 + 工作台
global-login.html                   全局 + 登录
```

---

## 与 PRD 的关系

```
飞书 PRD（V0.2）
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
