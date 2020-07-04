<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Exceptions\GatewayException;

use Ansuns\Pay\Gateways\Wechat;

/**
 * 微信企业打款网关
 * Class TransferGateway
 * @package Pay\Gateways\Wechat
 */
class Transfer extends Wechat
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }

    /**
     * 应用并返回数据
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function apply(array $options = [])
    {
        $options['mchid'] = $this->config['mch_id'];
        $options['mch_appid'] = $this->userConfig->get('app_id');
        unset($this->config['mch_id']);
        unset($this->config['sign_type']);
        $this->config = array_merge($this->config, $options);
        $this->unsetTradeTypeAndNotifyUrl();
        $this->unsetAppid();
        $this->config['sign'] = $this->getSign($this->config);
        $data = $this->fromXml($this->post(
            $this->gateway_transfer, $this->toXml($this->config),
            [
                'ssl_cer' => $this->userConfig->get('ssl_cer', ''),
                'ssl_key' => $this->userConfig->get('ssl_key', ''),
            ]
        ));
        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS') {
            $error = 'GetResultError:' . $data['return_msg'];
            $error .= isset($data['err_code_des']) ? ' - ' . $data['err_code_des'] : '';
        }
        if (isset($error)) {
            throw new GatewayException($error, 20001, $data);
        }
        return $data;
    }
}
