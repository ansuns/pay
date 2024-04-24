<?php


namespace Ansuns\Pay\Gateways\SandpayOnline;

use Ansuns\Pay\Gateways\SandpayOnline;

/**
 * 微信App支付网关
 * Class AppGateway
 * @package Pay\Gateways\Sandpay
 */
class App extends SandpayOnline
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'APP';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $payRequest = [
            'appid' => $this->userConfig->get('app_id'),
            'partnerid' => $this->userConfig->get('mch_id'),
            'prepayid' => $this->preOrder($options)['prepay_id'],
            'timestamp' => time() . '',
            'noncestr' => $this->createNonceStr(),
            'package' => 'Sign=WXPay',
        ];
        $payRequest['sign'] = $this->getSign($payRequest);
        return $payRequest;
    }

}
