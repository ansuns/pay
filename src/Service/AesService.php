<?php

namespace Ansuns\Pay\Service;

/**
 * AES加密类库
 * Class Aes
 * @package tools
 */
class AesService
{

    //如果是AES-128-CBC长度的必须是16位，AES-192-CBC 必须为32位 ，AES-256-CBC必须位64位

    protected static $iv = '1234123412341234';

    //可以任意长度，貌似超过64会被截取，尚未验证
    protected static $key = '1234123412341234';


    /**
     * 加密
     * @param $input 需要加密的字符串
     * @param string $key 加密密钥
     * @return string
     */
    public static function encrypt($input, $key = '')
    {
        if (!empty($key)) {
            self::$key = $key;
        }

        $encryptedData = openssl_encrypt($input, 'AES-128-ECB', self::$key, OPENSSL_RAW_DATA);

        return base64_encode($encryptedData);
    }

    /**
     * 解密
     * @param $input
     * @param string $key
     * @return string
     */
    public static function decrypt($input, $key = '')
    {
        if (!empty($key)) {
            self::$key = $key;
        }
        $encryptedData = openssl_encrypt($input, 'AES-128-ECB', self::$key, OPENSSL_RAW_DATA);

        return base64_encode($encryptedData);
    }

    //手动填充（补位）

    /**
     * 加密后对称密钥
     * @param $aesKey
     * @param $publicKey
     * @return string
     */
    public static function aesEncrypt($aesKey, $publicKey)
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        $key = openssl_get_publickey($publicKey);
        openssl_public_encrypt($aesKey, $encrypt, $key, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypt);
    }

    public static function keygen($length = 16)
    {
        $token = '';
        $tokenlength = round($length * 4 / 3);
        for ($i = 0; $i < $tokenlength; ++$i) {
            $token .= chr(rand(32, 1024));
        }
        $token = base64_encode(str_shuffle($token));
        return substr($token, 0, $length);
    }
}