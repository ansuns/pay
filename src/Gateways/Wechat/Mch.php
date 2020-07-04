<?php

namespace Ansuns\Pay\Gateways\Wechat;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\Wechat;

/**
 * 商户配置
 * Class Mch
 * @package Pay\Gateways\Wechat
 */
class Mch extends Wechat
{

    /**
     * @var string
     */
    protected $gateway_addsubdevconfig = 'https://api.mch.weixin.qq.com/secapi/mch/addsubdevconfig';
    /**
     * @var string
     */
    protected $gateway_querysubdevconfig = 'https://api.mch.weixin.qq.com/secapi/mch/querysubdevconfig';
    /**
     * @var string
     */
    protected $gateway_addrecommendconf = 'https://api.mch.weixin.qq.com/secapi/mkt/addrecommendconf';

    /**
     * @var string
     */
    protected $gateway_queryautowithdrawbydate = 'https://api.mch.weixin.qq.com/fund/queryautowithdrawbydate';


    /**
     * @var string
     */
    protected $encrypt_method = 'HMAC-SHA256';

    protected function init(array $options)
    {
        $this->unsetTradeTypeAndNotifyUrl();
        $this->unsetSpbillCreateIp();
        $this->config['sub_mch_id'] = isset($options['sub_mch_id']) ? $options['sub_mch_id'] : $this->userConfig->get('sub_mch_id');
    }

    //查询配置
    public function querysubdevconfig(array $options)
    {
        $this->init($options);
        $this->unsetSignTypeAndNonceStr();
        $result = $this->getResult($this->gateway_querysubdevconfig, true);
        if (is_array($result)) {
            $appid_config_list = json_decode($result['appid_config_list'], true);
            $jsapi_path_list = json_decode($result['jsapi_path_list'], true);
            return [
                'appid_config_list' => $appid_config_list['appid_config_list'],
                'jsapi_path_list' => $jsapi_path_list['jsapi_path_list'],
            ];
        }
        return $result;
    }

    //添加推荐
    public function addrecommendconf(array $options)
    {
        $this->init($options);
        $this->unsetAppid();
        if (isset($options['subscribe_appid'])) {
            $this->config['subscribe_appid'] = $options['subscribe_appid']; //推荐关注APPID
            $this->config['sub_appid'] = ($options['subscribe_appid'] != $this->userConfig->get('app_id')) ? $options['subscribe_appid'] : 'null'; //推荐关注APPID
        } elseif (isset($options['receipt_appid'])) {
            $this->config['receipt_appid'] = $options['receipt_appid']; //支付凭证推荐小程序appid
            $this->config['sub_appid'] = ($options['receipt_appid'] != $this->userConfig->get('app_id')) ? $options['receipt_appid'] : 'null'; //推荐关注APPID
        } else {
            throw new InvalidArgumentException('Missing Options -- [subscribe_appid or receipt_appid]');
        }
        //  $this->config['sub_appid'] = isset($options['sub_appid']) ? $options['sub_appid'] : 'null';
        $this->config['sign_type'] = $this->encrypt_method;
        return $this->getResult($this->gateway_addrecommendconf, true);
    }

    //添加配置
    public function addsubdevconfig(array $options)
    {
        $this->init($options);
        $this->unsetSignTypeAndNonceStr();
        if (isset($options['sub_appid']) && $options['sub_appid'] != '') {
            $this->config['sub_appid'] = $options['sub_appid'];
        } elseif (isset($options['jsapi_path'])) {
            $this->config['jsapi_path'] = $options['jsapi_path'];
        } else {
            $this->config['sub_appid'] = $this->userConfig->get('app_id'); //如果sub_appid和jsapi_path都为空,则认为配置平台appid
            //throw new InvalidArgumentException('Missing Options -- [sub_appid or jsapi_path]');
        }
        return $this->getResult($this->gateway_addsubdevconfig, true);
    }

    //该接口仅限小微商户
    public function queryautowithdrawbydate(array $options)
    {
        $this->init($options);
        $this->unsetAppid();
        $this->config['date'] = isset($options['date']) ? $options['date'] : date('Ymd');
//        if (!isset($options['date'])) {
//            throw new InvalidArgumentException('Missing Options -- [date]');
//        }
        $this->config['sign_type'] = $this->encrypt_method;
        return $this->getResult($this->gateway_queryautowithdrawbydate, true);
    }

    /**
     * 发起支付
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function apply(array $options)
    {
        //todo
        return $this->getResult($this->gateway);
    }

    /**
     * 查询订单状态
     * @param string $out_trade_no 商户订单号
     * @return array
     * @throws GatewayException
     */
    public function find($out_trade_no = '')
    {
        //todo
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