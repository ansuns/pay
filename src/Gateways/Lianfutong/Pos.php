<?php


namespace Ansuns\Pay\Gateways\Lianfutong;

use Ansuns\Pay\Gateways\Lianfutong;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信POS刷卡支付网关
 * Class PosGateway
 * @package Pay\Gateways\Wechat
 */
class Pos extends Lianfutong
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'MICROPAY';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $this->service = '/pay';
        $data = [
            'merchantCode' => $this->userConfig->get('merchant_no'),
            'subject' => $options['body'],
            'outTradeNo' => $options['out_trade_no'],
            'totalAmount' => ToolsService::ncPriceFen2yuan($options['total_fee']),
            'authCode' => $options['auth_code'],
            //'deviceInfo'   => isset($options['device_id']) ? $options['device_id'] : '',
            // 'subAppId'=> $options['appid']
        ];
        $this->config = array_merge($this->config, $data);
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            return $this->buildPayResult($result);
        }
        $result['trade_state'] = ($result['err_code'] == 'USER_PAYING') ? 'USERPAYING' : 'PAYERROR'; //只要不是支付中,则认为支付失败
        return $result;
    }
}
