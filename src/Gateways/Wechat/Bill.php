<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Gateways\Wechat;

/**
 * 下载微信电子面单
 * Class BillGateway
 * @package Pay\Gateways\Wechat
 */
class Bill extends Wechat
{
    protected $field_map = [
        'trade_time' => '交易时间',
        'appid' => '公众账号ID',
        'mch_id' => '商户号',
        'sub_mch_id' => '特约商户号',
        'device_info' => '设备号',
        'transaction_id' => '微信订单号',
        'out_trade_no' => '商户订单号',
        'openid' => '用户标识',
        'trade_type' => '交易类型',
        'trade_state' => '交易状态',
        'bank_type' => '付款银行',
        'fee_type' => '货币种类',
        'settlement_total_fee' => '应结订单金额',
        'coupon_fee' => '代金券金额',
        'refund_transaction_id' => '微信退款单号',
        'refund_out_trade_no' => '商户退款单号',
        'refund_fee' => '退款金额',
        'recharge_coupon_refund_fee' => '充值券退款金额',
        'refund_type' => '退款类型',
        'refund_state' => '退款状态',
        'body' => '商品名称',
        'attach' => '商户数据包',
        'fee' => '手续费',
        'rate' => '费率',
        'total_fee' => '订单金额',
        'apply_refund_fee' => '申请退款金额',
        'rate_remark' => '费率备注'
    ];

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return bool|string|array
     */
    public function apply(array $options)
    {
        $options['bill_date'] = isset($options['bill_date']) ? $options['bill_date'] : date('Ymd', strtotime('-1 day'));
        $options['bill_type'] = isset($options['bill_type']) ? $options['bill_type'] : 'ALL';
        $this->config = array_merge($this->config, $options);
        $this->unsetTradeTypeAndNotifyUrl();
        $this->config['sign'] = $this->getSign($this->config);
        $data = $this->post($this->gateway_bill, $this->toXml($this->config));
        if (isset($options['tar_type']) && $options['tar_type'] == 'GZIP') {
            return $data;
        }
        return $this->parserDownloadData($data, $this->field_map);
    }
}