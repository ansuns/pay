<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\Wechat;

/**
 * 转账到银行卡
 * Class BankGateway
 * @package Pay\Gateways\Wechat
 */
class Deposit extends Wechat
{

    /**
     * @var string
     */
    protected $gateway = 'https://api.mch.weixin.qq.com/deposit/micropay';

    /**
     * @var string
     */
    protected $gateway_query = 'https://api.mch.weixin.qq.com/deposit/orderquery';
    /**
     * @var string
     */
    protected $gateway_refund_query = 'https://api.mch.weixin.qq.com/deposit/refundquery';

    /**
     * @var string
     */
    protected $gateway_close = 'https://api.mch.weixin.qq.com/deposit/reverse';

    /**
     * @var string
     */
    protected $gateway_refund = 'https://api.mch.weixin.qq.com/deposit/refund';

    /**
     * @var string
     */
    protected $gateway_micropay = 'https://api.mch.weixin.qq.com/deposit/consume';
    /**
     * @var string
     */
    protected $encrypt_method = 'HMAC-SHA256';


    /**
     * 发起支付
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function apply(array $options)
    {
        if (!isset($options['auth_code'])) {
            throw new InvalidArgumentException('Missing Options -- [auth_code]');
        }
        if (!isset($options['total_fee'])) {
            throw new InvalidArgumentException('Missing Options -- [total_fee]');
        }
        $this->unsetTradeTypeAndNotifyUrl();
        //$this->config['deposit']      = 'Y';
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['total_fee'] = $options['total_fee'];
        $this->config['auth_code'] = $options['auth_code'];
        $this->config['out_trade_no'] = $options['out_trade_no'];
        $this->config['body'] = $options['body'];
        $this->config['time_expire'] = '2018-11-09 00:00:00';
        $this->config['sub_mch_id'] = $this->userConfig->get('sub_mch_id');
        return $this->getResult($this->gateway, true);
    }

    /**
     * 查询订单状态
     * @param string $out_trade_no 商户订单号
     * @return array
     * @throws GatewayException
     */
    public function find($out_trade_no = '')
    {
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['out_trade_no'] = $out_trade_no;
        $this->unsetTradeTypeAndNotifyUrl();
        $this->unsetSpbillCreateIp();
        return $this->getResult($this->gateway_query);
    }

    /**
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }
}