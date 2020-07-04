<?php


namespace Ansuns\Pay\Gateways\Sandpay;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\Sandpay;

/**
 * 人脸支付
 * Class Facepay
 * @package Pay\Gateways\Lianfutong
 */
class Facepay extends Sandpay
{

    public function get_wxpayface_authinfo(array $options)
    {
        $this->service = "/qr/getAuthInfo";
        $this->setReqData([
            'merchantCode' => $this->userConfig->get('merchant_no'),
            'ordNo' => $options['out_trade_no'] ?? ToolsService::get_bill_number(),
            //'subAppid'     => '',//选选
            'subMchId' => $this->userConfig->get('sub_mch_id'),//必传
            'storeId' => isset($options['store_id']) ? $options['store_id'] : 1, //门店编号， 由商户定义， 各门店唯一。
            'storeName' => isset($options['store_name']) ? $options['store_name'] : 1, //门店编号， 由商户定义， 各门店唯一。
            'wxTrmNo' => isset($options['device_id']) ? $options['device_id'] : 1, //门店编号， 由商户定义， 各门店唯一。
            //'attach'       => '{}',
            'rawdata' => $options['rawdata']
        ]);
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
        $this->service = '/qr/reverseScan';
        $auth_code = $options['auth_code'] ?? $options['face_code'];
        $this->setReqData([
                'authCode' => $auth_code,
                'ordNo' => $options['out_trade_no'],
                'subject' => $options['body'],
                'amt' => ToolsService::ncPriceFen2yuan($options['total_fee']),
            ]
        );
        if (preg_match("/^(10|11|12|13|14|15)\d{16}$/", $auth_code)) {
            $this->setReqData(['payType' => 'WECHAT']);//10~15开头18位 微信付款码
        } elseif (preg_match("/^28\d{15,16}$/", $auth_code)) {
            $this->setReqData(['payType' => 'ALIPAY']); //17位是分期码 28开头 18位支付宝付款码
        } else {
            throw new \Exception('付款码格式有误,请核实是否微信或支付宝付款码!');
        }
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            return $this->buildPayResult($result);
        }
        $result['trade_state'] = ($result['err_code'] == '2068') ? 'USERPAYING' : 'PAYERROR'; //只要不是支付中,则认为支付失败
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