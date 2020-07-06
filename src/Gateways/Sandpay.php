<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信支付基础类
 * Class Wechat
 * @package Pay\Gateways\Wechat
 */
abstract class Sandpay extends GatewayInterface
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
    protected $gateway = 'https://hmpay.sandpay.com.cn/gateway_t/api';

    /**
     * @var string
     */
    protected $gateway_query = 'https://api.mch.weixin.qq.com/pay/orderquery';

    protected $privateKey = 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBANFqaISzzjv89+38z7EHn9PDG6I5jNUQWHT/NDjgo4qNiZvQLthlbpNILMGJ3KwqI2TDyojRhwMNABLhzmYoSTlYs3NkowRu8S/L7FSDu11kFRqSU/Ox7tsYIMJALDOXV1eBZ5mEfqz28YJA8vouaT283JMaoqbavGK0N7i7D1BZAgMBAAECgYEAuIEk9w4oPSgjFJYyMsoB4iQ7m5FS6IHPPb1/uEELNc6AGDyymUu8wZzMefRJ7ZHuvx/VuPfKGUEB+KDkJZOG9pwXdAu6hLJS7iUpaK/JbS0DqOtRlFHnNft4YD50tWp/dZr7zNEvcwkRbS5OZJDRZ3IOCf76Z/q7vXimm27eL5UCQQDzL5xRGb0kddBkGjzMQ7IJ04hl2VkaaHBkqLrmIm9OxTeA+0tH17+AXvr2xbhXb4sOqUmmThB3YCTqowDD6NHDAkEA3HNDKFhTR7MEQCDy/OkJVFaIhr6gKavvMicYQx3fprAIlx+cG8xHlR4maHkxHqdEeP/hFCe5YJ3+uy0EPAF3swJBAINEv+xHKIH11ncycn8QS5piRM41dJN8rK6pJbnz/IFYk41cGFa/bu+sVWu/brJD05wmZUsP+HN3wnWlZ1RY6GECQG10AQUYDYlM1bBta5essIgiSrj0DpuCFUoGZSJ1w6SERE+cTyryGxxrktBOU9gPXozhJsSWEJFrAJ24dSDB7ccCQQCWX34oHvbVzLrsfCnQSRDP4HvFoJwQLHTDcP5ujvUnWd8rUNkflxt7Gfgr7sO5dMksMurGxSx4jcZr7TwE77Z8';

    /**
     * Wechat constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);
        if (is_null($this->userConfig->get('org_id'))) {
            //throw new InvalidArgumentException('Missing Config -- [org_id]');

        }
        if (is_null($this->userConfig->get('mno'))) {
            //throw new InvalidArgumentException('Missing Config -- [mno]');

        }
        if (is_null($this->userConfig->get('sxf_pub_key'))) {
            //throw new InvalidArgumentException('Missing Config -- [sxf_pub_key]');

        }
        if (is_null($this->userConfig->get('cooprator_pri_key'))) {
            //throw new InvalidArgumentException('Missing Config -- [cooprator_pri_key]');
            
        }
        if (is_null($this->userConfig->get('cooprator_pub_key'))) {
            //throw new InvalidArgumentException('Missing Config -- [cooprator_pub_key]');
        }
        if (!empty($config['cache_path'])) {
            HttpService::$cachePath = $config['cache_path'];
        }
        $this->userConfig['private_key'] = 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBANFqaISzzjv89+38z7EHn9PDG6I5jNUQWHT/NDjgo4qNiZvQLthlbpNILMGJ3KwqI2TDyojRhwMNABLhzmYoSTlYs3NkowRu8S/L7FSDu11kFRqSU/Ox7tsYIMJALDOXV1eBZ5mEfqz28YJA8vouaT283JMaoqbavGK0N7i7D1BZAgMBAAECgYEAuIEk9w4oPSgjFJYyMsoB4iQ7m5FS6IHPPb1/uEELNc6AGDyymUu8wZzMefRJ7ZHuvx/VuPfKGUEB+KDkJZOG9pwXdAu6hLJS7iUpaK/JbS0DqOtRlFHnNft4YD50tWp/dZr7zNEvcwkRbS5OZJDRZ3IOCf76Z/q7vXimm27eL5UCQQDzL5xRGb0kddBkGjzMQ7IJ04hl2VkaaHBkqLrmIm9OxTeA+0tH17+AXvr2xbhXb4sOqUmmThB3YCTqowDD6NHDAkEA3HNDKFhTR7MEQCDy/OkJVFaIhr6gKavvMicYQx3fprAIlx+cG8xHlR4maHkxHqdEeP/hFCe5YJ3+uy0EPAF3swJBAINEv+xHKIH11ncycn8QS5piRM41dJN8rK6pJbnz/IFYk41cGFa/bu+sVWu/brJD05wmZUsP+HN3wnWlZ1RY6GECQG10AQUYDYlM1bBta5essIgiSrj0DpuCFUoGZSJ1w6SERE+cTyryGxxrktBOU9gPXozhJsSWEJFrAJ24dSDB7ccCQQCWX34oHvbVzLrsfCnQSRDP4HvFoJwQLHTDcP5ujvUnWd8rUNkflxt7Gfgr7sO5dMksMurGxSx4jcZr7TwE77Z8';
        $this->config = [
            'app_id' => '663101000017582',
            'sub_app_id' => '',
            'timestamp' => date('Y-m-d H:i:s'),
            'format' => 'JSON',
            'charset' => 'UTF-8',
            'sign_type' => 'RSA',
            'nonce' => $this->createNonceStr(),
            'version' => '1.0',
            'biz_content' => [],
        ];
    }

    /**
     * 设置请求数据
     * @param array $array
     * @return $this
     */
    protected function setReqData($array)
    {
        $this->config['biz_content'] += $array;
        return $this;
    }

    protected function curl($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $headers = array('content-type: application/json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $reponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);
        return $reponse;
    }

    /**
     * 获取验证访问数据
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {
        $this->config['method'] = $this->getTradeType();
        $this->config['biz_content'] = json_encode($this->config['biz_content'], JSON_UNESCAPED_UNICODE);
        $this->config['sign'] = $this->rsaSign($this->config, $this->userConfig['private_key']);
        $url = $this->gateway;
        $header = ['Content-Type: application/json'];
        $result = $this->curl($url, json_encode($this->config, JSON_UNESCAPED_UNICODE), $header);
        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);

//        if (!empty($result['sign']) && !$this->verify($this->getSignContent($result), $result['sign'],$this->userConfig['private_key'])) {
//            throw new GatewayException('验证签名失败', 20000, $result);
//        }
        $response_data = [];
        $response = isset($result['data']) ? json_decode($result['data'], true) : [];
        $response_data = array_merge($response_data, $response);
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['msg']) ? $response_data['msg'] : 'OK!';
        if (!isset($response['sub_code']) || $response['sub_code'] !== 'SUCCESS') {
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = '';
            $response_data['err_code_des'] = '';
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
        $this->service = "/qr/refund";
        $this->setReqData([
            'ordNo' => $options['out_refund_no'],
            'origOrderNo' => $options['out_trade_no'],
            'amt' => ToolsService::ncPriceFen2yuan($options['refund_fee']),
        ]);
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
                'out_refund_no' => $data['ordNo'],
                'out_trade_no' => $data['origOrderNo'],
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
        $this->service = "/qr/tradeRefundQuery";
        $this->setReqData(['ordNo' => $out_trade_no]);
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
                'out_refund_no' => $data['ordNo'],
                'out_trade_no' => $out_trade_no,
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
        $this->service = "/qr/query";
        $this->setReqData(['ordNo' => $out_trade_no]);
        $data = $this->getResult();
        if ($this->isSuccess($data)) {
            return $this->buildPayResult($data);
        }
        $trade_state = $data['tranSts'] ?? 'FAIL';
        $data['trade_state'] = ($trade_state == 'USER_PAYING') ? 'USERPAYING' : $trade_state;
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
            'total_fee' => ToolsService::ncPriceYuan2fen(isset($data['oriTranAmt']) ? $data['oriTranAmt'] : $data['buyerPayAmount']),  //分
            'transaction_id' => isset($data['transactionId']) ? $data['transactionId'] : '',
            'out_trade_no' => isset($data['ordNo']) ? $data['ordNo'] : '',
            'attach' => '',
            //'time_end'       => ToolsService::format_time($data['payTime']),
            'time_end' => isset($data['payTime']) ? $data['payTime'] : '',
            'trade_state' => isset($data['tranSts']) ? $data['tranSts'] : $data['result_code'],
            'raw_data' => $data
        ];
        return $return;
    }

    /**RSA验签
     * @param array $data 待签名数据
     * @param string|null $sign 需要验签的签名
     * @param bool|string $pub_key
     * @return bool 验签是否通过 bool值
     */
    public function verify($data, $sign = null, $pub_key = false)
    {
        $str = chunk_split($pub_key, 64, "\n");
        $public_key = "-----BEGIN PUBLIC KEY-----\n$str-----END PUBLIC KEY-----\n";
        //转换为openssl格式密钥
        $res = openssl_get_publickey($public_key);
        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $public_key);
        //释放资源
        openssl_free_key($res);
        //返回资源是否成功
        return $result;
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
     * @throws \Exception
     */
    public function getNotify()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!empty($data['sign'])) {
            $sign = $data['sign'];
            unset($data['sign']);
            if (!$this->verify($this->getSignContent($data), $sign, $this->userConfig->get('sxf_pub_key'))) {
                throw new \Exception('Invalid Notify Sign is error.', '0');
            }
            $return = [
                'return_code' => 'SUCCESS', //通信结果
                'return_msg' => $data['bizMsg'],
                'result_code' => $data['bizCode'] == '0000' ? 'SUCCESS' : 'FAIL',
                'appid' => '',
                'mch_id' => '',
                'device_info' => '',
                'nonce_str' => '',
                'sign' => $sign,
                'openid' => $data['buyerId'] ?? $data['openid'],
                'is_subscribe' => '',
                'trade_type' => 'trade_type',
                'bank_type' => '',
                'total_fee' => ToolsService::ncPriceYuan2fen($data['amt']),  //分
                'transaction_id' => $data['transactionId'],
                'out_trade_no' => $data['ordNo'],
                'attach' => '',
                //'time_end'       => ToolsService::format_time($data['payTime']),
                'time_end' => $data['payTime'],
                'trade_state' => ($data['bizMsg'] == '交易成功') ? 'SUCCESS' : 'FAIL',
                'raw_data' => $data
            ];
            if ($data['bizCode'] !== '0000') {
                $return['err_code'] = isset($data['subCode']) ? $data['subCode'] : '';
                $return['err_code_des'] = isset($data['subMsg']) ? $data['subMsg'] : '';
            }
            return $return;
        }
        exit();
    }

    public function rsaSign($params, $signType = "RSA")
    {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function getSignContent($params)
    {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    protected function sign($data, $priKey, $signType = "RSA")
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    protected function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
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
