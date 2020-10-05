<?php


namespace Ansuns\Pay\Gateways\Bhecard;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\Bhecard;

/**
 * 商户配置
 * Class Mch
 * @package Pay\Gateways\Wechat
 */
class Mch extends Bhecard
{

    /**
     * 发起支付
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function apply(array $options = [])
    {
        $this->service = "merchant.add.input";
        $micro = $this->userConfig['micro'] ?? false;
        if ($micro) {
            // 小微商户
            $this->service = "small.merchant.add";
        }
        return $this->getResult($options);
    }

    /**
     * 商户入网审批
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function confirm(array $options = [])
    {
        $this->service = "merchant.add.confirm";
        return $this->getResult($options);
    }

    /**
     * 商户照片上传
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function photo(array $options = [])
    {
        $this->service = "merchant.add.photo";
        $micro = $this->userConfig['micro'] ?? false;
        if ($micro) {
            // 小微商户
            $this->service = "small.merchant.photo";
        }
        $enCodeArr = [];
        // 敏感字段加密
        foreach ($options as $key => $val) {
            if (!empty($val) && in_array($key, $enCodeArr)) {
                $options[$key] = $this->desEncrypt($val);
            }
        }
        return $this->getResult($options);
    }

    public function feeSet(array $options = [])
    {
        $this->service = "merchant.add.fee";
        return $this->getResult($options);
    }

    /**
     * 费率添加
     * @param array $options
     * @return array|mixed
     * @throws GatewayException
     */
    public function feeStop(array $options = [])
    {
        $this->service = "merchant.stop.fee";
        return $this->getResult($options);
    }

    /**
     * 商户开通支付方式
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function openWay(array $options = [])
    {
        $this->service = "merchant.pay.open";
        return $this->getResult($options);
    }

    /**
     * 商户自动提现管理
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function autotransferControl(array $options = [])
    {
        $this->service = "merchant.autotransfer.control";
        return $this->getResult($options);
    }

    /**
     * 查询进件信息
     * @param string $task_code
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function bind_config(string $appid)
    {
        $this->service = "/weChat/bindconfig";
        $this->setReqData([
            'subMchId' => $this->userConfig->get('sub_mch_id'),//必传
            'subAppid' => $appid,//必传
        ]);
        $result = $this->getResult();
    }

    /**
     * 查询进件信息
     * @param string $task_code
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function query_qrcode_product_info(string $task_code)
    {
        $this->service = "/MerchIncomeQuery/queryQrcodeProductInfo";
        $this->setReqData([
            'taskCode' => $task_code,
        ]);
        $result = $this->getResult();
        $data = [];
        if (!empty($result['repoInfo'])) {
            foreach ($result['repoInfo'] as $info) {
                if ($info['childNoType'] == 'WX') {
                    $data['mno'] = $info['mno'];
                    $data['sub_mch_id'] = $info['childNo'];
                }
            }
        }
        return $data ?: $result;
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