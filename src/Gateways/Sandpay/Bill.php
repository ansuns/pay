<?php


namespace Ansuns\Pay\Gateways\Sandpay;

use Ansuns\Pay\Gateways\Sandpay;

/**
 * 下载微信电子面单
 * Class BillGateway
 * @package Pay\Gateways\Sandpay
 */
class Bill extends Sandpay
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
     * 应用并返回参数
     * @param array $options
     * @return bool|array
     */
    public function apply(array $options = [])
    {
        $this->service = "/bill";//账单
        unset($this->config['trade_type']);
        unset($this->config['notify_url']);
        $data = [
            'merchantCode' => $this->userConfig->get('merchant_no'),
            'pageNumber' => 1,
            'pageSize' => 100,
            'orderStatus' => 'SUCCESS',
            'billBeginTime' => isset($options['begin_time']) ? $options['begin_time'] : date('YmdHis', strtotime("-10 day")),
            'billEndTime' => isset($options['end_time']) ? $options['end_time'] : date('YmdHis'),
        ];
        $this->config = array_merge($this->config, $data);
        return $this->getResult();
    }
}