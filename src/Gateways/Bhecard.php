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
    protected $gateway_test = "https://notify-test.eycard.cn:7443/WorthTech_Access_AppPaySystemV2/apppayacc";
    protected $gateway = "https://notify-test.eycard.cn:7443/WorthTech_Access_AppPaySystemV2/apppayacc";
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
            'channelid' => $this->userConfig->get('channelid', ''),
            'merid' => $this->userConfig->get('merid', ''),
            'termid' => $this->userConfig->get('termid', ''),
            'sign' => '',
        ];
        $this->getSignKey();
    }

    /**
     * 更新并获取签名密钥
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getSignKey()
    {
        $client = new Client(['verify' => false]);
        $config = [
            'channelid' => $this->userConfig->get('channelid', ''),
            'opt' => 'getSign',
        ];
        $config['sign'] = $this->getSign($config);

        $return_data = $client->request('POST', $this->gateway, ['form_params' => $config])->getBody()->getContents();
        $data = json_decode($return_data, true);
        $key = '';
        if (isset($data['resultcode']) && $data['resultcode'] == '00') {
            $key = $data['key'];
        }
        $this->userConfig->set('sign_key', $key);
    }

    /**
     * 设置请求数据
     * @param array $array
     * @return $this
     */
    protected function setReqData($array)
    {
        $this->config += $array;
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
        $this->config['sign'] = $this->getSign($this->config);
        $client = new Client(['verify' => false]);
        $return_data = $client->request('POST', $this->gateway, ['form_params' => $this->config])->getBody()->getContents();

        if (!ToolsService::is_json($return_data)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $return_data);
        }
        $return_data = json_decode($return_data, true);

//        if (!empty($resultOrigin['sign']) && !$this->verify($trade_response, $resultOrigin['sign'], $this->userConfig->get('easy_public_key'))) {
//            throw new GatewayException('验证签名失败', 20000, $resultOrigin);
//        }

        $response_data = $return_data;
        $response_data['sign'] = $resultOrigin['sign'] ?? '';
        $response_data['rawdata'] = $return_data;
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['msg']) ? $response_data['msg'] : 'OK!';
        $result['trade_status'] = $result['trade_status'] ?? 'ERROR';
        if ((!isset($response_data['resultcode']) || $response_data['resultcode'] !== '00')) {
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = isset($response_data['code']) ? $response_data['code'] : '';
            $response_data['err_code_des'] = isset($response_data['msg']) ? $response_data['msg'] : '';
        }
        return $response_data;
    }

    protected function doData($return_data)
    {
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
        if (isset($response_data['resultcode']) && $response_data['resultcode'] == '00') {
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = isset($response_data['code']) ? $response_data['code'] : '';
            $response_data['err_code_des'] = isset($response_data['msg']) ? $response_data['msg'] : '';
        }
        return $response_data;
    }

    /**
     * 订单退款/撤销操作
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function refund($options = [])
    {
        $reqData = [
            'opt' => 'zwrefund',
            'subject' => $options['subject'] ?? '',//商户描述
            'tradetrace' => $options['out_trade_no'] ?? '',//退款订单编号
            'oritradetrace' => $options['origin_trade_no'] ?? '',//原支付订单编号
            'tradeamt' => $options['refund_fee'] ?? '',//退款金额
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
        $opitions = [
            'opt' => 'tradeQuery',
            'tradetrace' => $out_trade_no,
        ];
        $this->setReqData($opitions);
        $data = $this->getResult();
        $trade_state = $data['resultcode'] ?? 'FAIL';
        $data['trade_state'] = ($trade_state == 'USER_PAYING') ? 'USERPAYING' : $trade_state;
        switch ($trade_state) {
            case 'AA':
            case 'UNKNOWN':
                $trade_state = 'USER_PAYING';//支付中
                break;
            case 'SUCCESS':
            case '00':
                $trade_state = 'SUCCESS';//支付成功
                break;
            default:
                $trade_state = 'USER_PAYING';//支付中
                break;
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
            'openid' => $data['wxopenid'] ?? '',
            'is_subscribe' => '',
            'trade_type' => $data['trade_type'] ?? '',
            'bank_type' => '',
            'total_fee' => $data['amount'] ?? 0,
            'transaction_id' => $data['wxtransactionid'] ?? '',
            'out_trade_no' => $data['tradetrace'] ?? '',
            'attach' => '',
            'pay_time'       =>  isset($data['wxtimeend']) ? date('Y-m-s H:i:s',strtotime($data['wxtimeend'])):date('Y-m-s H:i:s'),
            'time_end' => isset($data['wxtimeend']) ? date('Y-m-s H:i:s',strtotime($data['wxtimeend'])):date('Y-m-s H:i:s'),
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
        return isset($result['resultcode']) && ($result['resultcode'] === '00');
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
                'raw_data' => $data['raw_data'] ?? []
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
        return strtoupper(md5($data));

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
        $getKey = false;
        foreach ($sign_data as $key => $value) {
            if ($key != 'sign') {
                $params[] = $key . '=' . $value;
            }
            if ($key == 'opt' && $value == 'getSign') {
                $getKey = true;
            }
        }
        $data = implode("&", $params);
        if ($getKey == true) {
            $data .= "&key=" . $this->userConfig->get('channel_key');
        } else {
            $data .= "&key=" . $this->userConfig->get('sign_key');
        }
        return $data;
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
