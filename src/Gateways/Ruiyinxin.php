<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Service\AesService;
use Ansuns\Pay\Service\RYXRSAService;
use Ansuns\Pay\Service\ToolsService;

/**
 * 瑞银信支付基础类
 * Class Ruiyinxin
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
    protected $gateway_test = 'http://wxtest.ruishangtong.com/ydzf/ydzf-smzf';
    protected $gateway = 'https://qr.ruiyinxin.com/ydzf/ydzf-smzf';

    /**
     * @var string
     */
    protected $gateway_wechat_test = 'http://wxtest.ruishangtong.com/ydzf/wechat/gateway';
    protected $gateway_wechat = 'https://qr.ruiyinxin.com/ydzf/wechat/gateway';

    /**
     * Wechat constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);

        if (is_null($this->userConfig->get('reqMsgId'))) {
            throw new InvalidArgumentException('Missing Config -- [reqMsgId]');
        }
        if (is_null($this->userConfig->get('cooperator'))) {
            throw new InvalidArgumentException('Missing Config -- [cooperator]');
        }
        if (is_null($this->userConfig->get('cooperatorPubKey'))) {
            throw new InvalidArgumentException('Missing Config -- [cooperatorPubKey]');
        }
        if (is_null($this->userConfig->get('cooperatorPriKey'))) {
            throw new InvalidArgumentException('Missing Config -- [cooperatorPriKey]');
        }
        if (is_null($this->userConfig->get('smzfPubKey'))) {
            throw new InvalidArgumentException('Missing Config -- [smzfPubKey]');
        }

        //合作方 AES 对称密钥，加密报文
        $this->userConfig->set('cooperatorAESKey', AesService::keygen(16));
        $reqMsgId = $this->userConfig->get('reqMsgId');//请求流水号（订单号）
        $this->config = [
            'cooperator' => $this->userConfig->get('cooperator'),//合作方标识
            'signData' => '',//请求报文签名
            'tranCode' => '',//交易服务码
            'callBack' => $this->userConfig->get('callback', 'http://58.56.27.134:8086/smshmn/callback.jsp'),//回调地址（查询类交易可以不送）
            //加密后的 AES 对称密钥：用smzfPubKey加密cooperatorAESKey
            'encryptKey' => AesService::aesEncrypt($this->userConfig->get('cooperatorAESKey'), $this->userConfig->get('smzfPubKey')),
            'reqMsgId' => $reqMsgId,//请求流水号（订单号）
            //加密后的请求报文
            'encryptData' => [
                'version' => '1.0.0',
                'msgType' => '01',
                'reqDate' => date('YmdHis'),
                'data' => []
            ],
            'ext' => [],//备用域
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

        $data = json_encode($this->config['encryptData'], JSON_UNESCAPED_UNICODE);
        $this->config['signData'] = $this->getSign($data);
        $this->config['encryptData'] = AesService::encrypt($data, $this->userConfig->get('cooperatorAESKey'));
        $header = ['Content-Type: application/x-www-form-urlencoded'];
        $result = $this->post($this->gateway, http_build_query($this->config), ['headers' => $header]);
        file_put_contents('./result.txt', json_encode(["瑞银信交易", $result]) . PHP_EOL, FILE_APPEND);
        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);

        //合作方RSA私钥cooperatorPriKey解密encryptKey得到扫码支付平台smzfAESKey
        $rsa = new RYXRSAService($this->userConfig->get('cooperator_pri_key_path'), $this->userConfig->get('cooperator_pub_key_path'));
        $smzfAESKey = $rsa->privDecrypt($result['encryptKey']);
        //用解密得到的smzfAESKey 解密 encryptData
        $resEncryptData = base64_decode(AesService::aesDecryptData($result['encryptData'], $smzfAESKey));
        if (!ToolsService::is_json($resEncryptData)) {
            throw new GatewayException('解密数据不是有效json格式', 20000, $result);
        }

        $response_data = json_decode($resEncryptData, true);
        file_put_contents('./result.txt', json_encode([$response_data]) . PHP_EOL, FILE_APPEND);
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['respMsg']) ? $response_data['respMsg'] : '处理成功!';
        if (!isset($response_data['respCode']) || $response_data['respCode'] != '000000' || !isset($response_data['respType']) || $response_data['respType'] != 'S') {
            $response_data['result_code'] = 'FAIL';
            $err_code_des = (isset($response_data['respMsg']) ? $response_data['respMsg'] : '');
            $err_code = isset($response_data['respCode']) ? $response_data['respCode'] : 'F';
            $response_data['err_code'] = $err_code;
            $response_data['err_code_des'] = $err_code_des;
        }
        return $response_data;
    }


    /**
     * 退款操作
     * @param $refundAmount 需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
     * @param string $oriReqMsgId oriReqMsgId
     * @param string $merchantCode 商户入驻返回的商户编号
     * @return array|mixed
     * @throws GatewayException
     */
    public function refund($refundAmount, $oriReqMsgId = '', $merchantCode = '')
    {
        $this->gateway = 'http://wxtest.ruishangtong.com/ydzf/admin/ydzf-smzf001/';
        $this->config['tranCode'] = "SMZF004"; //申请退款
        //$this->config['reqMsgId'] =$oriReqMsgId;
        $this->setReqData([
            'oriReqMsgId' => $oriReqMsgId, 'merchantCode' => $merchantCode, 'refundAmount' => $refundAmount
        ]);
        $data = $this->getResult();
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
     * 撤销交易
     * @param string $oriReqMsgId
     * @param string $merchantCode
     * @return array|mixed
     * @throws GatewayException
     */
    public function close($oriReqMsgId = '', $merchantCode = '')
    {
        $this->config['tranCode'] = "SMZF005"; //撤销交易
        $this->setReqData([
            'oriReqMsgId' => $oriReqMsgId, 'merchantCode' => $merchantCode
        ]);
        return $this->getResult();
    }


    /**
     * 查询订单
     * @param string $oriReqMsgId
     * @return array|mixed
     * @throws GatewayException
     */
    public function find($oriReqMsgId = '')
    {
        $this->config['tranCode'] = "SMZF006"; //交易查询
        $this->setReqData(['oriReqMsgId' => $oriReqMsgId]);
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

        return isset($result['respCode']) && ($result['respCode'] === '000000') && isset($result['respType']) && ($result['respType'] === 'S');
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
