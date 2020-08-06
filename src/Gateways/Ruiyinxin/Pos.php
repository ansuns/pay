<?php


namespace Ansuns\Pay\Gateways\Ruiyinxin;

use Ansuns\Pay\Gateways\Ruiyinxin;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信POS刷卡支付网关
 * Class PosGateway
 * @package Pay\Gateways\Wechat
 */
class Pos extends Ruiyinxin
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
        $this->service = '';
        $this->setReqData($options);

        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            return $this->buildPayResult($result);
        }
        $result['trade_state'] = ($result['err_code'] == '2068') ? 'USERPAYING' : 'PAYERROR'; //只要不是支付中,则认为支付失败
        return $result;
    }
}
