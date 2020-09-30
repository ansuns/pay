<?php


namespace Ansuns\Pay\Gateways\Chinaebi;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\Chinaebi;
use Ansuns\Pay\Service\ToolsService;
use GuzzleHttp\Client;

/**
 * 商户配置
 * Class Mch
 * @package Pay\Gateways\Wechat
 */
class Mch extends Chinaebi
{

    /**
     * 发起申请
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function apply(array $options = [])
    {
        $this->service = "/rest/standardMerchant/inComing";
        $micro = $this->userConfig['micro'] ?? false;
        if ($micro) {
            $this->service = "/rest/microMerchant/inComing";

        }
        $this->setReqData($options);
        return $this->getResult();
    }


    /**
     * 微信特约商户绑定 APPID 配置
     * @param string $appid
     * @return array|mixed
     */
    public function bind_config(string $appid)
    {
        $this->service = "/rest/weChatMerchantConfig/bindAppIdConfig";
        $this->setReqData([
            'orgNumber' => $this->userConfig->get('org_id'),//机构代码
            'dyMchNo' => $this->userConfig->get('merc_id'),//电银商户号
            'subAppId' => $appid,//关联 APPID
        ]);
        return $this->getResult();
    }

    /**
     * 微信特约商户配置查询
     * @return array|mixed
     */
    public function bind_config_query()
    {
        $this->service = "/rest/weChatMerchantConfig/queryConfig";
        $this->setReqData([
            'orgNumber' => $this->userConfig->get('org_id'),//机构代码
            'dyMchNo' => $this->userConfig->get('merc_id'),//电银商户号
        ]);
        return $this->getResult();
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

    /**
     * 生成内容签名
     * @param $data
     * @return string
     */
    protected function getSign($data)
    {
        $signData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = stripslashes(json_encode($value, JSON_UNESCAPED_UNICODE));
            }
            if ($value == '') {
                continue;
            }
            if (!in_array($key, ['merchantCert', 'serverCert', 'sign', 'serverSign', 'merchantSign', 'sign_type'])) {
                $signData[$key] = (string)$value;
            }
        }

        ksort($signData);
        if (is_null($this->userConfig->get('private_key'))) {
            throw new InvalidArgumentException('Missing Config -- [private_key]');
        }

        $dataJson = json_encode($signData, JSON_UNESCAPED_UNICODE);
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->userConfig->get('private_key'), 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $res = openssl_get_privatekey($private_key);
        openssl_sign($dataJson, $sign, $res, OPENSSL_ALGO_MD5);
        openssl_free_key($res);
        return base64_encode($sign);  //base64编码

    }


    /**
     * 获取验证访问数据
     * @return array|mixed
     * @throws GatewayException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getResult()
    {
        $files = $this->body['files'] ?? [];
        unset($this->body['files']);
        $this->body['sign'] = $this->getSign($this->body);
        $client = new Client(['verify' => false]);
        if (!empty($files)) {
            $tmp_files = [];
            //特殊：上传文件处理
            foreach ($files as $key => $val) {
                if (empty($val)) {
                    continue;
                }
                $ext = pathinfo($val, PATHINFO_EXTENSION);
                if ($ext) {
                    $tmp_files[] = [
                        'name' => $key,
                        'contents' => fopen($val, 'r'),
                    ];
                }

            }

            // 准备GuzzleHttp参数
            $tmp = [];
            foreach ($this->body as $k => $v) {
                $tmp[] = [
                    'name' => $k,
                    'contents' => $v
                ];
            }
            $newData = array_merge($tmp, $tmp_files);

            $data = ['multipart' => $newData];
        } else {
            $data = ['json' => $this->body];
        }

        $url = $this->gatewayMch . $this->service;
        $result = $client->request('POST', $url, $data)->getBody()->getContents();

        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);

//        if (!$this->verify($result) || $result['res_code'] != '000000') {
//            throw new GatewayException('验证签名失败', 20000, $result);
//        }

        $response_data = $result;
        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = isset($response_data['res_msg']) ? $response_data['res_msg'] : 'OK!';
        $response_data['rawdata'] = $result;
        if (!isset($result['code']) || $result['code'] != '000000') {
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = $result['msg'] ?? 'UNKNOW_ERROR_CODE';
            $response_data['err_code_des'] = $result['msg'] ?? '未知错误';
        }
        return $response_data;
    }

    /**
     * 在线签约
     * @param array $options
     * @return mixed
     * @throws GatewayException
     */
    public function contract(array $options)
    {
        $this->service = '/rest/merchantInfo/contract';
        $reqData = [
            'orgNo' => $this->userConfig->get('org_id'),// 机构号 Y
            'mchId' => $this->userConfig->get('merc_id'),// 商户编号 N
            'merSeqNo' => $options['merSeqNo'] ?? '',// 商户请求流水号 Y 唯一,禁止重复
            'mchName' => $options['mchName'] ?? '',// 商户名称 Y
            'legalName' => $options['legalName'] ?? '',// 法人姓名 Y
            'mchAddress' => $options['mchAddress'] ?? '',// 商户地址 Y
            'phoneNo' => $options['phoneNo'] ?? '',// 联系电话 Y
            'backUrl' => $options['backUrl'] ?? '',// 回调地址 Y 可跳转的全链接
        ];

        $this->setReqData($reqData);
        $data = $this->getResult();
        if (!$this->isSuccess($data)) {
            return $this->failedReturn($data);
        }
        $pay_result = $data['body']['pay_result'] ?? 'F';
        if ($pay_result == 'S') {
            $data['trade_state'] = 'SUCCESS';
        }
        if ($pay_result == "R") {
            $data['trade_state'] = 'WAITING_PAYMENT';//等待支付
        }
        if ($pay_result == "F") {
            $data['trade_state'] = 'FAIL';//失败
        }
    }

    /**
     * 签约结果查询
     * @param array $options
     * @return mixed
     * @throws GatewayException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function contractQuery(array $options)
    {
        $this->service = '/rest/merchantInfo/querySignStatus';
        $reqData = [
            'orgNo' => $this->userConfig->get('org_id'),// 机构号 Y
            'merSeqNo' => $options['merSeqNo'] ?? '',// 原签约请求流水号 merSeqNo
        ];

        $this->setReqData($reqData);
        $data = $this->getResult();
        if (!$this->isSuccess($data)) {
            return $this->failedReturn($data);
        }
        $pay_result = $data['body']['pay_result'] ?? 'F';
        if ($pay_result == 'S') {
            $data['trade_state'] = 'SUCCESS';
        }
        if ($pay_result == "R") {
            $data['trade_state'] = 'WAITING_PAYMENT';//等待支付
        }
        if ($pay_result == "F") {
            $data['trade_state'] = 'FAIL';//失败
        }
    }
}