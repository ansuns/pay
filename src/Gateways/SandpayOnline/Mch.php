<?php


namespace Ansuns\Pay\Gateways\SandpayOnline;

use Ansuns\Pay\Exceptions\Exception;
use Ansuns\Pay\Exceptions\GatewayException;
use Ansuns\Pay\Exceptions\InvalidArgumentException;
use Ansuns\Pay\Gateways\SandpayOnline;
use Ansuns\Pay\Service\ToolsService;

/**
 * 商户配置
 * Class Mch
 * @package Pay\Gateways\Sandpay
 */
class Mch extends SandpayOnline
{
    protected $method = '';

    /**
     * 发起支付
     * @param array $options
     * @return mixed
     * @throws Exception
     * @throws GatewayException
     */
    public function apply(array $options = [])
    {
        return $this->getResult($this->gateway_agent);
    }


    /**
     * 商户入驻
     * @param array $opitions
     * @return array
     * @throws GatewayException
     */
    public function merchantCreate(array $opitions)
    {

        $this->setReqData($opitions);
        $this->config['method'] = "merchant.create";
        $data = $this->getResult();
        return $data;

    }

    public function storeCreate(array $opitions)
    {
        $this->setReqData($opitions);
        $this->config['method'] = "merchant.store.create";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 查询商户信息和门店信息
     * @param array $opitions
     * @return array
     * @throws GatewayException
     */
    public function merchantQuery(array $opitions)
    {
        $this->setReqData($opitions);
        $this->config['method'] = "merchant.query";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 服务商为其商户设置费率，可以新增费率，修改费率
     * @param array $opitions
     * @return array
     * @throws GatewayException
     */
    public function merchantRateSet(array $opitions)
    {
        $this->setReqData($opitions);
        $this->config['method'] = "merchant.rate.set";
        $data = $this->getResult();
        return $data;
    }


    /**
     * 服务商查询商户已设置的费率
     * @param $sub_merchant_id
     * @return array
     * @throws GatewayException
     */
    public function merchantRateQuery($sub_merchant_id)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id]);
        $this->config['method'] = "merchant.rate.query";
        $data = $this->getResult();
        return $data;
    }

    public function merchantBankBind(array $opitions)
    {
        $this->setReqData($opitions);
        $this->config['method'] = "merchant.bank.bindy";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 商户补进件、信息变更
     * 服务商为其商户进行补进件、信息变更，图片地址使用接口图片上传获取
     * 商户绑卡前或审核未通过可以进行补进件，此时修改的信息会立即生效
     * 商户绑卡后需等待审核通过后才可进行信息变更，信息变更需等待再次审核通过后才会生效
     * @param array $opitions
     * @return array
     * @throws GatewayException
     */
    public function merchantModify(array $opitions)
    {
        $this->setReqData($opitions);
        $this->config['method'] = "merchant.modify";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 服务商为其商户门店进行补进件、信息变更，图片地址使用接口图片上传获取
     * 门店审核前或审核未通过可以进行补进件，此时修改的信息会立即生效
     * 门店审核通过后可以进行信息变更，信息变更需等待再次审核通过后才会生效
     * @param array $opitions
     * @return array
     * @throws GatewayException
     */
    public function merchantStoreModify(array $opitions)
    {
        $this->setReqData($opitions);
        $this->config['method'] = "merchant.store.modify";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 渠道入驻
     * 服务商为其商户入驻渠道
     * @param array $opitions
     * @return array
     * @throws GatewayException
     */
    public function channelRegister(array $opitions)
    {
        $this->setReqData($opitions);
        $this->config['method'] = "channel.register";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 服务商查询商户渠道入驻信息
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @param $store_no 门店编号
     * @return array
     * @throws GatewayException
     */
    public function channelRegisterQuery($sub_merchant_id, $store_no)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id, 'store_no' => $store_no]);
        $this->config['method'] = "channel.register.query";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 商户新增支付授权目录
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @param $jsapi_path 特约商户公众账号JS API支付授权目录 ，要求符合URI格式规范，每次添加一个支付目录，最多5个
     * @return array
     * @throws GatewayException
     */
    public function merchantAddPayPath($sub_merchant_id, $jsapi_path)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id, 'jsapi_path' => $jsapi_path]);
        $this->config['method'] = "merchant.add.pay.path";
        $data = $this->getResult();
        return $data;
    }

    /**
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @param $sub_appid 微信APPID
     * @return array
     * @throws GatewayException
     */
    public function merchantBindWechatAppid($sub_merchant_id, $sub_appid)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id, 'sub_appid' => $sub_appid]);
        $this->config['method'] = "merchant.bind.wechat.appid";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 商户微信关注配置
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @param $sub_appid 绑定的微信APPID 该字段视支付接口中是否传sub_appid而定，如果没有填sub_appid,此处请填值NULL，否则请填写绑定特约商户或渠道公众号、小程序、APP支付等对应的APPID
     * @param string $subscribe_appid 推荐关注APPID 特约商户或渠道的公众号APPID
     * @param string $receipt_appid 支付凭证推荐小程序APPID 需为通过微信认证的小程序APPID，且认证主体与渠道商或子商户一致
     * @return array
     * @throws GatewayException
     */
    public function merchantAddRecomConf($sub_merchant_id, $sub_appid, $subscribe_appid = '', $receipt_appid = '')
    {
        $data = [
            'sub_merchant_id' => $sub_merchant_id, 'sub_appid' => $sub_appid
        ];
        if ($subscribe_appid) {
            $data['subscribe_appid'] = $subscribe_appid;
        } else {
            $data['receipt_appid'] = $receipt_appid;
        }
        $this->setReqData($data);
        $this->config['method'] = "merchant.add.recom.conf";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 商户微信配置查询
     * 服务商为其商户查询微信配置
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @return array
     * @throws GatewayException
     */
    public function merchantWechatConfigQuery($sub_merchant_id)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id]);
        $this->config['method'] = "merchant.wechat.config.query";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 提交微信实名认证申请单
     * 服务商提交商户微信实名认证申请单
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @return array
     * @throws GatewayException
     */
    public function wechatauthApplyment($sub_merchant_id)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id]);
        $this->config['method'] = "wechat.auth.applyment";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 撤销微信实名认证申请单
     * 服务商提交申请单后需要修改信息时，或者申请单审核结果为"已驳回"时服务商要修改申请材料时，均需要先调用撤销申请单接口
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @param $applyment_id 申请单编号
     * @return array
     * @throws GatewayException
     */
    public function wechatApplymentCancel($sub_merchant_id, $applyment_id)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id, 'applyment_id' => $applyment_id]);
        $this->config['method'] = "wechat.applyment.cancel";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 获取申请单审核结果
     * 服务商提交申请单后，需要定期调用此接口查询申请单的审核状态
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @param $applyment_id 申请单编号
     * @return array
     * @throws GatewayException
     */
    public function wechatApplymentResult($sub_merchant_id, $applyment_id)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id, 'applyment_id' => $applyment_id]);
        $this->config['method'] = "wechat.applyment.result";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 获取商户微信授权状态
     * 服务商获取商户微信实名认证授权状态
     * @param $sub_merchant_id 河马付商户号，平台为商户分配的惟一ID，商户入驻后，由平台返回
     * @return array
     * @throws GatewayException
     */
    public function wechatAuthorizeState($sub_merchant_id)
    {
        $this->setReqData(['sub_merchant_id' => $sub_merchant_id]);
        $this->config['method'] = "wechat.authorize.state";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 查询对账单下载地址
     * 服务商或商户查询对账单下载地址，将根据公参中的app_id来获取对应的对账单
     * 只能获取2020-04-10日之后的对账单
     * 只能获取三个月之内的对账单
     * 建议每天上午十点后获取对账单，对账单文件字符编码为GBK
     * @param string $bill_date 账单时间，格式：yyyy-MM-dd
     * @return array
     * @throws GatewayException
     */
    public function billDownloadUrlQuery($bill_date = '')
    {
        $bill_date = $bill_date ?? date('Y-m-d', time());
        $this->setReqData(['bill_date' => $bill_date]);
        $this->config['method'] = "bill.download.url.query";
        $data = $this->getResult();
        return $data;
    }

    /**
     * 商户审核回调通知
     * 商户初审、商户变更审核、门店初审、门店变更审核回调通知
     * 需进行验签以防止伪造通知
     * 商户的回调地址需事先报备
     * 回调通知可能会新增字段，需保证验签兼容
     * 回调通知请求Http Method=POST，Content-Type=application/json
     * 重复通知: 收到回调通知时，应返回Http状态码200且返回响应体SUCCESS，如返回其他值，平台将多次重复进行通知
     * @return array
     * @throws \Exception
     */
    public function merchantNotify()
    {
        $data = $_POST;
        if (!empty($data['sign'])) {
            $sign = $data['sign'];
            unset($data['sign']);
            if (!$this->verify($this->getSignContent($data), $sign, $this->publicKey)) {
                throw new \Exception('Invalid Notify Sign is error.', '0');
            }
            $return = [
                'return_code' => $data['return_code'], //通信结果
                'return_msg' => "SUCCESS",
                'result_code' => $data['sub_code'],
                'appid' => $data['app_id'],
                'mch_id' => '',
                'device_info' => '',
                'nonce_str' => '',
                'sign' => $sign,
                'openid' => $data['buyer_id'] ?? $data['buyer_id'],
                'is_subscribe' => '',
                'trade_type' => 'trade_type',
                'bank_type' => '',
                'total_fee' => ToolsService::ncPriceYuan2fen($data['pay_amount']),  //分
                'transaction_id' => $data['plat_trx_no'], // 平台交易流水号
                'out_trade_no' => $data['out_order_no'], // 商户订单号
                'attach' => '',
                //'time_end'       => ToolsService::format_time($data['payTime']),
                'time_end' => $data['pay_success_time'],
                'trade_state' => ($data['trade_status'] == 'SUCCESS') ? 'SUCCESS' : 'FAIL',
                'raw_data' => $data
            ];
            if ($data['bizCode'] !== '0000') {
                $return['err_code'] = isset($data['subCode']) ? $data['subCode'] : '';
                $return['err_code_des'] = isset($data['subMsg']) ? $data['subMsg'] : '';
            }
            return $return;
        }
        exit(); // 当商户收到回调通知时，应返回Http状态码200且返回响应体SUCCESS，如返回其他值，平台将多次重复进行通知
        return $data;
    }

    /**
     * 获取验证访问数据
     * @return array
     * @throws GatewayException
     */
    protected function getResult()
    {

        if ($this->method === 'upload') {
            unset($this->config['biz_content']);
            unset($this->config['sub_app_id']);
            unset($this->config['method']);
            unset($this->config['charset']);
            unset($this->config['version']);
            unset($this->config['format']);
            $this->config['external_id'] = $this->files['external_id'];
            $this->config['pic_type'] = $this->files['pic_type'];
            $this->config['sign'] = $this->rsaSign($this->config, $this->userConfig['agent_private_key']);
            $data = [];
            $pic_file = $this->files['pic_file'];
            $pic_type = $this->files['pic_type'];

            $extensions = pathinfo($pic_file, PATHINFO_EXTENSION);
            if ($extensions) {
                $data[] = [
                    'name' => 'pic_file',
                    'contents' => fopen($pic_file, 'r'),
                    'filename' => $pic_type . ".{$extensions}"
                ];
            }
            // 准备GuzzleHttp参数
            foreach ($this->config as $k => $v) {
                $data[] = [
                    'name' => $k,
                    'contents' => $v
                ];
            }
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $data = ['multipart' => $data];
            $resp = $client->request('POST', $this->gateway_upload, $data);
            $result = $resp->getBody()->getContents();
        } else {
            $this->config['biz_content'] = json_encode($this->config['biz_content'], JSON_UNESCAPED_UNICODE);
            $this->config['sign'] = $this->rsaSign($this->config, $this->userConfig['agent_private_key']);
            $header = ['Content-Type: application/json'];
            $result = $this->post($this->gatewayAgent, json_encode($this->config, JSON_UNESCAPED_UNICODE), ['headers' => $header]);
        }

        if (!ToolsService::is_json($result)) {
            throw new GatewayException('返回结果不是有效json格式', 20000, $result);
        }
        $result = json_decode($result, true);
//        if (!empty($result['sign']) && !$this->verify($this->getSignContent($result), $result['sign'],$this->userConfig['private_key'])) {
//            throw new GatewayException('验证签名失败', 20000, $result);
//        }

        $response_data = [];
        $response = isset($result['data']) ? json_decode($result['data'], true) : [];
        $response_data = array_merge($response_data, $response);

        $response_data['return_code'] = 'SUCCESS'; //数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; //初始状态为成功,如果失败会重新赋值
        $response_data['return_msg'] = $response_data['msg'] ?? 'OK!';
        $response_data['sub_code'] = $response_data['sub_code'] ?? 'SUCCESS';
        $response_data['sub_msg'] = $response_data['sub_msg'] ?? '交易成功';
        $code = $result['code'] ?? '200';
        if ($code !== '200') {
            $msg = [
                "200" => "网关请求成功，请判断业务返回码",
                "210" => "响应签名生成异常",
                "403" => "权限不足",
                "410" => "参数验证失败",
                "413" => "验签异常",
                "510" => "未知错误",
                "511" => "业务错误",
                "512" => "网关错误",
                "555" => "初始状态",
            ];

            $response_data['sub_code'] = $result['sub_code'] ?? 'ERROR';
            $response_data['sub_msg'] = $result['msg'] ?? '失败';
            $response_data['result_code'] = 'FAIL';
            $response_data['err_code'] = 'ERROR';
            $response_data['err_code_des'] = $result['msg'] ?? 'ERROR:交易失败';
            $response_data['return_msg'] = 'ERROR:' . $msg[$code] ?? "unknow error!";
            return $response_data;
        }

        return $response_data;
    }

    /**
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }

    public function upload(array $opitions)
    {
        $this->setUpload($opitions);
        // $this->config['method'] = "bill.download.url.query";
        $this->method = 'upload';
        $data = $this->getResult();

        return $data;
    }
}