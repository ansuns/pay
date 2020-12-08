<?php


namespace Ansuns\Pay\Gateways\Chinaebi;

use Ansuns\Pay\Gateways\Chinaebi;

/**
 * 微信扫码支付网关
 * Class ScanGateway
 * @package Pay\Gateways\Wechat
 */
class Scan extends Chinaebi
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'NATIVE';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return mixed
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $this->setReqData($options);
        return $this->getResult();
    }
}
