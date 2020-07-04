<?php

namespace Ansuns\Pay\Gateways\Alipay;

use Ansuns\Pay\Gateways\Alipay;

/**
 * 支付宝授权网关
 * Class AppGateway
 * @package Pay\Gateways\Alipay
 */
class Auth extends Alipay
{

    /**
     * 当前接口方法
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.open.auth.token.app';
    }

    /**
     * 当前接口方法
     * @return string
     */
    protected function getQueryMethod()
    {
        return 'alipay.open.auth.token.app.query';
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
     * @return string
     */
    public function apply(array $options = [])
    {
        return $this->getResult($options, $this->getMethod());
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return string
     */
    public function query(array $options = [])
    {
        return $this->getResult($options, $this->getQueryMethod());
    }
}
