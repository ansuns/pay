<?php

namespace Ansuns\Pay\Gateways;

use app\common\help\CodeHelp;
use InvalidArgumentException;
use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\GatewayException;

/**
 * 支付宝抽象类
 * Class Alipay
 * @package Pays\Gateways\Alipay
 */
abstract class Alipay extends GatewayInterface
{

    /**
     * 支付宝全局参数
     * @var array
     */
    protected $config;

    /**
     * 用户定义配置
     * @var Config
     */
    protected $userConfig;

    /**
     * 支付宝网关地址
     * @var string
     */
    protected $gateway = 'https://openapi.alipay.com/gateway.do?charset=utf-8';

    /**
     * Alipay constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->userConfig = new Config($config);
        if (is_null($this->userConfig->get('app_id'))) {
            throw new InvalidArgumentException('Missing Config -- [app_id]');
        }
        if (!empty($config['cache_path'])) {
            HttpService::$cachePath = $config['cache_path'];
        }
        // 沙箱模式
        if (!empty($config['debug'])) {
            $this->gateway = 'https://openapi.alipaydev.com/gateway.do?charset=utf-8';
        }
        $this->config = [
            'app_id' => $this->userConfig->get('app_id'),
            'method' => '',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'version' => '1.0',
            'app_auth_token' => $this->userConfig->get('app_auth_token', ''), //开发者代替商户发起请求
            'return_url' => $this->userConfig->get('return_url', ''),
            'notify_url' => $this->userConfig->get('notify_url', ''),
            'timestamp' => date('Y-m-d H:i:s'),
            'sign' => '',
            'biz_content' => '',
        ];
    }

    /**
     * 应用参数
     * @param array $options
     * @return mixed|void
     */
    public function apply(array $options)
    {
        $options['product_code'] = $this->getProductCode();
        if ($this->userConfig->get('app_auth_token')) {
            //如果 app_auth_token 不为空则认为是ISV模式,传入pid
            $this->config['extend_params'] = json_encode(['sys_service_provider_id' => $this->userConfig->get('pid')], JSON_UNESCAPED_UNICODE);
        }
        $this->config['biz_content'] = json_encode($options, JSON_UNESCAPED_UNICODE);
        $this->config['method'] = $this->getMethod();
        $this->config['sign'] = $this->getSign();
    }

    /**
     * 支付宝订单退款操作
     * @param array|string $options 退款参数或退款商户订单号
     * @param null $refund_amount 退款金额
     * @return array|bool
     * @throws GatewayException
     */
    public function refund($options, $refund_amount = null)
    {
        if (!is_array($options)) {
            $options = ['out_trade_no' => $options, 'refund_amount' => $refund_amount];
        }
        return $this->getResult($options, 'alipay.trade.refund');
    }

    /**
     * 关闭支付宝进行中的订单
     * @param array|string $options
     * @return array|bool
     * @throws GatewayException
     */
    public function close($options)
    {
        if (!is_array($options)) {
            $options = ['out_trade_no' => $options];
        }
        return $this->getResult($options, 'alipay.trade.close');
    }

    /**
     * 查询支付宝订单状态
     * @param string $out_trade_no
     * @return array|bool
     * @throws GatewayException
     */
    public function find($out_trade_no = '')
    {
        $options = ['out_trade_no' => $out_trade_no];
        $data = $this->getResult($options, 'alipay.trade.query');
        if (isset($data['trade_status'])) {
            $data['return_msg'] = $data['result_code'] = $data['return_code'] = 'ERROR';
            $data['err_code_des'] = "用户支付中";
            switch ($data['trade_status']) {
                case "WAIT_BUYER_PAY":
                    $data['trade_state'] = "USERPAYING";
                    break;
                case "TRADE_CLOSED":
                    $data['trade_state'] = "CLOSED";
                    break;
                case "TRADE_FINISHED":
                    $data['trade_state'] = "FINISHED";
                    break;
                case "TRADE_SUCCESS":
                    $data['trade_state'] = "SUCCESS";
                    $data['return_msg'] = 'SUCCESS';
                    $data['result_code'] = 'SUCCESS';
                    $data['return_code'] = 'SUCCESS';
                    $data['err_code_des'] = "支付成功";
                    break;
            }
            $data['transaction_id'] = $data['trade_no'];
            $data['out_trade_no'] = $data['out_trade_no'];

            $gmt_payment = isset($data['gmt_payment']) ? $data['gmt_payment'] : date('Y-m-d H:i:s');
            $gmt_payment = str_replace('-', '', $gmt_payment);
            $gmt_payment = str_replace(':', '', $gmt_payment);
            $gmt_payment = str_replace(' ', '', $gmt_payment);
            $data['time_end'] = $gmt_payment;
            $data['total_fee'] = tools()::ncPriceYuan2fen($data['total_amount']);
            $data['openid'] = isset($data['buyer_user_id']) ? $data['buyer_user_id'] : '';
        }
        return $data;
    }

