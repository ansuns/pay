<?php

namespace Ansuns\Pay\Service;

/**
 *  瑞银信Aes 对称加密
 * Class RYXAesService
 * @package Ansuns\Pay\Service
 */

class RYXAesService
{

    /**
     * 与java等的aes/ecb/pcks5加密一样效果
     * @param $data
     * @param $key
     * @return string
     */
    public static function encrypt($data, $key)
    {
        return base64_encode(openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_PKCS1_PADDING));//OPENSSL_PKCS1_PADDING 不知道为什么可以与PKCS5通用,未深究
    }


    /**
     * 解密java等的aes/ecb/pcks5加密的内容
     * @param $data
     * @param $key
     * @return string
     */
    public static function decrypt($data, $key)
    {
        return openssl_decrypt(base64_decode($data), 'AES-128-ECB', $key, OPENSSL_PKCS1_PADDING);//OPENSSL_PKCS1_PADDING 不知道为什么可以与PKCS5通用,未深究
    }

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

    /**
     * @param int $length
     * @return bool|string
     */
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