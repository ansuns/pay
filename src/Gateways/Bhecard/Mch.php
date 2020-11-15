<?php


namespace Ansuns\Pay\Gateways\Bhecard;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Gateways\Bhecard;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;


/**
 * 商户配置
 * Class Mch
 * @package Pay\Gateways\Wechat
 */
class Mch extends Bhecard
{

    protected $otherResult = [];
    protected $files, $rates;

    /**
     * 发起支付
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function apply(array $options = [])
    {
        $this->service = 'AGMERAPPLY';
        $this->setReqData($options);
        $data = $this->getResult();
        return $data;
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
        $this->setReqData($options);
        return $this->getResult();
    }

    /**
     * 商户照片上传
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function photo(array $options = [])
    {
        $this->setReqData($options);
        return $this->getResult();
    }

    /**
     * 费率添加
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function feeSet(array $options = [])
    {
        $this->service = "merchant.add.fee";
        $this->setReqData($options);
        return $this->getResult();
    }

    /**
     * 费率添加
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function feeMuiltSet(array $options = [])
    {
        $client = new Client(['verify' => false]);
        $tempRate = [
            "merchant_id" => $this->userConfig->get('merchant_id'),//商户号
            "fee_type" => "",//费率类型
            "fee_rate" => "",//费率,3800等于0.38%
            "has_failed_fee" => "true",//false,不退费，true退费
            "fee_method" => "2",//1,按单笔固定金额收取，2按交易本金比例收取
        ];
        foreach ($options as $k => $v) {
            if (!$v) {
                continue;
            }
            $tempRate['fee_type'] = $k;
            $tempRate['fee_rate'] = 10000 * (float)$v;
            $options = $tempRate;
            $this->config['biz_content'] = [];
            $this->config['service'] = "merchant.add.fee";
            $this->service = $this->config['service'];
            $this->setReqData($options);
            $this->config['sign'] = $this->getSign($this->config['biz_content']);
            $this->config['biz_content'] = json_encode($this->config['biz_content'], 320);
            $promises['rates_' . $k] = $client->postAsync($this->gateway, ['form_params' => $this->config]);
        }
        $results = Promise\unwrap($promises);

        foreach ($results as $key => $item) {
            $res = $this->doData($item->getBody()->getContents());;
            $res['rate'] = $key;
            $this->otherResult[] = $res;
            if ($res['result_code'] != 'SUCCESS') {
                $res['msg'] .= ":" . $res['rate'];
                return $res;
            }
        }
        $data = $this->otherResult[0];
        $data['raw_data'] = $this->otherResult;
        return $data;

    }

    /**
     * 费率停用
     * @param array $options
     * @return array|mixed
     * @throws GatewayException
     */
    public function feeStop(array $options = [])
    {
        $this->service = "merchant.stop.fee";
        $this->setReqData($options);
        return $this->getResult();
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
        $this->setReqData($options);
        return $this->getResult();
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
        $this->setReqData($options);
        return $this->getResult();
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
        return $result;
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
     * 查询进件状态
     * @param string $operaTrace
     * @return array
     * @throws GatewayException
     */
    public function find($operaTrace = '')
    {
        $this->service = 'QUERYAUDMER';
        $this->setReqData([
            'operaTrace' => $operaTrace,
        ]);
        return $this->getResult();
    }

    /**
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }

    /**
     * （分账接口）注册分账账户
     * @param array $options
     * @return array
     * @throws GatewayException
     */
    public function createPageInfo(array $options = [])
    {
        $this->service = "easypay.user.mgnt.createPageInfo";
        $this->setReqData($options);
        return $this->getResult();
    }
}