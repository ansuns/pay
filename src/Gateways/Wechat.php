<?php

namespace Ansuns\Pay\Gateways;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Contracts\GatewayInterface;
use Ansuns\Pay\Contracts\HttpService;
use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;

/**
 * 微信支付基础类
 * Class Wechat
 * @package Pay\Gateways\Wechat
 */
abstract class Wechat extends GatewayInterface
{

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Config
     */
    protected $userConfig;

    /**
     * @var string
     */
    protected $gateway = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * @var string
     */
    protected $gateway_query = 'https://api.mch.weixin.qq.com/pay/orderquery';
    /**
     * @var string
     */
    protected $gateway_refund_query = 'https://api.mch.weixin.qq.com/pay/refundquery';

    /**
     * @var string
     */
    protected $gateway_close = 'https://api.mch.weixin.qq.com/pay/closeorder';

    /**
     * @var string
     */
    protected $gateway_refund = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

    /**
     * @var string
     */
    protected $gateway_transfer = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';

    /**
     * @var string
     */
    protected $gateway_micropay = 'https://api.mch.weixin.qq.com/pay/micropay';

    /**
     * @var string
     */
    protected $gateway_bill = 'https://api.mch.weixin.qq.com/pay/downloadbill';

    /**
     * @var string
     */
    protected $gateway_fundflow = 'https://api.mch.weixin.qq.com/pay/downloadfundflow';
    /**
     * @var string
     */
    protected $gateway_ras_public = 'https://fraud.mch.weixin.qq.com/risk/getpublickey';

    /**
     * @var string
     */
    protected $gateway_paybank = 'https://api.mch.weixin.qq.com/mmpaysptrans/pay_bank';

