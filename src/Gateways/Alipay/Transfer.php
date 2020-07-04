<?php

namespace Ansuns\Pay\Gateways\Alipay;

use Ansuns\Pay\Gateways\Alipay;

/**
 * 支付宝转账网关
 * Class TransferGateway
 * @package Pay\Gateways\Alipay
 */
class Transfer extends Alipay
{

    /**
     * 当前接口方法
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.fund.trans.toaccount.transfer';
    }

    /**
     * 当前接口产品码
     * @return string
     */
    protected function getProductCode()
    {
        return '';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array|bool
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        return $this->getResult($options, $this->getMethod());
    }

    /**
     * 查询转账订单状态
     * @param string $out_biz_no
     * @return array|bool
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function find($out_biz_no = '')
    {
        $options = ['out_biz_no' => $out_biz_no];
        return $this->getResult($options, 'alipay.fund.trans.order.query');
    }
}
