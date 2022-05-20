<?php

namespace Ansuns\Pay\Service;

/**
 * 使用openssl-RSA实现非对称加密
 */
class RsaSecurityService
{
    private $_key;        //key arrays
    private $_privKey;    //private key resource
    private $_pubKey;    //public key resource
    private $hash = 'sha1';    //hash：默认sha1
    private $encode = 'base64'; //密文加密方式

    public function __construct($public = '', $private = '', $type = 'PUBLIC KEY')
    {
        if (!empty($public)) {
            $public = $this->pubFormat($public, $type);
        }
        $this->_key = array('private' => $private, 'public' => $public);
    }

    /*
    * determines which hashing function should be used
    */
    public function setHash($hash = 'sha1', $encode = 'base64')
    {
        //OPENSSL_ALGO_SHA1\OPENSSL_ALGO_MD5\OPENSSL_ALGO_MD4\OPENSSL_ALGO_MD2
        if (in_array($hash, array('md2', 'md5', 'sha1', 'sha256', 'sha384', 'sha512'))) {
            $this->hash = $hash;
        }
        if (in_array($encode, array('base64', 'hex'))) {
            $this->encode = $encode;
        }
    }

    /*
    * setup the private key
    */
    public function setupPrivKey()
    {
        if (is_resource($this->_privKey)) return true;
        $this->_privKey = openssl_get_privatekey($this->_key['private']);
        return true;
    }

    /**
     * setup the public key
     */
    public function setupPubKey()
    {
        if (is_resource($this->_pubKey)) return true;
        $this->_pubKey = openssl_get_publickey($this->_key['public']);
        return true;
    }

    /**
     * encrypt with the private key
     */
    public function privEncrypt($data, $size = 117)
    {
        if (!is_string($data)) return NULL;
        $this->setupPrivKey();

        $des = array();
        do {
            $string = substr($data, 0, $size);
            if (openssl_private_encrypt($string, $encrypted, $this->_privKey)) {
                $des[] = $encrypted;
            } else {
                return NULL;
            }
            $data = substr($data, $size);
        } while (strlen($data) > 0);
        $encrypted = join('', $des);
        return $this->encode == 'base64' ? base64_encode($encrypted) : bin2hex($encrypted);
    }

    /**
     * decrypt with the private key
     */
    public function privDecrypt($encrypted, $size = 128)
    {
        if (!is_string($encrypted)) return NULL;
        $this->setupPrivKey();
        $data = $this->encode == 'base64' ? base64_decode($encrypted) : $this->hex2bin($encrypted);
        do {
            $string = substr($data, 0, $size);
            if (openssl_private_decrypt($string, $decrypted, $this->_privKey)) {
                $des[] = $decrypted;
            } else {
                return NULL;
            }
            $data = substr($data, $size);
        } while (strlen($data) > 0);
        $str = join('', $des);
        return $str;
    }

    /**
     * encrypt with public key
     */
    public function pubEncrypt($data, $size = 256)
    {
        if (!is_string($data)) return NULL;
        $this->setupPubKey();

        $des = array();
        do {
            $string = substr($data, 0, $size);
            if (openssl_public_encrypt($string, $encrypted, $this->_pubKey)) {
                $des[] = $encrypted;
            } else {
                return NULL;
            }
            $data = substr($data, $size);
        } while (strlen($data) > 0);
        $encrypted = join('', $des);
        return $this->encode == 'base64' ? base64_encode($encrypted) : bin2hex($encrypted);
    }

    /**
     * decrypt with the public key
     */
    public function pubDecrypt($crypted, $size = 128)
    {
        if (!is_string($crypted)) return NULL;
        $this->setupPubKey();
        $data = $this->encode == 'base64' ? base64_decode($crypted) : $this->hex2bin($crypted);

        do {
            $string = substr($data, 0, $size);
            if (openssl_public_decrypt($string, $decrypted, $this->_pubKey)) {
                $des[] = $decrypted;
            } else {
                return NULL;
            }
            $data = substr($data, $size);
        } while (strlen($data) > 0);
        return join('', $des);
    }

    /**
     * sign with the private key
     */
    public function privSignForLiandong($data)
    {
        if (!is_string($data)) return NULL;
        $this->setupPrivKey();
        openssl_sign($data, $sign, $this->_privKey, $this->hash);
        if ($sign) {
            return $this->encode == 'base64' ? base64_encode($sign) : bin2hex($sign);
        }
        return NULL;
    }

    /**
     * sign with the private key
     */
    public function privSign($data)
    {
        if (!is_string($data)) return NULL;
        $this->setupPrivKey();
        openssl_sign($data, $sign, $this->_privKey, $this->hash);
        if ($sign) {
            return $this->encode == 'base64' ? base64_encode($sign) : bin2hex($sign);
        }
        return NULL;
    }

    /**
     * sign verify with the publick key
     */
    public function getpubVerify($data, $sign)
    {
        if (!is_string($data) || !is_string($sign)) return NULL;
        $this->setupPubKey();

        $sign = $this->encode == 'base64' ? base64_decode($sign) : $this->hex2bin($sign);
        $result = (bool)openssl_verify($data, $sign, $this->_pubKey, $this->hash);
        return $result;
    }

    /**
     * sign verify with the publick key
     */
    public function pubVerify($data, $sign)
    {
        if (!is_string($data) || !is_string($sign)) return NULL;
        $this->setupPubKey();

        $sign = $this->encode == 'base64' ? base64_decode($sign) : $this->hex2bin($sign);
        $result = (bool)openssl_verify($data, $sign, $this->_pubKey, $this->hash);
        return $result;
    }

    //检查是否为公钥
    public function isPublicKey($string = '')
    {
        $string = $this->pubFormat($string);
        $this->_pubKey = openssl_get_publickey($string);
        if (is_resource($this->_pubKey)) {
            return true;
        } else {
            return false;
        }

    }

    //格式化：公钥
    public function pubFormat($string = '', $type = 'PUBLIC KEY')
    {
        $rsa = $this->pubString($string);
        $arr = array();
        $arr[] = "-----BEGIN $type-----";
        while (strlen($rsa) >= 64) {
            $arr[] = substr($rsa, 0, 64);
            $rsa = substr($rsa, 64);
        }
        if (!empty($rsa)) {
            $arr[] = $rsa;
        }
        $arr[] = "-----END $type-----";
        $rsa = join("\n", $arr);
        return $rsa;
    }

    public function pubString($string)
    {
        return trim(str_replace(array("\n", "\r"), '', preg_replace('/----(.*)----/', '', $string)));
    }

    //16进制转2进制
    private function hex2bin($data)
    {
        $len = strlen($data);
        return pack("H" . $len, $data);
    }

    public function __destruct()
    {
        @fclose($this->_privKey);
        @fclose($this->_pubKey);
    }
}

