<?php


namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Gateways\Wechat;

/**
 * 人脸支付
 * Class Facepay
 * @package Pay\Gateways\Wechat
 */
class Facepay extends Wechat
{

    /**
     * @var string
     */
    protected $gateway = 'https://api.mch.weixin.qq.com/pay/facepay';
    /**
     * @var string
     */
    protected $gateway_get_wxpayface_authinfo = 'https://payapp.weixin.qq.com/face/get_wxpayface_authinfo';

    /**
     * @var string
     */
    protected $gateway_query = 'https://api.mch.weixin.qq.com/pay/facepayquery';
    /**
     * @var string
     */
    protected $gateway_refund_query = 'https://api.mch.weixin.qq.com/pay/facepayquery';

    /**
     * @var string
     */
    protected $gateway_close = 'https://api.mch.weixin.qq.com/secapi/pay/facepayreverse';

    /**
     * @var string
     */
    protected $gateway_refund = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

    /**
     * @var string
     */
    protected $gateway_micropay = 'https://api.mch.weixin.qq.com/pay/micropay';
    /**
     * @var string
     */
    protected $encrypt_method = 'HMAC-SHA256';
    /**
     * @var string
     */
    protected $version = 1;

    protected function init(array $options)
    {
        $this->unsetTradeTypeAndNotifyUrl();
        $this->unsetSpbillCreateIp();
        $this->config['sub_mch_id'] = isset($options['sub_mch_id']) ? $options['sub_mch_id'] : $this->userConfig->get('sub_mch_id');
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['version'] = $this->version;
        $this->config['now'] = time();
    }

    public function get_wxpayface_authinfo(array $options)
    {
        $this->init($options);
        //$this->config['attach']     = '{}'; //附加字段。字段格式使用Json
        $this->config['store_id'] = isset($options['store_id']) ? $options['store_id'] : 1; //门店编号， 由商户定义， 各门店唯一。
        $this->config['store_name'] = isset($options['store_name']) ? $options['store_name'] : 1; //门店名称，由商户定义。（可用于展示）
        $this->config['device_id'] = isset($options['device_id']) ? $options['device_id'] : 1; //终端设备编号，由商户定义。
        $this->config['rawdata'] = isset($options['rawdata']) ? $options['rawdata'] : 'H0kvnUgGHKuqflNwtNqCdOVpbO4Fd4u2NRS2uJz5/n080cOlYF5nNnuyVc+UsX0+q3nVrEYAhJFyxeG8MBx/cmZSicjI8UipaehhfFiIHnBZndrCSeGizNs6PSowudTG';
        $result = $this->getResult($this->gateway_get_wxpayface_authinfo);
        if (is_array($result) && isset($result['sub_appid']) && is_array($result['sub_appid']) && $result['sub_appid'] == []) {
            $result['sub_appid'] = '';
        }
        if (is_array($result) && isset($result['sub_mch_id']) && is_array($result['sub_mch_id']) && $result['sub_mch_id'] == []) {
            $result['sub_mch_id'] = '';
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
        $this->config = array_merge($this->config, $options);
        $this->unsetTradeTypeAndNotifyUrl();
        $result = $this->getResult($this->gateway);
        if (!$this->isSuccess($result)) {
            $result['trade_state'] = ($result['err_code'] == 'USERPAYING') ? $result['err_code'] : 'PAYERROR'; //只要不是支付中,则认为支付失败
        }
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