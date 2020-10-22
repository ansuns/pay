<?php


namespace Ansuns\Pay\Gateways\Bhecard;

use Ansuns\Pay\Gateways\Bhecard;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信小程序支付网关
 * Class MiniappGateway
 * @package Pay\Gateways\Wechat
 */
class Miniapp extends Bhecard
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
        $this->service = "easypay.js.pay.push";
        $this->setReqData($options);
        $result = $this->getResult();
        $result['success_data'] = "";
        if ($this->isSuccess($result)) {
            $result['success_data'] = json_decode($result['pay_info'], true);;
            $result['success_data'] = isset($result['success_data']['alipayTradeNo']) ? $result['success_data']['alipayTradeNo'] : $result['success_data'];
            return $result;
        }
        return $result;
    }

    public function newPay(array $options = [])
    {
        $this->service = "trade.acc.dsfpay.newPay";
        //$this->service = "trade.acc.dsfpay.newPay";
        $this->setReqData($options);
        $result = $this->getResult();
        return $result;
    }
}