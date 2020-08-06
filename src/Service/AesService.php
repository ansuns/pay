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
        if (!$key) {
            self::$key = $key;
        }

        //加密字符串需要自己手动去填充补位，也就是你字符串不足需要进行补位，要补够16
        $data = openssl_encrypt(self::addpadding($input), 'AES-128-CBC', self::$key, OPENSSL_NO_PADDING, self::$iv);
        //因为加密出来的字符不方便传输，所以需要把它转成16进制，也可以用base64加密
        return base64_encode($data);
    }

    /**
     * 解密
     * @param $input
     * @param string $key
     * @return string
     */
    public static function decrypt($input, $key = '')
    {
        if (!$key) {
            self::$key = $key;
        }
        $decrypted = openssl_decrypt(base64_decode($input), 'AES-128-CBC', self::$key, OPENSSL_NO_PADDING, self::$iv);

        //因为加密的时候，补了位，所以返回的时候需要把补了位的去除掉
        return rtrim($decrypted, "\0");
    }

    //手动填充（补位）

    /**
     * 手动填充
     * @param $string // 需要填充的字符串
     * @param int $blocksize 填充位数
     * @return string
     */
    private static function addpadding($string, $blocksize = 16)
    {

        //判断长度
        $len = strlen($string);

        //计算需要补位的长度
        $pad = $blocksize - ($len % $blocksize);

        //把字符串重新n次，然后拼接
        $string .= str_repeat("\0", $pad);

        return $string;

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