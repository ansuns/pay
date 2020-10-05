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
        //$this->service = "trade.acc.dsfpay.newPay";
        $this->setReqData($options);
        $result = $this->getResult();
        return $result;
        if ($this->isSuccess($result)) {
            $pay_request = [
                'appId' => $result['payAppId'],
                'timeStamp' => $result['payTimeStamp'],
                'nonceStr' => $result['paynonceStr'],
                'signType' => $result['paySignType'],
                'package' => $result['payPackage'],
                'paySign' => $result['paySign'],
            ];
            return $pay_request;
        } else {
            //特定失败自动尝试配置APPID
            if (isset($result['bizMsg']) && $result['bizMsg'] == '交易失败，请联系客服' && isset($result['bizCode']) && $result['bizCode'] == '2010') {
                try {
                    $this->service = "/weChat/bindconfig";
                    $this->config['reqData'] = [];
                    $this->setReqData([
                        'mno' => $this->userConfig->get('mno', ''),
                        'subMchId' => $this->userConfig->get('sub_mch_id'),//必传
                        'subAppid' => $options['appid'],//必传
                    ]);
                    $this->getResult();
                } catch (\Exception $e) {
                    wr_log($e->getMessage());
                }
            }
        }
        return $result;
    }
}