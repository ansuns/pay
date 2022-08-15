<?php

namespace Ansuns\Pay\Gateways\Chuanhua;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Gateways\Chuanhua;
use Ansuns\Pay\Service\ToolsService;

/**
 * 人脸支付
 * Class Facepay
 * @package Pay\Gateways\Lianfutong
 */
class Facepay extends Chuanhua
{
    public function get_wxpayface_authinfo(array $options)
    {
        $this->service = "/openapi/merchant/pay/facepay-getauthinfo";
        $this->setConfig([
            'institution_mark' => 1,
            'ordNo' => $options['out_trade_no'] ?? ToolsService::get_bill_number(),
            //'subAppId'     => $options['appid'], //	子商户公众账号ID(服务商模式)
            'wx_sub_appid' => $this->userConfig->get('wx_sub_appid'),//必传
            'store_id' => isset($options['store_id']) ? $options['store_id'] : 1, //门店编号， 由商户定义， 各门店唯一。
            'store_name' => isset($options['store_name']) ? $options['store_name'] : 1, //门店编号， 由商户定义， 各门店唯一。
            'device_id' => isset($options['device_id']) ? $options['device_id'] : 1, //门店编号， 由商户定义， 各门店唯一。
            //'attach'       => '{}',
            'raw_data' => $options['rawdata']
        ]);
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            $data = [
                'authinfo' => $result['authinfo'],
                'expires_in' => (int)$result['expires_in'],
            ];
            return $data;
        }
        return $result;
    }

    /**
     * 发起支付
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function apply(array $options)
    {
        $this->service = '/openapi/merchant/pay/micropay';
        $auth_code = $options['auth_code'];
        $this->setConfig([
                'auth_code' => $auth_code,
                'out_trade_no' => $options['out_trade_no'],
                'body' => $options['body'],
                'store_id' => 'null',
                'terminal_id' => 'null',
                'client_ip' => ToolsService::getClientIp(),
                'total_fee' => ToolsService::ncPriceFen2yuan($options['total_fee']),
            ]
        );
        if (preg_match("/^(10|11|12|13|14|15)\d{16}$/", $auth_code)) {
            $this->setConfig(['channel' => 'WECHAT_POS']);//10~15开头18位 微信付款码
        } elseif (preg_match("/^28\d{15,16}$/", $auth_code)) {
            $this->setConfig(['channel' => 'ALIPAY_POS']); //17位是分期码 28开头 18位支付宝付款码
        } else {
            throw new \Exception('付款码格式有误,请核实是否微信或支付宝付款码!');
        }
        $result = $this->getResult();
        if ($this->isSuccess($result) && $result['trade_status'] == 1) {
            return $this->buildPayResult($result, $options);
        }
        $result['trade_state'] = ($result['trade_status'] == 2) ? 'USERPAYING' : 'PAYERROR'; //只要不是支付中,则认为支付失败
        return $result;
    }

    /**
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }
}