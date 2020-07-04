<?php


namespace Ansuns\Pay\Gateways\Suixingfu;

use Ansuns\Pay\Gateways\Suixingfu;

/**
 * 微信POS刷卡支付网关
 * Class PosGateway
 * @package Pay\Gateways\Wechat
 */
class Pos extends Suixingfu
{

    /**
     * 当前操作类型
     * @return string
     */
    protected function getTradeType()
    {
        return 'MICROPAY';
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array
     * @throws \Pays\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $this->service = '/qr/reverseScan';
        $auth_code = $options['auth_code'];
        $this->setReqData([
                'authCode' => $auth_code,
                'ordNo' => $options['out_trade_no'],
                'subject' => $options['body'],
                'amt' => tools()::ncPriceFen2yuan($options['total_fee']),
            ]
        );
        if (preg_match("/^(10|11|12|13|14|15)\d{16}$/", $auth_code)) {
            $this->setReqData(['payType' => 'WECHAT']);//10~15开头18位 微信付款码
        } elseif (preg_match("/^28\d{15,16}$/", $auth_code)) {
            $this->setReqData(['payType' => 'ALIPAY']); //17位是分期码 28开头 18位支付宝付款码
        } else {
            throw new \Exception('付款码格式有误,请核实是否微信或支付宝付款码!');
        }
        $result = $this->getResult();
        if ($this->isSuccess($result)) {
            return $this->buildPayResult($result);
        }
        $result['trade_state'] = ($result['err_code'] == '2068') ? 'USERPAYING' : 'PAYERROR'; //只要不是支付中,则认为支付失败
        return $result;
    }
}
