<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Service\ToolsService;
use Exception;

/**
 *
 * Class Chuanhua
 * @package Pay\Gateways\Chuanhua
 */
abstract class Chuanhua extends GatewayInterface
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
    protected $gateway = 'https://newretail.tf56pay.com/newRetail';

    /**
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);
        if (is_null($this->userConfig->get('mch_id'))) {
            throw new InvalidArgumentException('Missing Config -- [mch_id]');
        }
        if (is_null($this->userConfig->get('md5_key'))) {
            throw new InvalidArgumentException('Missing Config -- [md5_key]');
        }
        if (!empty($config['cache_path'])) {
            HttpService::$cachePath = $config['cache_path'];
        }
        $this->config = [
            'mch_id' => $this->userConfig->get('mch_id', ''),
            'sub_mch_id' => $this->userConfig->get('sub_mch_id', ''),
            'nonce_str' => $this->createNonceStr(32),
            //'sign_type'  => 'MD5',
            'sign_type' => 'RSA',
            'timestamp' => date('YmdHis'),
        ];
    }

    /**
     * 设置请求数据
     * @param array $array
     * @return $this
     */
    protected function setConfig($array)
    {
        $this->config += $array;
        return $this;
    }

    /**
     * 获取验证访问数据
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {
        $this->config['sign'] = $this->getSign($this->config);
        $url = $this->gateway . $this->service;
        $header = ['Content-Type: application/json'];
        $result = $this->post($url, json_encode($this->config, JSON_UNESCAPED_UNICODE), ['headers' => $header]);
        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);
        $response_data = isset($result['data']) ? $result['data'] : $result;
        if (!empty($response_data) && !empty($response_data['sign']) && !$this->verify($response_data, $response_data['sign'], $response_data['sign_type'])) {
            throw new GatewayException('验证签名失败', 20000, $result);
        }
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['msg']) ? $response_data['msg'] : 'OK!';
        if (!isset($result['code']) || $result['code'] !== 0) {
            $response_data['result_code'] = 'FAIL';
            $err_code_des = 'ERROR_MSG:' . (isset($result['msg']) ? $result['msg'] : '');
            $err_code_des .= isset($result['code']) ? ';ERROR_CODE:' . $result['code'] : '';
            $err_code_des .= isset($response_data['code']) ? ';ERROR_SUB_CODE:' . $response_data['code'] : '';
            $err_code_des .= isset($response_data['msg']) ? ';ERROR_SUB_MSG:' . $response_data['msg'] : '';
            $err_code = isset($response_data['code']) ? $response_data['code'] : 'FAIL';
            $response_data['err_code'] = $err_code;
            $response_data['err_code_des'] = $err_code_des;
        }
        return $response_data;
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
        $this->config['refundAmount'] = ToolsService::ncPriceFen2yuan($options['refund_fee']); //单位分转成元
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
                'refund_fee' => ToolsService::ncPriceYuan2fen($data['refundAmount']),  //元转分
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
                'refund_fee' => ToolsService::ncPriceYuan2fen($data['refundAmount']),  //元转分
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
     * 查询订单状态OK
     * @param string $out_trade_no
     * @return array
     * @throws GatewayException
     */
    public function find($out_trade_no = '')
    {
        $this->service = "/openapi/merchant/pay/query";
        $this->config['date'] = '2019-12-31';
        $this->config['out_trade_no'] = $out_trade_no;
        $data = $this->getResult();
        if ($this->isSuccess($data)) {
            return $this->buildPayResult($data);
        }
        $trade_state = $data['tranSts'] ?? 'FAIL';
        $data['trade_state'] = ($trade_state == 'USER_PAYING') ? 'USERPAYING' : $trade_state;
        return $data;
    }

    protected function buildPayResult($data, $options = [])
    {
        return [
            'return_code' => $data['return_code'], //通信结果
            'return_msg' => $data['return_msg'],
            'result_code' => $data['result_code'],
            'appid' => isset($data['channel_no']) ? $data['channel_no'] : '',
            'mch_id' => isset($data['mch_id']) ? $data['mch_id'] : '',
            'device_info' => '',
            'nonce_str' => isset($data['nonce_str']) ? $data['nonce_str'] : '',
            'sign' => isset($data['sign']) ? $data['sign'] : '',
            'openid' => isset($data['user_info']) ? $data['user_info'] : '',
            'is_subscribe' => '',
            'trade_type' => isset($data['channel']) ? $data['channel'] : '',
            'bank_type' => '',
            'total_fee' => ToolsService::ncPriceYuan2fen($data['total_fee'] ?? $options['total_fee']),  //分
            'transaction_id' => isset($data['billno']) ? $data['billno'] : '',
            'out_trade_no' => isset($data['out_trade_no']) ? $data['out_trade_no'] : '',
            'attach' => '',
            //'time_end'       => ToolsService::format_time($data['payTime']),
            'time_end' => isset($data['paid_at']) ? $data['paid_at'] : '',
            'trade_state' => (isset($data['trade_status']) && $data['trade_status'] == 1) ? 'SUCCESS' : 'PAYERROR',
            'raw_data' => $data
        ];
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
        return json_encode(['code' => 'success', 'msg' => '成功']);
    }

    /**
     * 返回失败通知
     * @return string
     */
    public function getNotifyFailedReply()
    {
        return json_encode(['code' => 'fail', 'msg' => '失败']);
    }

    /**
     * 获取微信支付通知
     * @return array
     * @throws Exception
     */
    public function getNotify()
    {
        $data = $_POST;
        if (!empty($data['sign'])) {
            $sign = $data['sign'];
            unset($data['sign']);
            $local_sign = $this->getSign($data);
            if ($local_sign !== $sign) {
                throw new Exception('Invalid Notify Sign is error.', '0');
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
                'total_fee' => ToolsService::ncPriceYuan2fen($data['amt']),  //分
                'transaction_id' => $data['transactionId'],
                'out_trade_no' => $data['ordNo'],
                'attach' => '',
                //'time_end'       => ToolsService::format_time($data['payTime']),
                'time_end' => $data['payTime'],
                'trade_state' => 'SUCCESS',
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

    /**验签
     * @param array $data 待签名数据
     * @param string|null $sign 需要验签的签名
     * @param string $encrypt_method 签名类型
     * @return bool 验签是否通过 bool值
     */
    public function verify($data, $sign = null, $encrypt_method = 'MD5')
    {
        return $this->getSign($data, $encrypt_method) === $sign;
    }

    /**
     * 生成内容签名
     * @param $data
     * @param $encrypt_method
     * @return string
     */
    protected function getSign($data, $encrypt_method = '')
    {
        ksort($data);
        $encrypt_method = $encrypt_method ?: $this->config['sign_type'];
        switch (strtoupper($encrypt_method)) {
            case 'MD5':
                if (is_null($this->userConfig->get('md5_key'))) {
                    throw new InvalidArgumentException('Missing Config -- [md5_key]');
                }
                $string = strtoupper(md5($this->getSignContent($data) . '&key=' . $this->userConfig->get('md5_key')));
                break;
            case 'RSA':
                if (is_null($this->userConfig->get('private_key'))) {
                    throw new InvalidArgumentException('Missing Config -- [private_key]');
                }
                $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                    wordwrap($this->userConfig->get('private_key'), 64, "\n", true) .
                    "\n-----END RSA PRIVATE KEY-----";
                openssl_sign($this->getSignContent($data), $sign, $res, OPENSSL_ALGO_SHA256);
                $string = base64_encode($sign);
                break;
            default:
                throw new InvalidArgumentException('not support function:' . $encrypt_method);
        }
        return $string;
    }

    /**
     * 生成签名内容
     * @param array $data
     * @return string
     */
    private function getSignContent($data)
    {
        ksort($data);
        $buff = '';
        foreach ($data as $k => $v) {
            $buff .= ($k != 'sign' && $v != '' && !is_array($v)) ? $k . '=' . $v . '&' : '';
        }
        return trim($buff, '&');
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
