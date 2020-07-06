<?php


namespace Ansuns\Pay\Gateways\Sandpay;

use Ansuns\Pay\Gateways\Sandpay;
use Ansuns\Pay\Service\ToolsService;

/**
 * 付款码支付网关
 * Class PosGateway
 * @package Pay\Gateways\Wechat
 */
class Pos extends Sandpay
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'trade.percreate';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $this->setReqData($options);
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            return $this->buildPayResult($result);
        }
        $result['trade_state'] = ($result['err_code'] == '2068') ? 'USERPAYING' : 'PAYERROR'; //只要不是支付中,则认为支付失败
        return $result;
    }
}
