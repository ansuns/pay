<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Gateways\Wechat;
use Ansuns\Pay\Exceptions\GatewayException;

/**
 * 微信红包支持
 * Class Redpack
 * @package WePay
 */
class Redpack extends Wechat
{
    /**
     * @var string
     */
    protected $gateway = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";

    /**
     * @var string
     */
    protected $gateway_query = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }

    /**
     * 发放普通红包
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function apply(array $options)
    {
        $this->config['wxappid'] = $this->userConfig->get('app_id');
        $this->config = array_merge($this->config, $options);
        $this->unsetAppid();
        return $this->getResult($this->gateway, true);
    }

    /**
     * 发放裂变红包
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function groups(array $options)
    {
        $this->config['wxappid'] = $this->userConfig->get('app_id');
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack";
        $this->config = array_merge($this->config, $options);
        $this->unsetAppid();
        return $this->getResult($url, true);
    }

    /**
     * 查询红包记录
     * @param string $mch_billno 商户发放红包的商户订单号
     * @return array
     * @throws GatewayException
     */
    public function query($mch_billno)
    {
        unset($this->config['wxappid']);
        $this->config['appid'] = $this->userConfig->get('app_id');
        $this->config['mch_billno'] = $mch_billno;
        $this->config['bill_type'] = 'MCHT';
        return $this->getResult($this->gateway_query);
    }
}