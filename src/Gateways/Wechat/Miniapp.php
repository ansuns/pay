<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Gateways\Wechat;

/**
 * 微信小程序支付网关
 * Class MiniappGateway
 * @package Pay\Gateways\Wechat
 */
class Miniapp extends Wechat
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
     * 应用并返回参数
     * @param array $options
     * @return array
     * @throws \Pays\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $this->config['appid'] = $this->userConfig->get('app_id');
        $payRequest = [
            'appId' => $this->config['appid'],
            'timeStamp' => time() . '',
            'nonceStr' => $this->createNonceStr(),
            'package' => 'prepay_id=' . $this->preOrder($options)['prepay_id'],
            'signType' => 'MD5',
        ];
        $payRequest['paySign'] = $this->getSign($payRequest);
        return $payRequest;
    }
}
