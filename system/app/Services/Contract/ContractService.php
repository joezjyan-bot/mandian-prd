<?php

namespace App\Services\Contract;

use App\Contracts\EsignContract;
use App\Models\Order;
use RuntimeException;

/**
 * 合同签署服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:运营端/合同公证/01_合同签署流程.md(§1-§8);
 *           全局/02 状态字典 §6.3(合同状态)、§6.4(公证状态)、§5.1。
 *
 * ⚠️ 本文件只定义"合同环节有哪些动作、对应文档哪一步、该置什么状态、该校验什么",
 *    方法体均为占位(throw 未实现),不预先写死实现。业务团队按注释填充即可。
 *
 * —— 关键合规约束(§2,务必遵守)——
 * 合同三方固定为:甲方=门店(出租人/服务方)、乙方=客户(承租人)、丙方=平台(居间/技术/代收代付)。
 * **资方(内部资金来源)在合同中不出现**,以避免融资租赁定性。
 * 合同模板按订单 contract_template_id 选择(§3):商家订单用默认模板;
 * 联营/平台订单用资金来源专属模板(若有)或默认模板。该字段在审核阶段已锁定(运营端04 §9.3.3),
 * 本服务只读取使用,不在此决定资金来源。
 *
 * —— 设计约定(便于接手,降低耦合)——
 * 1. 电子签通道走 EsignContract(mock/real 不感知);通道选择(e签宝/法大大)按配置。
 * 2. 主状态流转经 OrderStatus::canTransition(§5.1):
 *    合同全部签署完成后,订单从 PENDING_SIGN → PENDING_FIRST_PAYMENT(由 OrderService::sign 现有逻辑或本服务回调推进,二者择一,避免重复置位)。
 * 3. 合同子状态(§6.3 CONTRACT_*)、公证子状态(§6.4 NOTARY_*)建议用独立字段,
 *    字段是否落库、落 orders 还是独立 order_contract 表(见文档§8/00核心决策表),由团队决定;骨架不擅自建表。
 */
class ContractService
{
    public function __construct(private EsignContract $esign) {}

    /**
     * 发起合同签署(§1 第4-5步、§4)。
     * 前置:订单处于 PENDING_SIGN(审核通过后)。
     * 动作:① 按订单 contract_template_id 锁定模板(§3,本服务只读取,不决定资金来源);
     *      ② 调 EsignContract::sign 推送三方合同链接;
     *      ③ 设签署顺序与超时(默认 24h,§4);
     *      ④ 合同子状态 → CONTRACT_SENT(§6.3)。
     * ⚠️ 合规:三方=门店/客户/平台,资方不出现(§2)。
     * TODO(业务团队):实现模板选择、通道发起、签署顺序与超时、子状态置位、记日志。
     */
    public function initiate(Order $order): Order
    {
        throw new RuntimeException('ContractService::initiate 待实现(合同公证01 §4 发起签署;三方不含资方 §2)');
    }

    /**
     * 客户签署完成回调(§5.1)。
     * 客户须本人完成(身份证人脸+验证码),不可代签;人脸不过可重试(累计3次客服介入,§7.2)。
     * TODO(业务团队):校验客户本人、更新合同子状态、记录签署时间。
     */
    public function onCustomerSigned(Order $order, array $callback): Order
    {
        throw new RuntimeException('ContractService::onCustomerSigned 待实现(合同公证01 §5.1 客户签署)');
    }

    /**
     * 门店签署完成回调(§5.2)。门店主账号签署。
     * TODO(业务团队):更新合同子状态、记录签署时间。
     */
    public function onStoreSigned(Order $order, array $callback): Order
    {
        throw new RuntimeException('ContractService::onStoreSigned 待实现(合同公证01 §5.2 门店签署)');
    }

    /**
     * 平台签署(§5.3)。平台预先备案电子印章,系统自动签;部分场景运营管理员手动签。
     * TODO(业务团队):自动签署/手动签署入口、更新合同子状态。
     */
    public function platformSign(Order $order): Order
    {
        throw new RuntimeException('ContractService::platformSign 待实现(合同公证01 §5.3 平台签署)');
    }

    /**
     * 全部签署完成回调(§6)。
     * 动作:① 合同子状态 → CONTRACT_SIGNED(§6.3);② 下载 PDF 副本存档(§6、03文档);
     *      ③ 推进订单进入下一节点(发货/公证)。
     * ⚠️ 主状态推进(PENDING_SIGN → PENDING_FIRST_PAYMENT)与 OrderService::sign 现有逻辑择一,
     *    避免重复置位;由 Controller 编排,本服务不直接调 OrderService。
     * TODO(业务团队):子状态置位、PDF 存档、回调通知。
     */
    public function onAllSigned(Order $order, array $callback): Order
    {
        throw new RuntimeException('ContractService::onAllSigned 待实现(合同公证01 §6 签署完成)');
    }

    /**
     * 发起公证(§1 第8步,可选;§6.4 公证状态)。
     * 公证为增值服务(办单助手02 §4 赋强公证),由客服/门店决定是否发起;短租不使用公证。
     * TODO(业务团队):对接公证服务(02文档)、公证子状态 NOTARY_*(§6.4)。
     */
    public function initiateNotary(Order $order): Order
    {
        throw new RuntimeException('ContractService::initiateNotary 待实现(合同公证01 §1/02文档 公证,可选)');
    }

    /**
     * 签署超时处理(§7.1)。默认 24h 未签 → 暂停+告警,客服核实,可重新发起(新计时)。
     * TODO(业务团队):超时检测(定时任务)、暂停告警、重新发起。
     */
    public function handleTimeout(Order $order): Order
    {
        throw new RuntimeException('ContractService::handleTimeout 待实现(合同公证01 §7.1 签署超时)');
    }

    /**
     * 签署失败处理(§7.2)。
     * 客户人脸不过累计3次→客服介入;通道故障→备用通道;客户拒签→订单待客服处理。
     * 合同子状态 → CONTRACT_FAILED(§6.3)或按场景。
     * TODO(业务团队):失败分类处理、备用通道、子状态置位。
     */
    public function handleFailure(Order $order, string $reason): Order
    {
        throw new RuntimeException('ContractService::handleFailure 待实现(合同公证01 §7.2 签署失败)');
    }
}
