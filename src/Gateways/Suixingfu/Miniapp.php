<?php


namespace Ansuns\Pay\Gateways\Suixingfu;

use Ansuns\Pay\Gateways\Suixingfu;

/**
 * 微信小程序支付网关
 * Class MiniappGateway
 * @package Pay\Gateways\Wechat
 */
class Miniapp extends Suixingfu
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
     * @throws \Pays\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $this->service = "/qr/jsapiScan";
        $this->setReqData([
            'ordNo' => $options['out_trade_no'] ?? ToolsService::get_bill_number(),
            'amt' => ToolsService::ncPriceFen2yuan($options['total_fee']),
            'payType' => 'LETPAY',
            'timeExpire' => 3,
            'subject' => '微信小程序买单',
            'subOpenid' => $options['openid'],
            'subAppid' => $options['appid'], //wxfb6c194c44d7cc1f
            'notifyUrl' => str_replace('https', 'http', $options['notify_url']),
            //'subMchId'     => $this->userConfig->get('sub_mch_id'),//必传
            //'attach'       => '{}',
        ]);
        $result = $this->getResult();
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