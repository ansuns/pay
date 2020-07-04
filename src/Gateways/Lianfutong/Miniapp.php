<?php


namespace Ansuns\Pay\Gateways\Lianfutong;

use Ansuns\Pay\Gateways\Lianfutong;

/**
 * 微信小程序支付网关
 * Class MiniappGateway
 * @package Pay\Gateways\Wechat
 */
class Miniapp extends Lianfutong
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'MINIAPP';
    }

    /**
     * 应用并返回参数
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
            'subAppId' => $options['appid'],
            'notifyUrl' => str_replace('https', 'http', $options['notify_url'])
        ];
        $this->config = array_merge($this->config, $data);
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            $pay_request = [
                'appId' => $result['appId'],
                'timeStamp' => $result['timeStamp'],
                'nonceStr' => $result['nonceStr'],
                'signType' => $result['signType'],
                'package' => $result['payPackage'],
                'paySign' => $result['paySign'],
            ];
            return $pay_request;
        }
        return $result;
    }
}
