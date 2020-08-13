<?php


namespace Ansuns\Pay\Gateways\Ruiyinxin;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\Ruiyinxin;
use Ansuns\Pay\Service\AesService;
use Ansuns\Pay\Service\RsaSecurityService;
use Ansuns\Pay\Service\RYXRSAService;
use Ansuns\Pay\Service\ToolsService;

/**
 * 商户配置
 * Class Mch
 * @package Pay\Gateways\Wechat
 */
class Mch extends Ruiyinxin
{

    protected $gateway = "http://119.254.80.46:7080/nms/";
    public static $ivParam = '5rU34728s1GQ3242';//AES密码向量   16位
    public static $aesKey = 'ta8bphaKM8tYc5sqgloIe9/cTmIBMZIHMzk8Ey4sJ94=';
    public static $accessId = "000000000000000";

    public function __construct(array $config, string $type = 'trade')
    {
        parent::__construct($config, $type);
        //进件
        $this->config = [
            'accessId' => self::$accessId,// $this->userConfig->get('accessId', $this->createNonceStr(16)),//（接入ID，为每个接入方分配的唯一ID），
            'reqTime' => date('YmdHis'),//请求时间，yyyyMMddHHmmss格式只接收来自1分钟内的请求
            'sign' => '',//（签名信息）
            //加密后的 AES 对称密钥：用smzfPubKey加密cooperatorAESKey
            'encryptKey' => AesService::aesEncrypt($this->userConfig->get('cooperatorAESKey'), $this->userConfig->get('smzfPubKey')),
            'otherParam' => "",//,附加参数，根据不同接口该参数不同，可传空，
            'info' => [],//（具体请求数据，该数据由AES（AES/CBC/PKCS5Padding）进行加密），
            'files' => $this->userConfig->get('files', []),
        ];

    }

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }

    /**
     * @return string
     */
    private function createAccessMerchId()
    {
        $charid = md5(uniqid(mt_rand(), true));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
    }

    /**
     * 发起支付
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function apply(array $options = [])
    {
        $this->setReqData($options);
        $this->service = "ims/merch/simpleApply";
        return $this->getResult();
    }


    /**
     * 设置请求数据
     * @param array $array
     * @return $this
     */
    protected function setReqData($array)
    {
        $this->config['info'] += $array;
        return $this;
    }


    protected function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * 获取验证访问数据
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {

        $commonParams = $this->getCommonParams();
        $params = $this->config['info'];
        //组织签名数据
        //使用私钥对数据部分openssl签名
        $signData = $this->createSignData($commonParams, $params);
        $this->config['sign'] = $signData;
        if (!isset($params['accessMerchId'])) {
            $accessMerchId = $this->createAccessMerchId();
            //$commonParams['accessMerchId'] = $accessMerchId;
        } else {
            $accessMerchId = $params['accessMerchId'];

        }
        $this->config['accessMerchId'] = $accessMerchId;
        // 获取AES密钥
        $keyParam = base64_decode(self::$aesKey);
        $base64Key = base64_encode($keyParam);
        $str = [
            'aesKey' => $base64Key,
            'ivParam' => self::$ivParam
        ];
        //用RSA公钥加密方式对AES密钥进行加密,对方公钥加密
        $jsonStr = json_encode($str, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $encryptKey = $this->createCooperatorAESKey($jsonStr);
        $this->config['encryptKey'] = $encryptKey;

        // 数据进行加密
        if (!empty($params)) {
            $rawData = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $rawData = '{}';
        }

        $info = $this->createAESencryptData($rawData, $keyParam, self::$ivParam);
        $this->config['info'] = $info;

        $files = $this->config['files'] ?? [];

        unset($this->config['files']);
        if (!empty($files)) {
            foreach ($files as $key => $value) {
                $this->config['files'][$key] = curl_file_create($value);

            }
        }
        $url = $this->gateway . $this->service;
        $result = $this->post($url, $this->config);
        file_put_contents('./result.txt', json_encode(["瑞银信进件", $this->config, $result]) . PHP_EOL, FILE_APPEND);

        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);
        if (!isset($result['encryptKey'])) {
            $response_data['return_code'] = 'EORROR'; //数据能解析则通信结果认为成功
            $response_data['result_code'] = 'EORROR'; //初始状态为成功,如果失败会重新赋值
            $response_data['return_msg'] = isset($result['msg']) ? $result['msg'] : 'EROOR!';
            return $response_data;
        }
        // 获取结果的数据进行解密
        $response_data = $this->decryptData($result);
        $response_data = json_decode($response_data, true);
        file_put_contents('./result.txt', json_encode([$response_data]) . PHP_EOL, FILE_APPEND);
        if (!isset($response_data['respCode']) || $response_data['respCode'] != '000000' || !isset($response_data['respType']) || $response_data['respType'] != 'S') {
            $response_data['result_code'] = 'FAIL';
            $err_code_des = (isset($response_data['respMsg']) ? $response_data['respMsg'] : '');
            $err_code = isset($response_data['respCode']) ? $response_data['respCode'] : 'F';
            $response_data['err_code'] = $err_code;
            $response_data['err_code_des'] = $err_code_des;
        }

        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['respMsg']) ? $response_data['respMsg'] : '处理成功';
        return $response_data;
    }

    /**
     * 获取公共请求参数
     * @return array
     */
    private function getCommonParams()
    {
        return [
            'accessId' => self::$accessId,
            'reqTime' => $this->config['reqTime'],
            'ivParam' => self::$ivParam,
        ];
    }

    /**
     * 获取验签
     * @param $str
     * @return null|string
     */
    private function createSignData($commonParams, $signData)
    {
        // 公共参数
        $buff = $this->makeUrlData($commonParams);

        // 输入参数
        if (!empty($signData)) {
            $buff .= '&';
            ksort($signData);
            $signBuff = $this->makeUrlData($signData);
            $buff .= $signBuff;
        }

        $privKey = $this->userConfig->get('privKey');
        $privKey = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($privKey, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;

        openssl_sign($buff, $binary_signature, $privKey, "SHA256");
        $res = base64_encode($binary_signature);
        return $res;
    }

    private function makeUrlData($arr)
    {
        $buff = '';
        foreach ($arr as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * AES加密
     * @param $string 要加密的字符串
     * @param $key
     * @param $iv
     * @return string
     */
    private function createAESencryptData($string, $key, $iv)
    {
        if ($string == '{}') {
            $data = openssl_encrypt($string, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        } else {
            $string = $this->pkcsPadding($string, 8);
            $data = openssl_encrypt($string, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        }

        $data = base64_encode($data);
        return $data;
    }

    private function pkcsPadding($str, $blocksize)
    {
        $pad = $blocksize - (strlen($str) % $blocksize);
        return $str . str_repeat(chr($pad), $pad);
    }

    /**
     * 加密数据
     * RSA公钥加密方式对AES密钥进行加密，对方公钥加密
     * @param $str
     * @return null|string
     */
    private function createCooperatorAESKey($str)
    {
        $pubKey = $this->userConfig->get('imsPubKey');

        $rsa = new RsaSecurityService($pubKey);
        $res = $rsa->pubEncrypt($str);
        return $res;
    }

    /**
     * 解密数据
     * 使用RSA私钥解密AES密钥,我方私钥解密
     * @param $resp
     * @return false|string
     */
    public function decryptData($resp)
    {
        $encryptKey = $resp['encryptKey'];
        $info = $resp['info'];


        $privKey = $this->userConfig->get('privKey');
        $privKey = '-----BEGIN RSA PRIVATE KEY-----' . PHP_EOL . chunk_split($privKey, 64, PHP_EOL) . '-----END RSA PRIVATE KEY-----' . PHP_EOL;
        $rsa = new RsaSecurityService('', $privKey);
        $res = $rsa->privDecrypt($encryptKey, 256);
        $res = json_decode($res, true);

        $result = openssl_decrypt(base64_decode($info), 'AES-256-CBC', base64_decode($res['aesKey']), OPENSSL_RAW_DATA, $res['ivParam']);
        return $result;
    }

    /**
     * 进件结果查询
     * @param $accessMerchId
     * @return array
     * @throws GatewayException
     */
    public function queryMerchResult($accessMerchId)
    {
        $this->setReqData(['accessMerchId' => $accessMerchId]);
        $this->service = "/ims/merch/queryMerchResult";
        return $this->getResult();
    }
}