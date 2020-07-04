<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Gateways\Wechat;

/**
 * 下载微信资金账单
 * Class FundflowGateway
 * @package Pay\Gateways\Wechat
 */
class Fundflow extends Wechat
{
    /**
     * @var string
     */
    protected $encrypt_method = 'HMAC-SHA256';

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return bool|array|string
     * @throws GatewayException
     */
    public function apply(array $options)
    {
        $this->config = array_merge($this->config, $options);
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['account_type'] = !empty($options['account_type']) ? $options['account_type'] : 'Basic';
        $this->config['sign'] = $this->getSign($this->config);
        $this->unsetTradeTypeAndNotifyUrl();
        $data = $this->fromXml($this->post($this->gateway_fundflow, $this->toXml($this->config), [
            'ssl_cer' => $this->userConfig->get('ssl_cer', ''),
            'ssl_key' => $this->userConfig->get('ssl_key', ''),
        ]));
        if (is_array($data) && (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS')) {
            $error = 'GetResultError:' . $data['return_msg'];
            $error .= isset($data['err_code_des']) ? ' - ' . $data['err_code_des'] : '';
        }
        if (isset($error)) {
            throw new GatewayException($error, 20001, $data);
        }
        return $this->parserDownloadData($data);
    }
}