    /**
     * Wechat constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->debug = !empty($config['debug']);
        $this->userConfig = new Config($config);
        if (is_null($this->userConfig->get('app_id'))) {
            throw new InvalidArgumentException('Missing Config -- [app_id]');
        }
        if (is_null($this->userConfig->get('mch_id'))) {
            throw new InvalidArgumentException('Missing Config -- [mch_id]');
        }
        if (is_null($this->userConfig->get('mch_key'))) {
            throw new InvalidArgumentException('Missing Config -- [mch_key]');
        }
        if (!empty($config['cache_path'])) {
            HttpService::$cachePath = $config['cache_path'];
        }
        // 沙箱模式
        if (!empty($config['debug'])) {
            $this->gateway = 'https://api.mch.weixin.qq.com/sandboxnew/pay/unifiedorder';
            $this->gateway_bill = 'https://api.mch.weixin.qq.com/sandboxnew/pay/downloadbill';
            $this->gateway_query = 'https://api.mch.weixin.qq.com/sandboxnew/pay/orderquery';
            $this->gateway_refund_query = 'https://api.mch.weixin.qq.com/sandboxnew/pay/refundquery';
            $this->gateway_close = 'https://api.mch.weixin.qq.com/sandboxnew/pay/closeorder';
            $this->gateway_refund = 'https://api.mch.weixin.qq.com/sandboxnew/secapi/pay/refund';
            $this->gateway_transfer = 'https://api.mch.weixin.qq.com/sandboxnew/mmpaymkttransfers/promotion/transfers';
            $this->gateway_micropay = 'https://api.mch.weixin.qq.com/sandboxnew/pay/micropay';
            $this->gateway_paybank = 'https://api.mch.weixin.qq.com/sandboxnew/mmpaysptrans/pay_bank';
//            $this->gateway_ras_public = 'https://fraud.mch.weixin.qq.com/sandboxnew/risk/getpublickey';
            // 沙箱验证签名及沙箱密钥更新
            $sandbox_signkey_cache = 'sandbox_signkey:' . $this->userConfig->get('mch_id');
            $sandbox_signkey = HttpService::getCache($sandbox_signkey_cache);
            if (empty($sandbox_signkey)) {
                $data = ['mch_id' => $this->userConfig->get('mch_id', ''), 'nonce_str' => $this->createNonceStr('32')];
                $data['sign'] = $this->getSign($data);
                $result = $this->fromXml($this->post('https://api.mch.weixin.qq.com/sandboxnew/pay/getsignkey', $this->toXml($data)));
                if (isset($result['return_code']) && $result['return_code'] === 'SUCCESS') {
                    $sandbox_signkey = $result['sandbox_signkey'];
                    HttpService::setCache($sandbox_signkey_cache, $sandbox_signkey);
                } else {
                    throw new Exception(isset($result['return_msg']) ? $result['return_msg'] : '沙箱验证签名及获取沙箱密钥失败！');
                }
            }
            $this->userConfig->set('mch_key', $sandbox_signkey);
        }
        $this->config = [
            'appid' => $this->userConfig->get('app_id', ''),
            'mch_id' => $this->userConfig->get('mch_id', ''),
            'nonce_str' => $this->createNonceStr(),
            'sign_type' => 'MD5',
            'notify_url' => $this->userConfig->get('notify_url', ''),
            'trade_type' => $this->getTradeType(),
            'spbill_create_ip' => $this->getClientIp()
        ];
        if ($this->userConfig->offsetExists('sub_appid')) {
            $this->config['sub_appid'] = $this->userConfig->get('sub_appid', '');
        }
        if ($this->userConfig->offsetExists('sub_mch_id')) {
            $this->config['sub_mch_id'] = $this->userConfig->get('sub_mch_id', '');
        }

    }

    /**
     * 订单退款操作
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function refund($options = [])
    {
        $this->config = array_merge($this->config, $options);
        $this->config['op_user_id'] = isset($this->config['op_user_id']) ?: $this->userConfig->get('mch_id', '');
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->gateway_refund, true);
    }

    /**
     * 查询退款订单状态
     * @param string $out_trade_no
     * @return array
     * @throws GatewayException
     */
    public function refund_find($out_trade_no = '')
    {
        $this->config['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->gateway_refund_query);
    }

    /**
     * 关闭正在进行的订单
     * @param string $out_trade_no
     * @return array
     * @throws GatewayException
     */
    public function close($out_trade_no = '')
    {
        $this->config['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        return $this->getResult($this->gateway_close);
    }

    /**
     * 查询订单状态
     * @param string $out_trade_no
     * @return array
     * @throws GatewayException
     */
    public function find($out_trade_no = '')
    {
        $this->config['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        $this->unsetSpbillCreateIp();
        return $this->getResult($this->gateway_query);
    }

    /**
     * XML内容验证
     * @param string $data
     * @param null $sign
     * @param bool $sync
     * @return array|bool
     */
    public function verify($data, $sign = null, $sync = false)
    {
        $data = $this->fromXml($data);
        $sign = is_null($sign) ? $data['sign'] : $sign;
        return $this->getSign($data) === $sign ? $data : false;
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
        return $this->getResult($this->gateway);
    }

    /**
     * 获取验证访问数据
     * @param string $url
     * @param bool $cert
     * @return array
     * @throws GatewayException
     */
    protected function getResult($url, $cert = false)
    {
        $this->config['sign'] = $this->getSign($this->config);
        if ($cert) {
            $data = $this->fromXml($this->post($url, $this->toXml($this->config), ['ssl_cer' => $this->userConfig->get('ssl_cer', ''), 'ssl_key' => $this->userConfig->get('ssl_key', '')]));
        } else {
            $data = $this->fromXml($this->post($url, $this->toXml($this->config)));
        }
        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS' || (isset($data['result_code']) && $data['result_code'] !== 'SUCCESS')) {
            $error = 'ERROR_RETURN_MSG:' . (isset($data['return_msg']) ? $data['return_msg'] : '未知错误');
            $error .= isset($data['err_code_des']) ? ';ERROR_CODE_DES:' . $data['err_code_des'] : '';
            $error .= isset($data['err_code_msg']) ? ';ERROR_CODE_MSG:' . $data['err_code_msg'] : '';
            $error .= isset($data['err_code']) ? ';ERROR_CODE:' . $data['err_code'] : '';
        }
        if (isset($data['sign'])) {
            if (!isset($error) && $this->getSign($data) !== $data['sign']) {
                $error = 'GetResultError: return data sign error';
            }
        }

        //发起支付时
        if (((isset($data['result_code']) && $data['result_code'] === 'FAIL') && (isset($data['err_code']) && $data['err_code'] === 'USERPAYING'))) {
            return $data;
        }

        //发起查询时
        if (((isset($data['result_code']) && $data['result_code'] === 'SUCCESS') && (isset($data['trade_state']) && $data['trade_state'] === 'USERPAYING'))) {
            $data['err_code_des'] = $data['trade_state_desc'];
            return $data;
        }

        //
        if (!isset($data['err_code_des'])) {
            $data['err_code_des'] = isset($data['trade_state_desc']) ? $data['trade_state_desc'] : "未知错误";
        }

        if (isset($error)) {
            throw new GatewayException($error, -1, $data);
        }
        return $data;
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
     * 生成内容签名
     * @param $data
     * @param $encrypt_method
     * @return string
     */
    protected function getSign($data, $encrypt_method = 'MD5')
    {
        if (is_null($this->userConfig->get('mch_key'))) {
            throw new InvalidArgumentException('Missing Config -- [mch_key]');
        }
        ksort($data);
        $encrypt_method = isset($this->config['sign_type']) ? $this->config['sign_type'] : $encrypt_method;
        switch (strtoupper($encrypt_method)) {
            case 'MD5':
                $string = md5($this->getSignContent($data) . '&key=' . $this->userConfig->get('mch_key'));
                break;
            case 'HMAC-SHA256':
                $string = hash_hmac('sha256', $this->getSignContent($data) . '&key=' . $this->userConfig->get('mch_key'), $this->userConfig->get('mch_key'));
                break;
            default:
                throw new InvalidArgumentException('not support function:' . $encrypt_method);
        }
        return strtoupper($string);
    }

    /**
     * 生成签名内容
     * @param $data
     * @return string
     */
    private function getSignContent($data)
    {
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
     * 转为XML数据
     * @param array $data 源数据
     * @return string
     */
    protected function toXml($data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new InvalidArgumentException('convert to xml error !invalid array!');
        }
        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= (is_numeric($val) ? "<{$key}>{$val}</{$key}>" : "<{$key}><![CDATA[{$val}]]></{$key}>");
        }
        return $xml . '</xml>';
    }

    /**
     * 获取客户端IP地址
     *
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return mixed
     */
    protected function getClientIp($type = 0)
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

    /**
     * 解析XML数据
     * @param string $xml 源数据
     * @return mixed
     */
    protected function fromXml($xml)
    {
        if (!$xml) {
            throw new InvalidArgumentException('convert to array error !invalid xml');
        }
        try {
            libxml_disable_entity_loader(true);
            return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
        } catch (\Exception $e) {
            return $xml;
        }
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
     * 清理签名验证不必要的参数
     * @return bool
     */
    protected function unsetSignTypeAndNonceStr()
    {
        unset($this->config['nonce_str']);
        unset($this->config['sign_type']);
        return true;
    }

    /**
     * 清理签名验证不必要的参数
     * @return bool
     */
    protected function unsetSpbillCreateIp()
    {
        unset($this->config['spbill_create_ip']);
        return true;
    }

    /**
     * 清理签名验证不必要的参数
     * @return bool
     */
    protected function unsetAppid()
    {
        unset($this->config['appid']);
        return true;
    }

    /**
     * 解析下载数据
     * @param string $result 源数据
     * @param array $field_map 字段映射
     * @return array
     * @throws \Exception
     */
    protected function parserDownloadData($result, $field_map = [])
    {
        $result = explode("\n", $result);
        $count = count($result);
        reset($result);
        $list_header = explode(',', current($result));
        $list_array = array_slice($result, 1, $count - 4);
        $total_header = explode(',', current(array_slice($result, $count - 3, 1)));
        $total_array = explode(',', current(array_slice($result, $count - 2, 1)));
        $list = [];
        foreach ($list_array as $key => $val) {
            $temp_data = [];
            foreach (explode(',', $val) as $k => $v) {
                $key_name = str_replace(array("/r", "/n", "/r/n"), "", $list_header[$k]);
                preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9]/u', $key_name, $result); // 保留中文/数字/字母  交易时间 trade_time 不处理会字符串长度15 无法匹配
                $key_name = join('', $result[0]);
                if (!empty($field_map)) {
                    //如果有字段映射,则查找对应key
                    $key_name = array_search($key_name, $field_map);
                    if ($key_name === false) {
                        throw new \Exception('字段:' . $key_name . '匹配失败!');
                    }
                }
                $temp_data[$key_name] = trim(str_replace('`', '', $v));
            }
            $list[] = $temp_data;
        }
        $total = [];
        foreach ($total_array as $key => $value) {
            $total[$total_header[$key]] = str_replace('`', '', $value);
        }
        return ['list' => $list, 'total' => $total];
    }

    /**
     * 获取微信支付通知
     * @return array
     * @throws InvalidArgumentException
     */
    public function getNotify()
    {
        $data = $this->fromXml(file_get_contents('php://input'));
        if (isset($data['sign']) && $this->getSign($data) === $data['sign']) {
            return $data;
        }
        throw new InvalidArgumentException('Invalid Notify.', '0');
    }

    /**
     * 获取微信支付通知回复内容
     * @return string
     */
    public function getNotifySuccessReply()
    {
        return $this->toXml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
    }

    /**
     * 返回失败通知XML
     * @param string $return_msg 错误信息
     * @return string
     */
    public function getNotifyFailedReply($return_msg = '')
    {
        return $this->toXml(['return_code' => 'FAIL', 'return_msg' => 'FAIL:' . $return_msg]);
    }
}
