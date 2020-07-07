<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Service\HttpService;
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

    /**
     * Wechat constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);

        if (is_null($this->userConfig->get('app_id'))) {
            throw new InvalidArgumentException('Missing Config -- [app_id]');
        }
        if (is_null($this->userConfig->get('private_key'))) {
            throw new InvalidArgumentException('Missing Config -- [private_key]');

        }

        $this->config = [
            'app_id' => $this->userConfig->get('app_id'), // 商户支付号 // 代理商
            'sub_app_id' => $this->userConfig->get('sub_app_id'), // 子商户号 //  商户
            'method' => '', // 方法 trade.percreate
            'charset' => 'UTF-8', // 编码
            'sign_type' => 'RSA', // 签名串
            'sing' => '',//  签名串
            'timestamp' => date('Y-m-d H:i:s'),
            'nonce' => $this->createNonceStr(), // 请求端随机生成数
            'version' => '1.0', // 接口版本
            'format' => 'JSON',// 业务参数请求格式
            'biz_content' => [], // 业务参数
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

    /**
     * 获取验证访问数据
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {

        $this->config['biz_content'] = json_encode($this->config['biz_content'], JSON_UNESCAPED_UNICODE);
        $this->config['sign'] = $this->rsaSign($this->config, $this->userConfig['private_key']);
        $header = ['Content-Type: application/json'];
        $result = $this->post($this->gateway, json_encode($this->config, JSON_UNESCAPED_UNICODE), $header);

        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);
        file_put_contents('./result.txt', json_encode([$this->config, $result]) . PHP_EOL, FILE_APPEND);
//        if (!empty($result['sign']) && !$this->verify($this->getSignContent($result), $result['sign'],$this->userConfig['private_key'])) {
//            throw new GatewayException('验证签名失败', 20000, $result);
//        }

        $response_data = [];
        $response = isset($result['data']) ? json_decode($result['data'], true) : [];
        $response_data = array_merge($response_data, $response);

        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = $response_data['msg'] ?? 'OK!';
        $response_data['sub_code'] = $response_data['sub_code'] ?? 'SUCCESS';
        $response_data['sub_msg'] = $response_data['sub_msg'] ?? '交易成功';
        $code = $result['code'] ?? '200';
        if ($code !== '200') {
            $response_data['sub_code'] = $result['sub_code'] ?? 'ERROR';
            $response_data['sub_msg'] = $result['sub_msg'] ?? '失败';
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = 'ERROR';
            $response_data['err_code_des'] = $result['msg'] ?? 'ERROR:交易失败';
            $response_data['return_msg'] = 'ERROR';

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
        // 采用相同的退款订单号退款，仅会成功退款一笔，均返回退款成功
        $this->setReqData([
            'out_order_no' => $options['out_order_no'], // 商户订单号
            'refund_amount' => $options['refund_amount'],//退款金额，单位元
            'refund_request_no' => $options['refund_request_no'],//商户退款请求号，同一订单退款不可重复
            'extend_params' => [
                'reason' => 'order refund'
            ]
        ]);
        $this->config['method'] = "trade.refund";

        // JSON格式，与下游额外约定的特殊参数
        return $this->getResult();
    }


    /**
     * 仅用于退款查询，关单及撤销重新发起即可
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function refund_find($options = [])
    {
        // 采用相同的退款订单号退款，仅会成功退款一笔，均返回退款成功
        $this->setReqData([
            'out_order_no' => $options['out_order_no'], // 商户订单号
            'refund_amount' => $options['refund_amount'],//退款金额，单位元
            'refund_request_no' => $options['refund_request_no'],//商户退款请求号，同一订单退款不可重复
            'extend_params' => [
                'reason' => 'order refund query'
            ]
        ]);
        $this->config['method'] = "trade.refund.query";

        // JSON格式，与下游额外约定的特殊参数
        return $this->getResult();
    }

    /**
     * 仅用于二维码预下单且未支付的订单
     * @param string $out_trade_no
     * @param string $reason
     * @return array
     * @throws GatewayException
     */
    public function close($out_trade_no = '', $reason = '')
    {
        $this->setReqData([
            'out_order_no' => $out_trade_no,
            'extend_params' => [
                'reason' => 'order close'
            ]
        ]);
        $this->config['method'] = "trade.close";

        // JSON格式，与下游额外约定的特殊参数
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
        $this->setReqData([
            'out_order_no' => $out_trade_no,
            'extend_params' => [
                'reason' => 'order query'
            ]
        ]);
        $this->config['method'] = "trade.query";
        $data = $this->getResult();
//        $trade_state = $data['sub_code'] ?? 'FAIL';
//        $data['trade_state'] = ($trade_state == 'USER_PAYING') ? 'USERPAYING' : $trade_state;
        return $data;
    }

    /**
     * @param string $out_trade_no
     * @return array
     * @throws GatewayException
     */
    public function cancel($options = [])
    {
//        $options = [
//            "out_order_no" => "23423423432423432",//订单号三选一必填30商户订单号123456
//            "plat_trx_no" => "6661809266450551869771371523",//订单号三选一必填32平台交易流水号
//            "bank_order_no" => "HMP2001176623767551967834112",//订单号三选一必填32银行订单号，平台送给渠道的商户订单
//            "store_id" => "",//否8门店号100001
//            "terminal_id" => "32",//否终端号10000
//            "operator_id" => "10000",//否8操作员号10000
//            "req_reserved" => "",//商户自定义字段
//            "extend_params" => []//-JSON格式，与下游额外约定的特殊参数
//        ];
        $this->setReqData($options);
        $this->config['method'] = "trade.cancel";
        $data = $this->getResult();
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
            //'total_fee' => ToolsService::ncPriceYuan2fen(isset($data['oriTranAmt']) ? $data['oriTranAmt'] : $data['buyerPayAmount']),  //分
            'transaction_id' => isset($data['transactionId']) ? $data['transactionId'] : '',
            'out_trade_no' => isset($data['ordNo']) ? $data['ordNo'] : '',
            'attach' => '',
            'time_end' => isset($data['payTime']) ? $data['payTime'] : '',
            'trade_state' => isset($data['tranSts']) ? $data['tranSts'] : $data['result_code'],
            'qr_code' => isset($data['qr_code']) ? $data['qr_code'] : '',
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
        $data = $_GET;
//        $data = [
//            "app_id"=>"552018071901179",
//            "bank_order_no"=>"551808166435673987681113091",
//            "bank_trx_no"=>"20180816095058384099",
//            "notify_type"=>"trade_status_sync",
//            "out_order_no"=>"1534384254834",
//            "pay_amount"=>"0.01",
//            "pay_success_time"=>"20180816095048",
//            "plat_trx_no"=>"541808166435673987681113094",
//            "settle_amount"=>"0.01",
//            "sign_type"=>"RSA",
//            "trade_status"=>"SUCCESS",
//            "sign"=>"OLUnaUiXFnqjO4ThGdL4w8fTHL7M5zwH7JmmfHow3BWm2dthcx7ydlnlgSKa2Q2ixOZLYFagrmC2PeWRG7X iSWvrT4wDJMYVfR5o58m/i2f"
//        ];
        if (!empty($data['sign'])) {
            $sign = $data['sign'];
            unset($data['sign']);
            if (!$this->verify($this->getSignContent($data), $sign, $this->publicKey)) {
                throw new \Exception('Invalid Notify Sign is error.', '0');
            }
            $return = [
                'return_code' => $data['return_code'], //通信结果
                'return_msg' => "SUCCESS",
                'result_code' => $data['sub_code'],
                'appid' => $data['app_id'],
                'mch_id' => '',
                'device_info' => '',
                'nonce_str' => '',
                'sign' => $sign,
                'openid' => $data['buyer_id'] ?? $data['buyer_id'],
                'is_subscribe' => '',
                'trade_type' => 'trade_type',
                'bank_type' => '',
                'total_fee' => ToolsService::ncPriceYuan2fen($data['pay_amount']),  //分
                'transaction_id' => $data['plat_trx_no'], // 平台交易流水号
                'out_trade_no' => $data['out_order_no'], // 商户订单号
                'attach' => '',
                //'time_end'       => ToolsService::format_time($data['payTime']),
                'time_end' => $data['pay_success_time'],
                'trade_state' => ($data['trade_status'] == 'SUCCESS') ? 'SUCCESS' : 'FAIL',
                'raw_data' => $data
            ];
            if ($data['bizCode'] !== '0000') {
                $return['err_code'] = isset($data['subCode']) ? $data['subCode'] : '';
                $return['err_code_des'] = isset($data['subMsg']) ? $data['subMsg'] : '';
            }
            return $return;
        }
        exit(); // 当商户收到回调通知时，应返回Http状态码200且返回响应体SUCCESS，如返回其他值，平台将多次重复进行通知
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
