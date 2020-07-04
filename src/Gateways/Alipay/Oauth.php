<?php


namespace Ansuns\Pay\Gateways\Alipay;

use Ansuns\Pay\Gateways\Alipay;

/**
 * 支付宝授权网关
 * Class AppGateway
 * @package Pay\Gateways\Alipay
 */
class Oauth extends Alipay
{

    /**
     * 当前接口方法
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.system.oauth.token';
    }

    /**
     * 当前接口方法
     * @return string
     */
    protected function getQueryMethod()
    {
        return 'alipay.open.auth.token.app.query';
    }

    /**
     * 当前接口产品码
     * @return string
     */
    protected function getProductCode()
    {
        return '';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function apply(array $options = [])
    {
        $this->config = array_merge($this->config, $options);
        $result = $this->getResult($options, $this->getMethod());
        if (isset($result['user_id'])) {
            $result['openid'] = $result['user_id'];
        } else {
            throw new \Exception('支付宝授权失败:' . ($result['sub_msg'] ?? (json_encode($result))));
        }
        return $result;
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function query(array $options = [])
    {
        return $this->getResult($options, $this->getQueryMethod());
    }
}
