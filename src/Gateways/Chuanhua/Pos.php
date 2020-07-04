<?php

namespace Ansuns\Pay\Gateways\Chuanhua;

use Ansuns\Pay\Gateways\Chuanhua;

/**
 * 微信POS刷卡支付网关
 * Class PosGateway
 * @package Pay\Gateways\Wechat
 */
class Pos extends Chuanhua
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
//sub_mch_id	string(10)	是		新零售子商户号
//channel	string	是	WECHAT_POS：微信付款码支付
//ALIPAY_POS：支付宝付款码支付	通道类型
//total_fee	decimal(10, 2)	是		交易金额（元）
//out_trade_no	string(32)	是		商户订单号
//body	string(30)	是		商品描述
//store_id	string(10)	是		门店ID
//terminal_id	string(30)	是		设备编号
//client_ip	string(15)	是		用户IP地址
//auth_code	string(18)	是		支付付款码
//notify_url	string(150)	否		通知地址
//wechat_channel_no	string(20)	否		微信渠道号
//alipay_pid	string(20)	否		支付宝pid
//sub_appid	string(30)	否		微信公众号或者小程序的appid
        //todo
        $this->service = '/openapi/merchant/pay/micropay';
        $auth_code = $options['auth_code'];
        $this->setConfig([
                'auth_code' => $auth_code,
                'out_trade_no' => $options['out_trade_no'],
                'body' => $options['body'],
                'store_id' => 'null',
                'terminal_id' => 'null',
                'client_ip' => ToolsService::get_client_ip(),
                'total_fee' => ToolsService::ncPriceFen2yuan($options['total_fee']),
            ]
        );
        if (preg_match("/^(10|11|12|13|14|15)\d{16}$/", $auth_code)) {
            $this->setConfig(['channel' => 'WECHAT_POS']);//10~15开头18位 微信付款码
        } elseif (preg_match("/^28\d{15,16}$/", $auth_code)) {
            $this->setConfig(['channel' => 'ALIPAY_POS']); //17位是分期码 28开头 18位支付宝付款码
        } else {
            throw new \Exception('付款码格式有误,请核实是否微信或支付宝付款码!');
        }
        $result = $this->getResult();
        if ($this->isSuccess($result) && $result['trade_status'] == 1) {
            return $this->buildPayResult($result, $options);
        }
        $result['trade_state'] = ($result['trade_status'] == 2) ? 'USERPAYING' : 'PAYERROR'; //只要不是支付中,则认为支付失败
        return $result;
    }
}
