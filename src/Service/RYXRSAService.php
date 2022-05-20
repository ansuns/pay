<?php

namespace Ansuns\Pay\Service;

/**
 * 瑞银信rsa
 */
class RYXRSAService
{
    private $_config = [
        'public_key' => '',
        'private_key' => '',
    ];

    public function __construct($private_key_filepath, $public_key_filepath)
    {
        $this->_config['private_key'] = $this->_getContents($private_key_filepath);
        $this->_config['public_key'] = $this->_getContents($public_key_filepath);
    }

    /**
     * @param $file_path string
     * @return bool|string
     * @uses 获取文件内容
     */
    private function _getContents($file_path)
    {
        file_exists($file_path) or die ('密钥或公钥的文件路径错误');
        return file_get_contents($file_path);
    }

    /**
     * @return bool|resource
     * @uses 获取私钥
     */
    private function _getPrivateKey()
    {
        $priv_key = $this->_config['private_key'];
        return openssl_pkey_get_private($priv_key);
    }

    /**
     * @return bool|resource
     * @uses 获取公钥
     */
    private function _getPublicKey()
    {
        $public_key = $this->_config['public_key'];
        return openssl_pkey_get_public($public_key);
    }

    /**
     * @param string $data
     * @return null|string
     * @uses 私钥加密
     */
    public function privEncrypt($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_private_encrypt($data, $encrypted, $this->_getPrivateKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * @param string $data
     * @return null|string
     * @uses 公钥加密
     */
    public function publicEncrypt($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_public_encrypt($data, $encrypted, $this->_getPublicKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * @param string $encrypted
     * @return null
     * @uses 私钥解密
     */
    public function privDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, $this->_getPrivateKey())) ? $decrypted : null;
    }

    /**
     * @param string $encrypted
     * @return null
     * @uses 公钥解密
     */
    public function publicDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_public_decrypt(base64_decode($encrypted), $decrypted, $this->_getPublicKey())) ? $decrypted : null;
    }
}
