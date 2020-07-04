<?php

namespace Ansuns\Pay\Gateways\Alipay;

use Ansuns\Pay\Gateways\Alipay;

/**
 * 支付宝刷卡支付
 * Class PosGateway
 * @package Pay\Gateways\Alipay
 */
class Pos extends Alipay
{

    /**
     * 当前接口方法
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.trade.pay';
    }

    /**
     * 当前接口产品码
     * @return string
     */
    protected function getProductCode()
    {
        return 'FACE_TO_FACE_PAYMENT';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @param string $scene
     * @return array|bool
     * @throws \Pays\Exceptions\GatewayException
     */
    public function apply(array $options = [], $scene = 'bar_code')
    {
        $options['scene'] = $scene;
        $options['subject'] = $options['body'];
        $total_fee = $options['total_fee'];
        $options['total_amount'] = tools()::ncPriceFen2yuan($total_fee);
        unset($options['total_fee']);
        $result = $this->getResult($options, $this->getMethod());
        $result['time_end'] = date('YmdHis', strtotime($result['gmt_payment']));
        $result['transaction_id'] = $result['trade_no'];
        $result['openid'] = $result['buyer_user_id'];
        $result['total_fee'] = $total_fee;
        return $result;
    }
}
