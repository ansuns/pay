<?php


namespace Ansuns\Pay\Gateways\Sandpay;

use Ansuns\Pay\Gateways\Sandpay;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信公众号支付网关
 * Class MpGateway
 * @package Pay\Gateways\Sandpay
 */
class Mp extends Sandpay
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
        $this->service = "/precreate";
        $data = [
            'merchantCode' => $this->userConfig->get('merchant_no'),
            'channel' => 'WXPAY',
            'tradeType' => $this->getTradeType(),
            'subject' => '微信买单',
            'outTradeNo' => $options['out_trade_no'],
            'totalAmount' => ToolsService::ncPriceFen2yuan($options['total_fee']),
            'openId' => $options['openid'],
            'notifyUrl' => str_replace('https', 'http', $options['notify_url'])
        ];
        $this->config = array_merge($this->config, $data);
        $result = $this->getResult();
        $payRequest = [
            'appId' => $result['appId'],
            'timeStamp' => $result['timeStamp'],
            'nonceStr' => $result['nonceStr'],
            'signType' => $result['signType'],
            'package' => $result['payPackage'],
            'paySign' => $result['paySign'],
        ];
        return $payRequest;
    }
}
