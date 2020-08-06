<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Service\AesService;
use Ansuns\Pay\Service\ToolsService;

/**
 * 微信支付基础类
 * Class Wechat
 * @package Pay\Gateways\Wechat
 */
abstract class Ruiyinxin extends GatewayInterface
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
    protected $gateway = 'http://wxtest.ruishangtong.com/ydzf/ydzf-smzf';

    /**
     * @var string
     */
    protected $gateway_query = 'https://api.mch.weixin.qq.com/pay/orderquery';

    /**
     * Wechat constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);
        $reqMsgId = $this->createNonceStr(32);//请求流水号（订单号）
        $this->config = [
            "cooperator" => "R_SMZF_HBKY",//合作方标识
            "signData" => '',//请求报文签名
            "tranCode" => "SMZF002",//交易服务码
            "callBack" => "http://58.56.27.134:8086/smshmn/callback.jsp",//回调地址（查询类交易可以不送）
            //加密后的 AES 对称密钥：用smzfPubKey加密cooperatorAESKey
            "encryptKey" => AesService::encrypt($this->userConfig->get('cooperatorAESKey'), $this->userConfig->get('smzfPubKey')),
            'reqMsgId' => $reqMsgId,//请求流水号（订单号）
            //加密后的请求报文
            "encryptData" => [
                'version' => '1.0.0',
                'msgType' => '01',
                'reqDate' => date('YmdHis'),
                'data' => []
            ],
            "ext" => [],//备用域
        ];


    }

    /**
     * 设置请求数据
     * @param array $array
     * @return $this
     */
    protected function setReqData($array)
    {
        $this->config['encryptData']['data'] += $array;
        return $this;
    }

    /**
     * 获取验证访问数据
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {
        file_put_contents('./result.txt', json_encode([7777, $this->config], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        $this->config['signData'] = $this->getSign(json_encode($this->config['encryptData'], JSON_UNESCAPED_UNICODE));
        $this->config['encryptData'] = AesService::encrypt(json_encode($this->config['encryptData'], JSON_UNESCAPED_UNICODE), $this->userConfig->get('cooperatorAESKey'));
        $url = $this->gateway;
        $header = ['Content-Type: application/json'];
        $result = $this->post($url, json_encode($this->config, JSON_UNESCAPED_UNICODE), ['headers' => $header]);
        file_put_contents('./result.txt', json_encode([$this->config, $result], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);
        if (!empty($result['sign']) && !$this->verify($this->getSignContent($result), $result['sign'], $this->userConfig->get('sxf_pub_key'))) {
            throw new GatewayException('验证签名失败', 20000, $result);
        }
        //错误示例1{"msg":"subOpenId or subAppid is empty","code":"SXF0002"}
        //错误示例2{"msg":"操作成功","code":"SXF0000","sign":"XX","respData":{"bizMsg":"交易失败，请联系客服","bizCode":"2010","uuid":"093755621dc64ce0b3cfda3c335a83e5"},"signType":"RSA","orgId":"21561002","reqId":"3FQillJLkDGMxngvRKfbYo3kccuBIGYy"}
        $response_data = $result['respData'] ?? $result;
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['msg']) ? $response_data['msg'] : 'OK!';
        if (!isset($result['code']) || $result['code'] !== 'SXF0000' || (isset($response_data['bizCode']) && $response_data['bizCode'] !== '0000')) {
            $response_data['result_code'] = 'FAIL';
            $err_code_des = 'ERROR_MSG:' . (isset($result['msg']) ? $result['msg'] : '');
            $err_code_des .= isset($result['code']) ? ';ERROR_CODE:' . $result['code'] : '';
            $err_code_des .= isset($response_data['bizCode']) ? ';ERROR_SUB_CODE:' . $response_data['bizCode'] : '';
            $err_code_des .= isset($response_data['bizMsg']) ? ';ERROR_SUB_MSG:' . $response_data['bizMsg'] : '';
            $err_code = isset($response_data['bizCode']) ? $response_data['bizCode'] : 'FAIL';
            if (isset($response_data['msg']) && (strpos($response_data['msg'], 'ordNo不能重复') !== false)) {
                //针对商城特殊判断返回
                // {"msg":"ordNo不能重复","code":"SXF0002"}
                $err_code = 'INVALID_REQUEST';
            }
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

    /**
     * 生成内容签名
     * @param $data
     * @return string
     */
    protected function getSign($data)
    {
        if (is_null($this->userConfig->get('cooperatorPriKey'))) {
            throw new InvalidArgumentException('Missing Config -- [cooperatorPriKey]');
        }
        $privateKey = $this->userConfig->get('cooperatorPriKey');
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        $key = openssl_get_privatekey($privateKey);
        openssl_sign($data, $signature, $key);
        openssl_free_key($key);
        $sign = base64_encode($signature);
        return $sign;
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
