<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Service\ToolsService;
use GuzzleHttp\Client;

/**
 * 电银支付基础类
 * Class Wechat
 * @package Pay\Gateways\Chinaebi
 */
abstract class Chinaebi extends GatewayInterface
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
    protected $gateway = 'https://116.228.47.74:7443/transaction_agent/scan/trans';

    //交易类型
    const WX_NATIVE = 'WX_NATIVE';//微信扫码
    const WX_APP = 'WX_APP';//微信APP//支付
    const WX_JSAPI = 'WX_JSAPI';//微信JSAPI(公众号,小程序)
    const CUP_APP = 'CUP_APP';//银联控件支付
    const ALIPAY = 'ALIPAY';//支付宝APP支付
    const AL_NATIVE = 'AL_NATIVE';//支付宝扫码
    const WX_JSAPP = 'WX_JSAPP';//微信APP支付
    const ALI_JSAPI = 'ALI_JSAPI';//标准支付宝小程序支付

    protected $merchant_private_key = '';
    protected $merchant_cert = '';
    protected $body = [];

    /**
     * Wechat constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);
//        if (is_null($this->userConfig->get('cert_path'))) {
//            throw new InvalidArgumentException('Missing Config -- [cert_path]');
//        }
//        if (is_null($this->userConfig->get('cert_pwd'))) {
//            throw new InvalidArgumentException('Missing Config -- [cert_pwd]');
//        }
//        if (is_null($this->userConfig->get('merchant_id'))) {
//            throw new InvalidArgumentException('Missing Config -- [merchant_id]');
//        }
//        if (is_null($this->userConfig->get('service'))) {
//            throw new InvalidArgumentException('Missing Config -- [service]');
//        }
        $this->config = [
            'merc_id' => $this->userConfig->get('merc_id'), // 商户号
            'send_time' => date("Ymd"), // 交易发起时间
            'org_id' => $this->userConfig->get('org_id'), //机构号
            'charset' => 'UTF-8', // 字符集
            'version' => '1.0', // 接口版本
            'sign_type' => 'RSA', // 签名类型
            //'sign' => '',//签名
        ];
    }

    /**
     * 设置请求数据
     * @param array $array
     * @return $this
     */
    protected function setReqData($array)
    {
        $this->body += $array;
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

        $header = ['Content-Type: application/json'];
        $request = [
            'head' => $this->config,
            'body' => $this->body
        ];
        //  $result = $this->post($this->gateway, json_encode($request), ['headers' => $header]);

        file_put_contents('./result.txt', json_encode($request) . PHP_EOL, FILE_APPEND);
        $client = new Client(['verify' => false]);
        //$result  = $client->request('POST',$this->gateway,['json'=>$request]);
        $data_string = json_encode($request);
        $result = $client->request('POST', $this->gateway, ['body' => $data_string,
            'headers' => ['Content-Type' => 'application/json',]
        ])->getBody()->getContents();

        file_put_contents('./result.txt', json_encode([777, $result]) . PHP_EOL, FILE_APPEND);
        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);

//        if (!$this->verify($result) || $result['res_code'] != '000000') {
//            throw new GatewayException('验证签名失败', 20000, $result);
//        }

        $headData = $result['head'] ?? [];
        $response_data = $result['body'] ?? [];
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['res_msg']) ? $response_data['res_msg'] : 'OK!';
        if (!isset($headData['res_code']) || $headData['res_code'] !== '00') {
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = $headData['res_code'];
            $response_data['err_code_des'] = $headData['res_msg'];
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
     * @param array $params 待签名数据
     * @param string|null $sign 需要验签的签名
     * @param bool|string $pub_key
     * @return bool 验签是否通过 bool值
     */
    public function verify($params, $sign = null, $pub_key = false)
    {
        $str = $this->getSignContent($params);
        $pubkey = $this->convertPublicKey(base64_encode(hex2bin($params['serverCert']))); //格式化公钥
        return openssl_verify($str, hex2bin($params['serverSign']), $pubkey, OPENSSL_ALGO_SHA256); // 返回验签结果
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
     * @param $params
     * @return string
     */
    protected function getSign($data)
    {

        $data += $this->body;
        if (is_null($this->userConfig->get('private_key'))) {
            throw new InvalidArgumentException('Missing Config -- [private_key]');
        }
        $data = $this->getSignContent($data);
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->userConfig->get('private_key'), 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $res = openssl_get_privatekey($private_key);
        openssl_sign($data, $sign, $res, "SHA256");
        openssl_free_key($res);
        return base64_encode($sign);  //base64编码
    }

    /**
     * 生成签名内容
     * @param array $sign_data
     * @return string
     */
    private function getSignContent($sign_data)
    {
        ksort($sign_data);
        $params = [];
        foreach ($sign_data as $key => $value) {
            if (is_array($value)) {
                $value = stripslashes(json_encode($value, JSON_UNESCAPED_UNICODE));
            }
            if ($value == '') {
                continue;
            }
            if (!in_array($key, ['merchantCert', 'serverCert', 'sign', 'serverSign', 'merchantSign', 'sign_type'])) {
                $params[] = $key . '=' . $value;
            }
        }
        return implode("&", $params);
    }

    /**
     * 格式化公钥
     * @param $publicKey
     * @return string
     */
    protected function convertPublicKey($publicKey)
    {
        //判断是否传入公钥内容
        $public_key_string = !empty($publicKey) ? $publicKey : '';
        //64位换行公钥钥内容
        $public_key_string = chunk_split($public_key_string, 64, "\n");
        //公钥头部
        $public_key_header = "-----BEGIN CERTIFICATE-----" . PHP_EOL;
        //公钥尾部
        $public_key_footer = "-----END CERTIFICATE-----";
        //完整公钥拼接
        $public_key_string = $public_key_header . $public_key_string . $public_key_footer;
        return $public_key_string;
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

    /**
     * 获取客户端IP地址
     *
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return mixed
     */
    protected function get_client_ip($type = 0)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL)
            return $ip[$type];
        if (isset ($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos)
                unset ($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset ($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset ($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }
}
