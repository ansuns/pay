<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;

/**
 * 微信支付基础类
 * Class Wechat
 * @package Pays\Gateways\Wechat
 */
abstract class Lianfutong extends GatewayInterface
{

    /**
     * @var array
     */
    protected $config;
    /**
     * @var string
     */
    protected $service;
    /**
     * @var Config
     */
    protected $userConfig;

    /**
     * @var string
     */
    protected $gateway = 'https://api.liantuofu.com/open';

    /**
     * @var string
     */
    protected $gateway_query = 'https://api.mch.weixin.qq.com/pay/orderquery';

    /**
     * Wechat constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);
        if (is_null($this->userConfig->get('core_merchant_no'))) {
            throw new InvalidArgumentException('Missing Config -- [core_merchant_no]');
        }
        if (is_null($this->userConfig->get('merchant_no'))) {
            throw new InvalidArgumentException('Missing Config -- [merchant_no]');
        }
        if (is_null($this->userConfig->get('partner_key'))) {
            throw new InvalidArgumentException('Missing Config -- [partner_key]');
        }
        if (!empty($config['cache_path'])) {
            HttpService::$cachePath = $config['cache_path'];
        }
        $this->config = [
            'appId' => $this->userConfig->get('core_merchant_no', ''),
            'random' => $this->createNonceStr(),
        ];
    }

    /**
     * 订单退款操作
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function refund($options = [])
    {
        $this->service = "/refund";
        $this->config['outTradeNo'] = $options['out_trade_no'];
        $this->config['refundNo'] = $options['out_refund_no'];
        $this->config['refundAmount'] = tools()::ncPriceFen2yuan($options['refund_fee']); //单位分转成元
        $this->config['merchantCode'] = $this->userConfig->get('merchant_no');
        $data = $this->getResult();
        if ($this->isSuccess($data)) {
            $return = [
                'return_code' => $data['return_code'], //通信结果
                'return_msg' => $data['return_msg'],
                'result_code' => $data['result_code'],
                'appid' => isset($data['appId']) ? $data['appId'] : '',
                'mch_id' => '',
                'nonce_str' => isset($data['nonceStr']) ? $data['nonceStr'] : '',
                'sign' => isset($data['sign']) ? $data['sign'] : '',
                'out_refund_no' => $data['refundNo'],
                'out_trade_no' => $data['outTradeNo'],
                'refund_id' => '',
                'transaction_id' => '',
                'refund_fee' => tools()::ncPriceYuan2fen($data['refundAmount']),  //元转分
                'raw_data' => $data
            ];
            return $return;
        }
        return $data;
    }

    /**
     * 查询退款订单状态
     * @param string $out_trade_no
     * @return array
     * @throws GatewayException
     */
    public function refund_find($out_trade_no = '')
    {
        $this->service = "/refund/query";
        $this->config['refundNo'] = $out_trade_no;
        $this->config['merchantCode'] = $this->userConfig->get('merchant_no');
        $data = $this->getResult();
        if ($this->isSuccess($data)) {
            $return = [
                'return_code' => $data['return_code'], //通信结果
                'return_msg' => $data['return_msg'],
                'result_code' => $data['result_code'],
                'appid' => isset($data['appId']) ? $data['appId'] : '',
                'mch_id' => '',
                'nonce_str' => isset($data['nonceStr']) ? $data['nonceStr'] : '',
                'sign' => isset($data['sign']) ? $data['sign'] : '',
                'out_refund_no' => $data['refundNo'],
                'out_trade_no' => $data['outTradeNo'],
                'refund_id' => '',
                'transaction_id' => '',
                'refund_fee' => tools()::ncPriceYuan2fen($data['refundAmount']),  //元转分
                'raw_data' => $data
            ];
            return $return;
        }
        return $data;
    }

    /**
     * 关闭正在进行的订单
     * @param string $out_trade_no
     * @param string $reason
     * @return array
     * @throws GatewayException
     */
    public function close($out_trade_no = '', $reason = '')
    {
        $this->service = "/close";
        $this->config['outTradeNo'] = $out_trade_no;
        $this->config['reason'] = $reason;
        $this->config['merchantCode'] = $this->userConfig->get('merchant_no');
        return $this->getResult();
    }

    /**
     * 查询订单状态
     * @param string $out_trade_no
     * @return array
     * @throws GatewayException
     */
    public function find($out_trade_no = '')
    {
        $this->service = "/pay/query";
        $this->config['outTradeNo'] = $out_trade_no;
        $this->config['merchantCode'] = $this->userConfig->get('merchant_no');
        $data = $this->getResult();
        if ($this->isSuccess($data)) {
            return $this->buildPayResult($data);
        }
        if ($data['err_code'] == 'USER_PAYING') {
            $data['trade_state'] = 'USERPAYING';
        } else {
            $data['trade_state'] = isset($data['orderStatus']) ? $data['orderStatus'] : 'FAIL';
        }
        return $data;
    }

    protected function buildPayResult($data)
    {
        $return = [
            'return_code' => $data['return_code'], //通信结果
            'return_msg' => $data['return_msg'],
            'result_code' => $data['result_code'],
            'appid' => isset($data['appId']) ? $data['appId'] : '',
            'mch_id' => '',
            'device_info' => '',
            'nonce_str' => isset($data['nonceStr']) ? $data['nonceStr'] : '',
            'sign' => isset($data['sign']) ? $data['sign'] : '',
            'openid' => isset($data['buyerId']) ? $data['buyerId'] : '',
            'is_subscribe' => '',
            'trade_type' => isset($data['payType']) ? $data['payType'] : '',
            'bank_type' => '',
            'total_fee' => tools()::ncPriceYuan2fen($data['totalAmount']),  //分
            'transaction_id' => isset($data['transactionId']) ? $data['transactionId'] : '',
            'out_trade_no' => isset($data['outTradeNo']) ? $data['outTradeNo'] : '',
            'attach' => '',
            //'time_end'       => tools()::format_time($data['payTime']),
            'time_end' => isset($data['payTime']) ? $data['payTime'] : '',
            'trade_state' => isset($data['orderStatus']) ? $data['orderStatus'] : '',
            'raw_data' => $data
        ];
        return $return;
    }

