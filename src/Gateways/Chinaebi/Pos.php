<?php


namespace Ansuns\Pay\Gateways\Chinaebi;

use Ansuns\Pay\Gateways\Chinaebi;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信POS刷卡支付网关
 * Class PosGateway
 * @package Pay\Gateways\Wechat
 */
class Pos extends Chinaebi
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
     * code_url，生成电银动态二维码(C 扫 B)
     * @param array $options
     * @return array
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $this->setReqData($options);
        return $this->getResult();
    }
}
