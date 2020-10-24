<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Service\ToolsService;
use GuzzleHttp\Client;

/**
 * 易生支付基础类
 * Class Bhecard
 * @package Pay\Gateways\Bhecard
 */
abstract class Bhecard extends GatewayInterface
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
    protected $gateway_test = "https://test_nucc.bhecard.com:9088/api_gateway.do";
    protected $gateway = "https://newpay.bhecard.com/api_gateway.do";
    protected $aappppl = "https://test_nucc.bhecard.com:9088/api_gateway.do";


    /**
     * Wechat constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $env = $config['env'] ?? 'pro';
        if ($env != 'pro') {
            $this->gateway = $this->gateway_test;
        }
        $this->userConfig = new Config($config);
        $this->config = [
            'service' => '',
            'partner' => $this->userConfig->get('partner', ''),
            'sign' => '',
            'sign_type' => 'RSA',
            'charset' => 'UTF-8',
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

    /**
     * 图片进行base64编码
     * @param $image
     * @return string
     */
    protected function imgToBase64($image)
    {
        return base64_encode(file_get_contents($image));
    }

    /**
     * 获取验证访问数据
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {
        $this->config['service'] = $this->service;

        if (isset($this->config['biz_content']['image_str'])) {
            //图片特殊编码后上传
            $this->config['biz_content']['image_str'] = $this->imgToBase64($this->config['biz_content']['image_str']);
        }
        $this->config['sign'] = $this->getSign($this->config['biz_content']);
        $this->config['biz_content'] = json_encode($this->config['biz_content'], 320);
        $client = new Client(['verify' => false]);
        $return_data = $client->request('POST', $this->gateway, ['form_params' => $this->config])->getBody()->getContents();
        $service_return_name = str_replace(".", "_", $this->service) . '_response';
        $resultOrigin = json_decode($return_data, true);

        $trade_response = json_encode($resultOrigin[$service_return_name], 320);

        // 失败
        if (isset($resultOrigin['null_response'])) {
            $service_return_name = 'null_response';
            $trade_response = json_encode($resultOrigin[$service_return_name], 320);
        }

        if (!ToolsService::is_json($trade_response)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $resultOrigin);
        }
        // 业务详细数据
        $result = json_decode($trade_response, true);

        if (!empty($resultOrigin['sign']) && !$this->verify($trade_response, $resultOrigin['sign'], $this->userConfig->get('easy_public_key'))) {
            throw new GatewayException('验证签名失败', 20000, $resultOrigin);
        }

        $response_data = $result;
        $response_data['sign'] = $resultOrigin['sign'] ?? '';
        $response_data['rawdata'] = $return_data;
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['msg']) ? $response_data['msg'] : 'OK!';
        $result['trade_status'] = $result['trade_status'] ?? 'ERROR';
        if ((!isset($result['code']) || $result['code'] !== '00') && strrpos('SUCCESS', $result['trade_status']) === false) {
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = isset($response_data['code']) ? $response_data['code'] : '';
            $response_data['err_code_des'] = isset($response_data['msg']) ? $response_data['msg'] : '';
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
        $this->service = "easypay.merchant.refund";
        $reqData = [
            'subject' => $options['subject'] ?? '',//商户描述
            'merchant_id' => $options['merchant_id'] ?? '',
            'out_trade_no' => $options['out_trade_no'] ?? '',//退款订单编号
            'origin_trade_no' => $options['origin_trade_no'] ?? '',//原支付订单编号
            'refund_amount' => $options['refund_amount'] ?? '',//订单的交易退款金额，单位为分
        ];
        $this->setReqData($reqData);
        $data = $this->getResult();
        //trade_type:"CONSUME":支付;"REFUND":退款;"DEPOSIT":充值
        $trade_state = $data['trade_status'] ?? 'FAIL';

        if ($this->isSuccess($data)) {
            switch ($trade_state) {
                case 'INIT':
                case 'UNKNOWN':
                    $trade_state = 'USER_PAYING';//支付中
                    break;
                case 'SUCCESS':
                case 'BUSINESS_OK':
                    $trade_state = 'SUCCESS';//支付成功
                    break;
            }
        }

        $data['trade_state'] = ($trade_state == 'USER_PAYING') ? 'USERPAYING' : $trade_state;
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
    public function find($out_trade_no)
    {
        $this->service = "easypay.merchant.query";
        $opitions = [
            'merchant_id' => $this->userConfig->get('merchant_id'),
            'out_trade_no' => $out_trade_no,
        ];
        $this->setReqData($opitions);
        $data = $this->getResult();
        $trade_state = $data['trade_status'] ?? 'FAIL';
        $data['trade_state'] = ($trade_state == 'USER_PAYING') ? 'USERPAYING' : $trade_state;
        if ($this->isSuccess($data)) {
            switch ($trade_state) {
                case 'INIT':
                case 'UNKNOWN':
                    $trade_state = 'USER_PAYING';//支付中
                    break;
                case 'SUCCESS':
                case 'BUSINESS_OK':
                    $trade_state = 'SUCCESS';//支付成功
                    break;
            }
        }

        $data['trade_state'] = ($trade_state == 'USER_PAYING') ? 'USERPAYING' : $trade_state;
        return $this->buildPayResult($data);
    }

    protected function buildPayResult($data)
    {
        $return = [
            'return_code' => $data['return_code'], //通信结果
            'return_msg' => $data['return_msg'],
            'result_code' => $data['result_code'],
            'appid' => $data['appid'] ?? '',
            'mch_id' => '',
            'device_info' => '',
            'nonce_str' => $data['nonce_str'] ?? '',
            'sign' => isset($data['sign']) ? $data['sign'] : '',
            'openid' => $data['openid'] ?? '',
            'is_subscribe' => '',
            'trade_type' => $data['trade_type'] ?? '',
            'bank_type' => '',
            'total_fee' => $data['amount'] ?? 0,
            'transaction_id' => $data['bank_order_no'] ?? '',
            'out_trade_no' => $data['out_trade_no'] ?? '',
            'attach' => '',
            //'time_end'       => ToolsService::format_time($data['payTime']),
            'time_end' => $data['success_time'] ?? date('Y-m-s H:i:s'),
            'trade_state' => $data['trade_state'] ?? '',
            'raw_data' => $data
        ];
        return $return;
    }

    /**
     * RSA验签
     * @param array $data 待签名数据
     * @param string|null $sign 需要验签的签名
     * @param bool|string $public_key
     * @return bool 验签是否通过 bool值
     */
    public function verify($data, $sign = null, $public_key = false)
    {
        $str = chunk_split($public_key, 64, "\n");
        $public_key = "-----BEGIN PUBLIC KEY-----\n$str-----END PUBLIC KEY-----\n";
        $pu_key = openssl_pkey_get_public($public_key);
        $verify = openssl_verify($data, base64_decode($sign), $pu_key, OPENSSL_ALGO_SHA1);
        openssl_free_key($pu_key);
        return $verify == 1;
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
        return isset($result['code']) && ($result['code'] === '00');
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
        if (is_null($this->userConfig->get('sign_key'))) {
            throw new InvalidArgumentException('Missing Config -- [sign_key]');
        }
        $data = $this->getSignContent($data);
        $sign_key = $this->userConfig->get('sign_key');
        $str = chunk_split($sign_key, 64, "\n");
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
        $res = openssl_get_privatekey($private_key);
        openssl_sign($data, $sign, $res);
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
        return json_encode($sign_data, 320);
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
     * 身份证,银行卡信息如需加密时使用
     * @param $data
     * @return string
     */
    public function desEncrypt($data)
    {
        if (is_null($this->userConfig->get('des_encode_key'))) {
            throw new InvalidArgumentException('Missing Config -- [des_encode_key]');
        }
        $out = openssl_encrypt($data, 'DES-ECB', $this->userConfig->get('des_encode_key'), OPENSSL_RAW_DATA);
        return bin2hex($out);
    }
}
