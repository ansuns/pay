<?php


namespace Ansuns\Pay\Gateways\Ruiyinxin;

use Ansuns\Pay\Gateways\Ruiyinxin;

/**
 * 微信小程序支付网关
 * Class MiniappGateway
 * @package Pay\Gateways\Wechat
 */
class Miniapp extends Ruiyinxin
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'SMZF010';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $this->config['tranCode'] = $this->getTradeType(); //扫码支付
        $this->setReqData($options);
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            $pay_request = [
                'appId' => $options['subAppid'] ?? '',
                'timeStamp' => time(),
                'nonceStr' => time(),
                'signType' => '',
                'package' => '',
                'paySign' => '',
            ];
            $result['data'] = $result['data']['wxjsapiStr'] ? json_decode($result['data']['wxjsapiStr'], true) : $pay_request;
            return $result;
        } else {
            $this->bindAppidAndPath($options);
        }
        return $result;
    }

    /**
     * 绑定appid和授权目录
     * @param array $options
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function bindAppidAndPath(array $options = [])
    {
        $subAppid = $options['subAppId'] ?? '';
        $merchantCode = $options['merchantCode'] ?? '';
        $this->gateway = "https://qr.ruiyinxin.com/ydzf/wechat/gateway";
        $this->config['tranCode'] = 'SUB_APPID'; //扫码支付
        $this->setReqData(['subAppid' => $subAppid, 'merchantCode' => $merchantCode]);
        $this->getResult();
        $this->gateway = "https://qr.ruiyinxin.com/ydzf/wechat/gateway";
        $this->config['tranCode'] = 'JSAPI_PATH'; //扫码支付
        $this->setReqData(['jsapiPath' => "https://www.oiopay.com/api/ryx/weixin/", 'merchantCode' => $merchantCode]);
        $this->getResult();

    }
}