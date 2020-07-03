<?php
/**
 * Created by PhpStorm.
 * User: ANSUNS
 * Date: 2020/7/3
 * Time: 10:33
 */

namespace Ansuns;

use Ansuns\Util\BasicPayInterface;

class SandPay extends BasicPayInterface
{
    protected $API_HOST;
    protected $PUB_KEY_PATH;  //公钥文件
    protected $PRI_KEY_PATH; //私钥文件
    protected $CERT_PWD; //私钥证书密码
    public $PAY_URL = '/order/pay'; //
    public $QUERY_URL = '/order/query'; //
    public $REFUND_URL = '/order/refund'; //
    public $DOWN_URL = '/clearfile/download'; //
    public $CLOSE_URL = '/order/close'; //

    public function __construct(array $config)
    {
        $this->API_HOST = 'https://cashier.sandpay.com.cn/gateway/api';
        $this->PUB_KEY_PATH = $config['pub_key_path'];
        $this->PRI_KEY_PATH = $config['pri_key_path'];
        $this->CERT_PWD = $config['cert_pwd'];

    }

    public function apply(array $options)
    {
        return $this->post($this->PAY_URL, $options);
    }

    public function refund(array $options)
    {
        return $this->post($this->REFUND_URL, $options);
    }

    public function close(array $options)
    {
        return $this->post($this->CLOSE_URL, $options);
    }

    public function find(string $out_trade_no)
    {
        //return $this->post($this->QUERY_URL, $options);
    }

    /**
     * NOTIFY
     * @throws \Exception
     */
    public function notify()
    {
        $pubkey = $this->loadX509Cert($this->APUB_KEY_PATH);
        if ($_POST) {
            $sign = $_POST['sign']; //签名
            $signType = $_POST['signType']; //签名方式
            $data = stripslashes($_POST['data']); //支付数据
            $charset = $_POST['charset']; //支付编码
            $result = json_decode($data, true); //data数据

            if (verify($data, $sign, $pubkey)) {
                //签名验证成功
                file_put_contents("temp/sd_notifyUrl_log.txt", date("Y-m-d H:i:s") . "  " . "异步通知返回报文：" . $data . "\r\n",
                    FILE_APPEND);
                echo "respCode=000000";
                exit;
            } else {
                //签名验证失败
                exit;
            }
        }
    }


    /**
     * 获取公钥
     * @param $path
     * @return mixed
     * @throws \Exception
     */
    public function loadX509Cert($path)
    {
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadx509Cert::file_get_contents ERROR');
            }

            $cert = chunk_split(base64_encode($file), 64, "\n");
            $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";

            $res = openssl_pkey_get_public($cert);
            $detail = openssl_pkey_get_details($res);
            openssl_free_key($res);

            if (!$detail) {
                throw new \Exception('loadX509Cert::openssl_pkey_get_details ERROR');
            }

            return $detail['key'];
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * 获取私钥
     * @param $path
     * @param $pwd
     * @return mixed
     * @throws \Exception
     */
    public function loadPk12Cert($path, $pwd)
    {
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadPk12Cert::file
					_get_contents');
            }

            if (!openssl_pkcs12_read($file, $cert, $pwd)) {
                throw new \Exception('loadPk12Cert::openssl_pkcs12_read ERROR');
            }
            return $cert['pkey'];
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * 私钥签名
     * @param $plainText
     * @param $path
     * @return string
     * @throws \Exception
     */
    public function sign($plainText, $path)
    {
        $plainText = json_encode($plainText);
        try {
            $resource = openssl_pkey_get_private($path);
            $result = openssl_sign($plainText, $sign, $resource);
            openssl_free_key($resource);

            if (!$result) {
                throw new \Exception('签名出错' . $plainText);
            }

            return base64_encode($sign);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 公钥验签
     * @param array $plainText
     * @param null $sign
     * @param bool $path
     * @return int|mixed
     * @throws \Exception
     */
    public function verify($plainText, $sign, $path)
    {
        $resource = openssl_pkey_get_public($path);
        $result = openssl_verify($plainText, base64_decode($sign), $resource);
        openssl_free_key($resource);

        if (!$result) {
            throw new \Exception('签名验证未通过,plainText:' . $plainText . '。sign:' . $sign, '02002');
        }

        return $result;
    }

    /**
     * 发送请求
     * @param string $url
     * @param array|string $data
     * @param array $options
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public function post($url, $data, $options = [])
    {

        // step2: 私钥签名
        $prikey = loadPk12Cert($this->PRI_KEY_PATH, $this->CERT_PWD);
        $sign = sign($data, $prikey);

        // step3: 拼接post数据
        $post = array(
            'charset' => 'utf-8',
            'signType' => '01',
            'data' => json_encode($data),
            'sign' => $sign
        );

        // step4: post请求
        $result = http_post_json($this->API_HOST . $url, $post);
        $arr = parse_result($result);

        //step5: 公钥验签
        $pubkey = loadX509Cert($this->PUB_KEY_PATH);
        try {
            verify($arr['data'], $arr['sign'], $pubkey);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }

        // step6： 获取credential
        $data = json_decode($arr['data'], true);
        if ($data['head']['respCode'] == "000000") {
            $credential = $data['body']['credential'];
        } else {
            print_r($arr['data']);
        }

        if (empty($url) || empty($param)) {
            return false;
        }
        $param = http_build_query($param);
        try {

            $ch = curl_init();//初始化curl
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //正式环境时解开注释
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $data = curl_exec($ch);//运行curl
            curl_close($ch);

            if (!$data) {
                throw new \Exception('请求出错');
            }

            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $result
     * @return array
     */
    public function parse_result($result)
    {
        $arr = array();
        $response = urldecode($result);
        $arrStr = explode('&', $response);
        foreach ($arrStr as $str) {
            $p = strpos($str, "=");
            $key = substr($str, 0, $p);
            $value = substr($str, $p + 1);
            $arr[$key] = $value;
        }

        return $arr;
    }

}