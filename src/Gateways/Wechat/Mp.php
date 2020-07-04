<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Gateways\Wechat;

/**
 * 微信公众号支付网关
 * Class MpGateway
 * @package Pay\Gateways\Wechat
 */
class Mp extends Wechat
{
    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'JSAPI';
    }

    /**
     * 设置并返回参数
     * @param array $options
     * @return array
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $payRequest = [
            'appId' => $this->userConfig->get('app_id'),
            'timeStamp' => time() . '',
            'nonceStr' => $this->createNonceStr(),
            'package' => 'prepay_id=' . $this->preOrder($options)['prepay_id'],
            'signType' => 'MD5',
        ];
        $payRequest['paySign'] = $this->getSign($payRequest);
        return $payRequest;
    }
}