    /**
     * XML内容验证
     * @param string $data
     * @param null $sign
     * @param bool $sync
     * @return array|bool
     */
    public function verify($data, $sign = null, $sync = false)
    {
        $sign = is_null($sign) ? $data['sign'] : $sign;
        return $this->getSign($data) === $sign ? $data : false;
    }

    /**
     * @return mixed
     */
    abstract protected function getTradeType();

    /**
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    protected function preOrder($options = [])
    {
        $this->config = array_merge($this->config, $options);
        return $this->getResult();
    }

    /**
     * 获取验证访问数据
     * @param bool $cert
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {
        $this->config['sign'] = $this->getSign($this->config);
        $url = $this->gateway . $this->service;
        $result = $this->post($url, $this->config);
        if (!tools()::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);
        $result['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $result['return_msg'] = isset($result['msg']) ? $result['msg'] : 'OK!';
        $result['result_code'] = isset($result['code']) ? $result['code'] : 'FAIL';
        if (isset($result['code']) && $result['code'] !== 'SUCCESS') {
            $err_code_des = 'ERROR_MSG:' . (isset($result['msg']) ? $result['msg'] : '');
            $err_code_des .= isset($result['code']) ? ';ERROR_CODE:' . $result['code'] : '';
            $err_code_des .= isset($result['subCode']) ? ';ERROR_SUB_CODE:' . $result['subCode'] : '';
            $err_code_des .= isset($result['subMsg']) ? ';ERROR_SUB_MSG:' . $result['subMsg'] : '';
            $err_code = isset($result['subCode']) ? $result['subCode'] : 'FAIL';
            if (isset($result['subMsg']) && (strpos($result['subMsg'], '订单已存在') !== false)) {
                //针对商城特殊判断返回
                // {"code":"FAILED","msg":"支付失败","subCode":"PARAMETER_ERROR","subMsg":"订单已存在，请重新下单"}
                $err_code = 'INVALID_REQUEST';
            }
            $result['err_code'] = $err_code;
            $result['err_code_des'] = $err_code_des;
        }
        return $result;
    }

    /**
     * 判断结果是否成功
     * @param $result
     * @return bool
     */
    protected function isSuccess($result)
    {
        if (!is_array($result)) {
            return false;
        }
        return isset($result['return_code']) && ($result['return_code'] === 'SUCCESS') && isset($result['result_code']) && ($result['result_code'] === 'SUCCESS');
    }

    /**
     * 获取微信支付成功通知回复内容
     * @return string
     */
    public function getNotifySuccessReply()
    {
        return 'success';
    }

    /**
     * 返回失败通知
     * @return string
     */
    public function getNotifyFailedReply()
    {
        return 'fail';
    }

    /**
     * 获取微信支付通知
     * @return array
     * @throws \Exception
     */
    public function getNotify()
    {
        $data = $_POST;
        if (!empty($data['sign'])) {
            $sign = $data['sign'];
            unset($data['sign']);
            $local_sign = $this->getSign($data);
            if ($local_sign !== $sign) {
                wr_log('sign is not match:local_sign:' . $local_sign . ',get_sign:' . $sign, 1);
                throw new \Exception('Invalid Notify Sign is error.', '0');
            }
            $return = [
                'return_code' => 'SUCCESS', //通信结果
                'return_msg' => $data['msg'],
                'result_code' => $data['code'],
                'appid' => '',
                'mch_id' => '',
                'device_info' => '',
                'nonce_str' => '',
                'sign' => $sign,
                'openid' => $data['buyerId'],
                'is_subscribe' => '',
                'trade_type' => $data['payType'],
                'bank_type' => '',
                'total_fee' => tools()::ncPriceYuan2fen($data['totalAmount']),  //分
                'transaction_id' => $data['transactionId'],
                'out_trade_no' => $data['outTradeNo'],
                'attach' => '',
                //'time_end'       => tools()::format_time($data['payTime']),
                'time_end' => $data['payTime'],
                'trade_state' => $data['orderStatus'],
                'raw_data' => $data
            ];
            if ($data['code'] !== 'SUCCESS') {
                $return['err_code'] = isset($data['subCode']) ? $data['subCode'] : '';
                $return['err_code_des'] = isset($data['subMsg']) ? $data['subMsg'] : '';
            }
            return $return;
        }
        exit();
    }

    /**
     * 生成内容签名
     * @param $data
     * @return string
     */
    protected function getSign($data)
    {
        if (is_null($this->userConfig->get('partner_key'))) {
            throw new InvalidArgumentException('Missing Config -- [partner_key]');
        }
        ksort($data);
        $string = md5($this->getSignContent($data) . '&key=' . $this->userConfig->get('partner_key'));
        return strtolower($string);
    }

    /**
     * 生成签名内容
     * @param $data
     * @return string
     */
    private function getSignContent($data)
    {
        $str = '';
        foreach ($data as $key => $val) {
            if ($val != null && $val !== '' && $key != 'key' && $key != 'sign_type') {
                $str .= $key . "=" . $val . "&";
            }
        }
        return rtrim($str, '&');
    }

    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    protected function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 清理签名验证不必要的参数
     * @return bool
     */
    protected function unsetTradeTypeAndNotifyUrl()
    {
        unset($this->config['notify_url']);
        unset($this->config['trade_type']);
        return true;
    }
}
