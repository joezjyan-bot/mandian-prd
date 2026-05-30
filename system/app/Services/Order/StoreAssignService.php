<?php

namespace App\Services\Order;

use App\Models\Order;
use RuntimeException;

/**
 * 平台订单门店分配服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:运营端/订单管理/07_平台订单门店分配.md(§2 状态、§3 数据模型、§4 分配、
 *           §5 改派、§9 权限、§10 异常、§12 接口);全局/02 §6.12(分配字段)。
 *
 * ⚠️ 方法体均为占位(throw 未实现)。仅平台订单(receivables_assignment)使用本服务;
 *    商家订单/联营订单扫码即门店,不进入门店分配(§1)。
 *
 * —— 状态口径冲突,待 Hudson 确认(第6条)——
 * 运营端07 §2 说新增主状态 PENDING_STORE_ASSIGN;
 * 但全局/02 §0.1(V0.2.2,更新于07之后)的26个主状态里【未收录】该状态,
 * 且 §11.5 明确"门店分配超时不新增主状态,订单主状态仍保持在 PENDING_REVIEW 的内部子状态中"。
 * → 两份文档口径冲突。倾向以 §0.1 收敛口径为准(不单设主状态,用 PENDING_REVIEW + 分配字段表达),
 *   因 §0.1 是最新且声明"冲突以本补丁为准"。
 * 【本骨架不写死状态置位】:分配动作/字段两种口径一致,方法体待确认口径后由团队实现。
 *
 * —— 关键业务/合规约束 ——
 * 1. 严格顺序(§2.2):必须先完成内部资金来源分配,才能分配门店。
 * 2. 核心风险=打款打错门店(§开篇):必须多维度精确匹配 + 工号二次确认 + 操作日志。
 * 3. 分配字段(§3.1):assigned_store_id / assigned_merchant_id / assigned_at /
 *    assigned_by / assigned_by_employee_no;分配日志表 order_store_assign_log(§3.2)。
 * 4. 候选门店过滤(§10):未开通安心用/已关停/收款账户冻结的门店,系统阻止;跨城市警告不阻止。
 * 5. 改派权限分级(§9):合同发起前主管可改派;合同已签不可改派只能撤单重下。
 * 6. C 端红线(§6.2):分配过程、商家主体、收款账户、资金来源、合同模板均不暴露 C 端;
 *    客户签约后只显示"提货门店:XXX"一行。
 *
 * —— 设计约定(便于接手)——
 * - order_store_assign_log 表、orders 分配字段(§3)由团队按文档建/补,骨架不擅自建表加字段。
 * - 候选门店来自店铺管理模块;本服务只定义筛选契约,不实现门店库查询细节。
 */
class StoreAssignService
{
    /**
     * 列出可分配门店候选(§4 搜索 + §10 过滤)。GET /admin/order/:id/assignable-stores。
     * 多维度匹配:门店名/编码/商家主体/收款户名/城市;过滤未开通安心用、已关停、账户冻结。
     * TODO(业务团队):对接店铺管理门店池,实现搜索与过滤,返回候选列表。
     */
    public function assignableStores(Order $order, array $filters = []): array
    {
        throw new RuntimeException('StoreAssignService::assignableStores 待实现(运营端07 §4/§10 候选门店)');
    }

    /**
     * 首次分配门店(§4 流程 + §4.6 工号二次确认)。
     * 前置:平台订单 + 已完成内部资金来源分配(§2.2 严格顺序)。
     * 动作:写 assigned_store_id/assigned_merchant_id/assigned_at/assigned_by/assigned_by_employee_no;
     *      写 order_store_assign_log(action=assign);状态推进按待确认口径(见类注释)。
     * 校验:工号二次确认(§4.6)、候选门店合法(§10)。
     * TODO(业务团队):前置校验、写分配字段与日志、状态置位(待口径确认)。
     */
    public function assign(Order $order, int $storeId, int $operatorId, string $employeeNo): Order
    {
        throw new RuntimeException('StoreAssignService::assign 待实现(运营端07 §4 首次分配 + 工号二次确认)');
    }

    /**
     * 改派(§5 + §9 权限分级)。
     * 仅主管权限;合同发起前可改派,合同已发起需先撤合同,合同已签不可改派(只能撤单重下)。
     * 写 order_store_assign_log(action=reassign,含 previous_store_id/reason/工号/IP)。
     * TODO(业务团队):按阶段+权限校验、写改派日志、状态/合同联动。
     */
    public function reassign(Order $order, int $newStoreId, int $supervisorId, string $employeeNo, string $reason): Order
    {
        throw new RuntimeException('StoreAssignService::reassign 待实现(运营端07 §5 改派,主管权限)');
    }

    /**
     * 撤销分配(§3.2 action=revoke;改派的前置)。
     * TODO(业务团队):撤销分配字段、写 revoke 日志、回到待分配门店。
     */
    public function revoke(Order $order, int $operatorId, string $reason): Order
    {
        throw new RuntimeException('StoreAssignService::revoke 待实现(运营端07 撤销分配)');
    }
}
