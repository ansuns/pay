<?php


namespace Ansuns\Pay\Gateways\Chinaebi;

use Ansuns\Pay\Gateways\Chinaebi;
use Ansuns\Pay\Pay;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信小程序支付网关
 * Class MiniappGateway
 * @package Pay\Gateways\Wechat
 */
class Miniapp extends Chinaebi
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
        $this->setReqData($options);
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            $trans_type = $result['body']['trans_type'] ?? 'WX_JSAPI';
            if ($trans_type == 'WX_JSAPI') {
                $wx_pay_info = json_decode($result['body']['wx_pay_info'], true);
                $pay_request = [
                    'appId' => $wx_pay_info['appId'],
                    'timeStamp' => $wx_pay_info['timeStamp'],
                    'nonceStr' => $wx_pay_info['nonceStr'],
                    'signType' => $wx_pay_info['signType'],
                    'package' => $wx_pay_info['package'],
                    'paySign' => $wx_pay_info['paySign'],
                ];
            } else {
                //商户页面通过 JSSDK 直接调用支付宝 APP 时，商户需要将返回的交易号 TradeNo 去掉前两位
                $pay_request['trade_no'] = substr_replace($result['body']['al_pay_info'], '', 0, 2);
            }
            $result['success_data'] = $pay_request;
            return $result;
        } else {
            //特定失败自动尝试配置APPID
            $pay = new Pay(['chinaebi' => self::$otherConfig]);
            $sub_app_id = $options['sub_app_id'] ?? '';
            $result = $pay->driver('chinaebi')->gateway('mch')->bind_config($sub_app_id);
        }
        return $result;
    }
}