<?php


namespace Ansuns\Pay\Gateways\Sandpay;

use Ansuns\Pay\Gateways\Sandpay;

/**
 * 微信小程序支付网关
 * Class MiniappGateway
 * @package Pay\Gateways\Sandpay
 */
class Miniapp extends Sandpay
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'trade.create';
    }

    public function apply(array $options = [])
    {
        $this->config['method'] = $this->getTradeType();
        $this->setReqData($options);
        $result = $this->getResult();

        if ($this->isSuccess($result)) {
            return $result;
        } else {
            try {
                //特定失败自动尝试配置APPID
                $this->gateway = $this->gatewayAgent;
                $sub_app_id = $this->config['sub_app_id'];
                $this->config['method'] = 'merchant.bind.wechat.appid';
                $this->config['biz_content'] = [];
                $this->setReqData([
                    'sub_merchant_id' => $this->config['sub_app_id'],
                    'sub_appid' => $options['mer_app_id'],//必传
                ]);
                unset($this->config['sub_app_id']);
                $this->getResult();

                //自动尝试配置授权目录
                $this->gateway = $this->gatewayAgent;
                $this->config['method'] = 'merchant.add.pay.path';
                $this->config['biz_content'] = [];
                $this->setReqData([
                    'sub_merchant_id' => $sub_app_id,
                    'jsapi_path' => 'http://www.oiopay.com/api/merchant/',//必传
                ]);
                unset($this->config['sub_app_id']);
                $this->getResult();

            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
        return $result;
    }
}