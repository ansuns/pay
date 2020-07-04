<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\Wechat;

/**
 * 服务商分账接口
 * Class Profitsharing
 * https://pay.weixin.qq.com/wiki/doc/api/allocation_sl.php?chapter=25_4
 * @package Pay\Gateways\Wechatsub
 */
class Profitsharing extends Wechat
{

    /**
     * @var string
     */
    protected $gateway = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharing';

    /**
     * @var string
     */
    protected $gateway_query = 'https://api.mch.weixin.qq.com/pay/profitsharingquery';

    /**
     * @var string
     */
    protected $gateway_add = 'https://api.mch.weixin.qq.com/pay/profitsharingaddreceiver';

    /**
     * @var string
     */
    protected $gateway_remove = 'https://api.mch.weixin.qq.com/pay/profitsharingremovereceiver';

    /**
     * @var string
     */
    protected $gateway_finish = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish';
    /**
     * @var string
     */
    protected $encrypt_method = 'HMAC-SHA256';


    /**
     * 发起支付
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function apply(array $options)
    {
        if (!isset($options['transaction_id'])) {
            throw new InvalidArgumentException('Missing Options -- [transaction_id]');
        }
        if (!isset($options['out_order_no'])) {
            throw new InvalidArgumentException('Missing Options -- [out_order_no]');
        }
        if (!isset($options['receivers'])) {
            throw new InvalidArgumentException('Missing Options -- [receivers]');
        }
        $this->unsetConfig();
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['transaction_id'] = $options['transaction_id'];
        $this->config['out_order_no'] = $options['out_order_no'];
        $this->config['receivers'] = is_array($options['receivers']) ? json_encode($options['receivers'], JSON_UNESCAPED_UNICODE) : $options['receivers'];
        return $this->getResult($this->gateway, true);
    }

    /**
     * 查询订单状态
     * @param array $options 查询参数
     * @return array
     * @throws GatewayException|InvalidArgumentException
     */
    public function query(array $options)
    {
        if (!isset($options['transaction_id'])) {
            throw new InvalidArgumentException('Missing Options -- [transaction_id]');
        }
        if (!isset($options['out_order_no'])) {
            throw new InvalidArgumentException('Missing Options -- [out_order_no]');
        }
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['transaction_id'] = $options['transaction_id'];
        $this->config['out_order_no'] = $options['out_order_no'];
        $this->unsetConfig();
        unset($this->config['appid']);
        return $this->getResult($this->gateway_query);
    }

    /**
     * 添加分账接收方
     * @param array $options 参数
     * @return array
     * @throws GatewayException|InvalidArgumentException
     */
    public function add(array $options)
    {
        if (!isset($options['receiver'])) {
            throw new InvalidArgumentException('Missing Options -- [receiver]');
        }
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['receiver'] = is_array($options['receiver']) ? json_encode($options['receiver'], JSON_UNESCAPED_UNICODE) : $options['receiver'];
        $this->unsetConfig();
        return $this->getResult($this->gateway_add);
    }

    /**
     * 删除分账接收方
     * @param array $options 参数
     * @return array
     * @throws GatewayException|InvalidArgumentException
     */
    public function remove(array $options)
    {
        if (!isset($options['receiver'])) {
            throw new InvalidArgumentException('Missing Options -- [receiver]');
        }
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['receiver'] = is_array($options['receiver']) ? json_encode($options['receiver'], JSON_UNESCAPED_UNICODE) : $options['receiver'];
        $this->unsetConfig();
        return $this->getResult($this->gateway_remove);
    }

    /**
     * 完结分账
     * @param array $options 参数
     * @return array
     * @throws GatewayException|InvalidArgumentException
     */
    public function finish(array $options)
    {
        if (!isset($options['transaction_id'])) {
            throw new InvalidArgumentException('Missing Options -- [transaction_id]');
        }
        if (!isset($options['out_order_no'])) {
            throw new InvalidArgumentException('Missing Options -- [out_order_no]');
        }
        if (!isset($options['amount'])) {
            throw new InvalidArgumentException('Missing Options -- [amount]');
        }
        $this->unsetConfig();
        $this->config['sign_type'] = $this->encrypt_method;
        $this->config['transaction_id'] = $options['transaction_id'];
        $this->config['out_order_no'] = $options['out_order_no'];
        $this->config['amount'] = intval($options['amount']);
        $this->config['description'] = isset($options['description']) ? $options['description'] : '分账已完成';
        return $this->getResult($this->gateway_finish, true);
    }

    protected function unsetConfig()
    {
        $this->unsetTradeTypeAndNotifyUrl();
        $this->unsetSpbillCreateIp();
        return true;
    }

    /**
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }
}