    /**
     * 验证支付宝支付宝通知
     * @param array $data 通知数据
     * @param null $sign 数据签名
     * @param bool $sync
     * @return array|bool
     */
    public function verify($data, $sign = null, $sync = false)
    {
        if (is_null($this->userConfig->get('public_key'))) {
            throw new InvalidArgumentException('Missing Config -- [public_key]');
        }
        $sign = is_null($sign) ? $data['sign'] : $sign;
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($this->userConfig->get('public_key'), 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $toVerify = $sync ? json_encode($data) : $this->getSignContent($data, true);
        return openssl_verify($toVerify, base64_decode($sign), $res, OPENSSL_ALGO_SHA256) === 1 ? $data : false;
    }

    /**
     * @return string
     */
    protected function buildPayHtml()
    {
        $html = "<form id='alipaysubmit' name='alipaysubmit' action='{$this->gateway}' method='post'>";
        foreach ($this->config as $key => $value) {
            $value = str_replace("'", '&apos;', $value);
            $html .= "<input type='hidden' name='{$key}' value='{$value}'/>";
        }
        $html .= "<input type='submit' value='ok' style='display:none;'></form>";
        return $html . "<script>document.forms['alipaysubmit'].submit();</script>";
    }

    /**
     * 获取验证访问数据
     * @param array $options
     * @param string $method
     * @return array|bool
     * @throws GatewayException
     */
    protected function getResult($options, $method)
    {
        $this->config['method'] = $method;
        $this->config['biz_content'] = json_encode($options, JSON_UNESCAPED_UNICODE);
        if ($this->userConfig->get('app_auth_token')) {
            //如果 app_auth_token 不为空则认为是ISV模式,传入pid
            $this->config['extend_params'] = json_encode(['sys_service_provider_id' => $this->userConfig->get('pid')], JSON_UNESCAPED_UNICODE);
        }
        $this->config['sign'] = $this->getSign();
        $method = str_replace('.', '_', $method) . '_response';
        $data = json_decode($this->post($this->gateway, $this->config), true);
        $response = isset($data[$method]) ? $data[$method] : $data['error_response'];
        if ((!isset($response['code']) || $response['code'] !== '10000') && !isset($response['access_token'])) {
            //支付失败统一返回错误信息
            $response['gmt_payment'] = date('Y-m-d H:i:s');
            $response['trade_no'] = "";
            $response['buyer_user_id'] = "";
            $response['return_code'] = "ERROR";
            $response['result_code'] = "ERROR";
            $response['trade_state'] = "PAYERROR";
            $sub_code = isset($response['sub_code']) ? $response['sub_code'] : 10000;
            $response['err_code_des'] = CodeHelp::instance()->alipay($response['code'], $sub_code);
            // 等待支付
            $response['trade_state'] = ($response['code'] == '10003') ? "USERPAYING" : "PAYERROR";

            if (!IS_CLI) {
                return $response;
            } else {
                //命令行下载对账单需要抛出异常
                throw new GatewayException(
                    "\nResultError" .
                    (empty($response['code']) ? '' : "\n{$response['msg']}[{$response['code']}]") .
                    (empty($response['sub_code']) ? '' : "\n{$response['sub_msg']}[{$response['sub_code']}]\n"),
                    $response['code'],
                    $data
                );
            }
        }
        $result = $this->verify($response, $data['sign'], true);
        if ($result === false) {
            wr_log('支付成功,但是签名验证失败');
            $result = $response; //临时兼容,依旧返回有效数据
        } else {
            wr_log('支付成功,签名验证通过');
            wr_log($result);
        }
        if (is_array($result)) {
            $result = array_merge($this->success_return(), $result);
        }
        return $result;
    }

    private function success_return()
    {
        return [
            'return_code' => 'SUCCESS',
            'return_msg' => 'SUCCESS',
            'result_code' => 'SUCCESS',
            'trade_state' => 'SUCCESS'
        ];
    }

    /**
     * 获取数据签名
     * @return string
     */
    protected function getSign()
    {
        if (is_null($this->userConfig->get('private_key'))) {
            throw new InvalidArgumentException('Missing Config -- [private_key]');
        }
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->userConfig->get('private_key'), 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($this->getSignContent($this->config), $sign, $res, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    /**
     * 数据签名处理
     * @param array $toBeSigned
     * @param bool $verify
     * @return bool|string
     */
    protected function getSignContent(array $toBeSigned, $verify = false)
    {
        ksort($toBeSigned);
        $stringToBeSigned = '';
        foreach ($toBeSigned as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 0, -1);
        unset($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 获取微信支付通知
     * @return array
     * @throws \Pays\Exceptions\InvalidArgumentException
     */
    public function getNotify()
    {
        $data = request()->param();
        wr_log($data, 1);
        if (isset($data['sign']) && $this->getSign($data) === $data['sign']) {
            if (isset($data['trade_status']) && $data['trade_status'] == 'TRADE_SUCCESS') {
                $data['trade_state'] = ($data['trade_status'] == 'TRADE_SUCCESS') ? 'SUCCESS' : $data['trade_status'];
                $data['transaction_id'] = $data['trade_no'];
                $data['out_trade_no'] = $data['out_trade_no'];
                $gmt_payment = $data['gmt_payment'];
                $gmt_payment = str_replace('-', '', $gmt_payment);
                $gmt_payment = str_replace(':', '', $gmt_payment);
                $gmt_payment = str_replace(' ', '', $gmt_payment);
                $data['time_end'] = $gmt_payment;
                $data['total_fee'] = tools()::ncPriceYuan2fen($data['total_amount']);
                $data = array_merge($this->success_return(), $data);
            }
            wr_log('支付回调验签名成功', 1);
            return $data;
        } else {
            if (isset($data['trade_status']) && $data['trade_status'] == 'TRADE_SUCCESS') {
                $data['trade_state'] = ($data['trade_status'] == 'TRADE_SUCCESS') ? 'SUCCESS' : $data['trade_status'];
                $data['transaction_id'] = $data['trade_no'];
                $data['out_trade_no'] = $data['out_trade_no'];
                $gmt_payment = $data['gmt_payment'];
                $gmt_payment = str_replace('-', '', $gmt_payment);
                $gmt_payment = str_replace(':', '', $gmt_payment);
                $gmt_payment = str_replace(' ', '', $gmt_payment);
                $data['time_end'] = $gmt_payment;
                $data['total_fee'] = tools()::ncPriceYuan2fen($data['total_amount']);
                $data = array_merge($this->success_return(), $data);
            }
            wr_log('支付回调验签名失败', 1);
            return $data;
            wr_log('支付回调验签名失败', 1);
        }

        throw new InvalidArgumentException('Invalid Alipay Notify.', '0');
    }

    /**
     * 获取微信支付通知回复内容
     * @return string
     */
    public function getNotifySuccessReply()
    {
        return 'success';
    }

    /**
     * 返回失败通知XML
     * @param string $return_msg 错误信息
     * @return string
     */
    public function getNotifyFailedReply($return_msg = '')
    {
        return 'failed';
    }

    /**
     * @return string
     */
    abstract protected function getMethod();

    /**
     * @return string
     */
    abstract protected function getProductCode();
}
