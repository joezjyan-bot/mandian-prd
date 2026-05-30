<?php

namespace App\Services\Delivery;

use App\Models\Order;
use RuntimeException;

/**
 * 交付签收照片证据服务【骨架 / 接口契约,待业务团队实现】。
 *
 * 唯一依据:运营端/订单管理/02_交付签收照片证据.md(§2 证据阶段、§4 字段、§5 业务规则);
 *           与 DeliveryService(已实现交付/签收主流程)互补——本服务管"证据链",不重复管状态流转。
 *
 * ⚠️ 方法体均为占位(throw 未实现),不预先写死实现。
 *
 * —— 关键业务规则(§5,务必遵守)——
 * 1. 长租车辆类必须填设备识别码(IMEI/SN/VIN)后才能完成交付(§5.1)。
 * 2. 门店员工只能上传自己商家订单或被授权订单的照片(§5.3)。
 * 3. 客户签收后,交付前照片不允许删除,只允许追加异常说明(§5.4)。
 * 4. 所有上传/删除/追加/客户确认进操作日志(§5.6)。
 *
 * —— 人脸 AI 辅助核验:隐私红线(§2.1、§5.7-§5.10,务必严守)——
 * 1. AI 只输出辅助建议(high_match/suspect_mismatch/undetermined),**不得自动放行或自动拒绝**;
 *    最终交付核验必须由有权限的人工点击通过/驳回/要求重传(§2.1.2)。
 * 2. **不建立可检索的人脸库,不支持按人脸检索历史客户或订单**(§2.1.5)。
 *    只存:比对结果、评分、供应商标识、调用流水号、人工核验结论(§4.1)。
 * 3. C 端不出现"人脸比对""风控"字眼,客户侧按"资料审核/交付材料审核"口径展示(§2.1.5)。
 * 4. 启用前必须先取得证件影像/实名影像用于交付核验的授权(§5.7)。
 * 5. AI 失败/超时/无法判定 → 自动转人工,不得视为通过或拒绝(§5.8)。
 * 6. suspect_mismatch 核验通过须二次确认+填原因;驳回/重传须记录通知(§5.9)。
 * 7. 阈值(high_match/suspect_mismatch)文档标 TODO【需运营/算法确认】,骨架不擅自设阈值。
 *
 * —— 设计约定(便于接手)——
 * - 证据表、AI 核验字段(§4/§4.1)由团队按文档建,骨架不擅自建表。
 * - 人脸比对走第三方持牌 API(§2.1.1),封装为外部 Contract,本服务只定义调用与结果落库契约,
 *   不实现任何人脸特征提取/检索逻辑(隐私红线)。
 */
class DeliveryEvidenceService
{
    /**
     * 上传证据照片(§2 各阶段、§4 字段)。
     * 阶段:发货/当面交付/签收/归还/异常补充;图片类型:设备/配件/外观/人机合照/人车合照/签收/验收。
     * 校验:上传人权限(§5.3)、车辆类设备识别码必填(§5.1)、客户签收后交付前照片不可删(§5.4)。
     * TODO(业务团队):落证据记录、权限校验、写操作日志。
     */
    public function uploadEvidence(Order $order, string $stage, string $imageType, array $images, int $uploaderId): array
    {
        throw new RuntimeException('DeliveryEvidenceService::uploadEvidence 待实现(运营端02 §2/§4 证据上传)');
    }

    /**
     * 追加异常说明(§5.4:客户签收后只允许追加,不允许删原照片)。
     * TODO(业务团队):追加说明、写操作日志。
     */
    public function appendAnomalyNote(Order $order, int $evidenceId, string $note, int $operatorId): void
    {
        throw new RuntimeException('DeliveryEvidenceService::appendAnomalyNote 待实现(运营端02 §5.4 异常追加)');
    }

    /**
     * 客户确认收货(§4 客户确认状态:未确认/已确认/有异议)。
     * 与 DeliveryService::confirmReceipt 的关系:本方法管"证据层的客户确认状态",
     * 订单主状态流转仍由 DeliveryService 负责;由 Controller 编排,避免双写状态。
     * TODO(业务团队):置客户确认状态、写操作日志。
     */
    public function customerConfirm(Order $order, int $evidenceId, string $confirmStatus): void
    {
        throw new RuntimeException('DeliveryEvidenceService::customerConfirm 待实现(运营端02 §4 客户确认)');
    }

    /**
     * 发起人机/人车合照 AI 辅助核验(§2.1)。
     * ⚠️ 隐私红线:调第三方持牌人脸比对 API,做"合照人脸 vs 下单实名留存人脸"一次性比对;
     *    只存结果/评分/供应商/流水号(§4.1),不建可检索人脸库。
     *    返回三档建议,**不自动放行/拒绝**;失败/超时/无法判定转人工。
     * TODO(业务团队):封装第三方 Contract 调用、落 AI 字段;不实现任何人脸检索/特征库逻辑。
     */
    public function requestFaceMatchAssist(Order $order, int $evidenceId): array
    {
        throw new RuntimeException('DeliveryEvidenceService::requestFaceMatchAssist 待实现(运营端02 §2.1 AI辅助核验;严守隐私红线、AI不自动放行)');
    }

    /**
     * 人工核验(§2.1.2、§4.1 manual_verify_*)。
     * 结果:passed/rejected/need_reupload。suspect_mismatch 通过须二次确认+填原因(§5.9)。
     * 这是交付核验的最终决定权(AI 仅辅助)。
     * TODO(业务团队):人工核验落库、二次确认校验、通知记录、写操作日志。
     */
    public function manualVerify(Order $order, int $evidenceId, string $result, string $note, int $verifierId): void
    {
        throw new RuntimeException('DeliveryEvidenceService::manualVerify 待实现(运营端02 §2.1.2 人工核验,最终决定权);');
    }

    /**
     * 归还验收照片与库存入库联动(§5.5)。
     * TODO(业务团队):归还验收证据、与库存设备管理模块联动入库状态。
     */
    public function recordReturnInspection(Order $order, array $images, int $operatorId): array
    {
        throw new RuntimeException('DeliveryEvidenceService::recordReturnInspection 待实现(运营端02 §5.5 归还验收)');
    }
}
