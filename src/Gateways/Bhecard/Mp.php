<?php


namespace Ansuns\Pay\Gateways\Bhecard;

use Ansuns\Pay\Gateways\Bhecard;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信公众号支付网关
 * Class MpGateway
 * @package Pay\Gateways\Wechat
 */
class Mp extends Bhecard
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
        $this->service = "apppayacc";
        $options ['tradetype'] = $this->getTradeType();
        $this->setReqData($options);
        $result = $this->getResult();

        $result['success_data'] = "";
        if ($this->isSuccess($result)) {
            $result['success_data'] = ($options['opt'] == 'apPreOrder') ? (string)$result['prepayid'] : json_decode($result['prepayid'], true);;
        }
        return $result;
    }
}
