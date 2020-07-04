<?php

namespace Ansuns\Pay\Gateways\Alipay;

use Ansuns\Pay\Gateways\Alipay;

/**
 * 支付宝网站支付网关
 * Class WebGateway
 * @package Pay\Gateways\Alipay
 */
class Web extends Alipay
{

    /**
     * 当前接口方法
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.trade.page.pay';
    }

    /**
     * 当前接口产品码
     * @return string
     */
    protected function getProductCode()
    {
        return 'FAST_INSTANT_TRADE_PAY';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return string
     */
    public function apply(array $options = [])
    {
        parent::apply($options);
        return $this->buildPayHtml();
    }
}
