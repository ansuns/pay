<?php


namespace Ansuns\Pay\Gateways\Alipay;

use Ansuns\Pay\Gateways\Alipay;

/**
 * 手机WAP支付网关
 * Class WapGateway
 * @package Pay\Gateways\Alipay
 */
class Wap extends Alipay
{

    /**
     * 当前接口方法
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.trade.create';
    }

    /**
     * 当前接口产品码
     * @return string
     */
    protected function getProductCode()
    {
        return 'QUICK_WAP_WAY';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return string
     */
    public function apply(array $options = [])
    {
        $this->config['notify_url'] = $options['notify_url'];
        $data['out_trade_no'] = $options['out_trade_no'];
        $data['buyer_id'] = $options['openid'];
        $data['subject'] = $options['body'];
        $data['body'] = $options['body'];
        $total_fee = $options['total_fee'];
        $data['total_amount'] = tools()::ncPriceFen2yuan($total_fee);
        $result = $this->getResult($data, $this->getMethod());
        $result['out_trade_no'] = $result['out_trade_no'];
        $result['trade_no'] = $result['trade_no'];
        return $result;

        //parent::apply($options);
        //return $this->buildPayHtml();
    }
}