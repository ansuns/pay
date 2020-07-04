<?php

namespace Ansuns\Pay\Gateways\Alipay;

use Ansuns\Pay\Gateways\Alipay;

/**
 * 支付宝扫码支付
 * Class ScanGateway
 * @package Pay\Gateways\Alipay
 */
class Scan extends Alipay
{

    /**
     * 当前接口方法
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.trade.precreate';
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
}
