<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Service\ToolsService;

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
    protected $gateway = 'http://pay.uat.chinaebi.com:50080/mrpos/cashier';

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

    /**
     * Wechat constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);
        if (is_null($this->userConfig->get('cert_path'))) {
            throw new InvalidArgumentException('Missing Config -- [cert_path]');
        }
        if (is_null($this->userConfig->get('cert_pwd'))) {
            throw new InvalidArgumentException('Missing Config -- [cert_pwd]');
        }
        if (is_null($this->userConfig->get('merchant_id'))) {
            throw new InvalidArgumentException('Missing Config -- [merchant_id]');
        }
        if (is_null($this->userConfig->get('service'))) {
            throw new InvalidArgumentException('Missing Config -- [service]');
        }
        $this->config = [
            'charset' => '00',// 字符集，固定值：00；代表 GBK
            'version' => '1.1',//  接口版本 String(3) Y 固定值：1.1
            'signType' => 'RSA',//  签名类型，固定值：RSA
            'merchantSign' => '',//  签名
            'merchantCert' => '',//  商户证书
            'service' => $this->userConfig->get('service'),//  接口
            'merchantId' => $this->userConfig->get('merchant_id'),//  商户号
            'requestId' => $this->createNonceStr(32),//  请求号，仅能用大小写字母与数字，且在商户系统具有唯一性
        ];

        $this->setCert();
    }

    /**
     * 解析密钥
     */
    protected function setCert()
    {
        $certs = array();
        $privkeypass = $this->userConfig->get('cert_pwd');// 私钥密码
        $pfxpath = $this->userConfig->get('cert_path'); //密钥文件路径
        $priv_key = file_get_contents($pfxpath); //获取密钥文件内容
        openssl_pkcs12_read($priv_key, $certs, $privkeypass); //读取公钥、私钥
        $this->merchant_private_key = $certs['pkey']; //私钥
        $merchantCert = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'], "", $certs['cert']);
        $merchantCert = base64_decode($merchantCert, 1);
        $merchantCert = strtoupper(bin2hex($merchantCert));
        $this->merchant_cert = $merchantCert;
    }

    /**
     * 设置请求数据
     * @param array $array
     * @return $this
     */
    protected function setReqData($array)
    {
        $this->config = array_merge($this->config, $array);
        return $this;
    }

    /**
     * 获取验证访问数据
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {
        $this->config['merchantSign'] = $this->getSign($this->config);
        $this->config['merchantCert'] = $this->merchant_cert;

        $header = ['Content-Type: application/json'];
        $result = $this->post($this->gateway, json_encode($this->config), ['headers' => $header]);
        $result = mb_convert_encoding($result, "utf8", "gbk");
        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);
        file_put_contents('./result.txt', json_encode(["电银交易", $this->config, $result]) . PHP_EOL, FILE_APPEND);
        if (!$this->verify($result) || $result['rspCode'] != '000000') {
            throw new GatewayException('验证签名失败', 20000, $result);
        }

        $response_data = $result['respData'] ?? $result;
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['msg']) ? $response_data['msg'] : 'OK!';
        if (!isset($result['rspCode']) || $result['rspCode'] !== '000000') {
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = $result['rspCode'];
            $response_data['err_code_des'] = $result['rspMessage'];
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
    protected function getSign($params)
    {
        ksort($params);
        $str = mb_convert_encoding($this->getSignContent($params), "gbk", "utf8");
        //注册生成加密信息
        openssl_sign($str, $signMsg, $this->merchant_private_key, OPENSSL_ALGO_SHA256);
        $merchantSign = strtoupper(bin2hex($signMsg));
        return $merchantSign;
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

            if ($value == '') {
                continue;
            }
            
            if (is_array($value)) {
                $value = stripslashes(json_encode($value, JSON_UNESCAPED_UNICODE));
            }
            if (!in_array($key, ['merchantCert', 'serverCert', 'sign', 'serverSign', 'merchantSign'])) {
                $params[] = $key . '=' . $value;
            }

        }
        $data = implode("&", $params);

        return $data;
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
