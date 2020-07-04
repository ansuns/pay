<?php


namespace Ansuns\Pay\Gateways\Lianfutong;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\Lianfutong;

/**
 * 人脸支付
 * Class Facepay
 * @package Pay\Gateways\Lianfutong
 */
class Facepay extends Lianfutong
{
    /**
     * 获取人脸授权信息
     * @param array $options
     * @return array
     * @throws Exception
     * @throws GatewayException
     */
    public function get_wxpayface_authinfo(array $options)
    {
        $this->service = "/facePayAuth";
        $data = [
            'merchantCode' => $this->userConfig->get('merchant_no'),
            'outTradeNo' => $options['out_trade_no'] ?? ToolsService::get_bill_number(),
            //'subAppId'     => $options['appid'], //	子商户公众账号ID(服务商模式)
            'storeId' => isset($options['store_id']) ? $options['store_id'] : 1, //门店编号， 由商户定义， 各门店唯一。
            'storeName' => isset($options['store_name']) ? $options['store_name'] : 1, //门店编号， 由商户定义， 各门店唯一。
            'deviceId' => isset($options['device_id']) ? $options['device_id'] : 1, //门店编号， 由商户定义， 各门店唯一。
            //'attach'       => '{}',
            'rawdata' => $options['rawdata']
        ];
        $this->config = array_merge($this->config, $data);
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            $data = [
                'authinfo' => $result['authInfo'],
                'expires_in' => (int)$result['expiresIn'],
            ];
            return $data;
        } else {
            throw new \Exception($result['return_msg'] ?? json_encode($result, JSON_UNESCAPED_UNICODE));
        }
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
        $this->service = "/facePay";
        $auth_code = $options['auth_code'] ?? $options['face_code'];
        $data = [
            'merchantCode' => $this->userConfig->get('merchant_no'),
            'subject' => $options['body'],
            'outTradeNo' => $options['out_trade_no'],
            'totalAmount' => ToolsService::ncPriceFen2yuan($options['total_fee']),
            'openId' => $options['openid'],
            'authCode' => $auth_code,
            // 'subAppId'=> $options['appid']
        ];
        $sub_appid = $this->userConfig->get('sub_appid');
        if ($sub_appid) {
            $data['subAppId'] = $sub_appid;
        }
        $this->config = array_merge($this->config, $data);
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            return $this->buildPayResult($result);
        }
        $result['trade_state'] = ($result['err_code'] == 'USERPAYING') ? $result['err_code'] : 'PAYERROR'; //只要不是支付中,则认为支付失败
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