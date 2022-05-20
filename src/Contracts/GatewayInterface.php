<?php

namespace Ansuns\Pay\Contracts;


/**
 * 支付网关接口
 * Interface GatewayInterface
 * @package Pay\Contracts
 */
abstract class GatewayInterface
{
    /**
     * 发起支付
     * @param array $options
     * @return mixed
     */
    abstract public function apply(array $options);

    /**
     * 订单退款
     * @param $options
     * @return mixed
     */
    abstract public function refund($options);

    /**
     * 关闭订单
     * @param $options
     * @return mixed
     */
    abstract public function close($options);

    /**
     * 查询订单
     * @param $out_trade_no
     * @return mixed
     */
    abstract public function find($out_trade_no);

    /**
     * 通知验证
     * @param array $data
     * @param null $sign
     * @param bool $sync
     * @return mixed
     */
    abstract public function verify($data, $sign = null, $sync = false);


    /**
     * 网络模拟请求
     * @param string $url
     * @param array $data
     * @param array $options 请求数据
     * @return array|false|mixed|string
     * @throws \Exception
     */
    public function post(string $url, array $data, array $options = [])
    {
        return \Ansuns\Pay\Service\HttpService::get_instance()->post($url, $data, $options)->get_body();
    }


    /**
     * curl get
     * @param $url
     * @param array $data
     * @return array|bool
     */
    public function get($url, $data = [])
    {
        return \Ansuns\Pay\Service\HttpService::get_instance()->get($url, $data)->get_body();
    }

    /**
     * 成功返回
     * @param $data
     * @return array
     */
    protected function successReturn($data)
    {
        $response_data['return_code'] = 'SUCCESS'; // 通信：数据能解析则通信结果认为成功
        $response_data['result_code'] = 'SUCCESS'; // 业务：初始状态为成功，如果失败会重新赋值
        $response_data['return_msg'] = $data['head']['res_msg'] ?? "OK"; // 提示
        $response_data['return_msg_des'] = $data['head']['res_msg'] ?? "OK"; // 提示，详细
        $response_data['data'] = $data['successData'] ?? (object)[];
        $response_data['rawdata'] = $data;// 原始数据
        return $response_data;
    }

    /**
     * 失败返回
     * @param $data
     * @return array
     */
    protected function failedReturn($data)
    {
        $response_data['return_code'] = 'SUCCESS'; // 通信：数据能解析则通信结果认为成功
        $response_data['result_code'] = $data['head']['res_code'] ?? 'FAIL'; // 业务：初始状态为成功，如果失败会重新赋值
        $response_data['error_msg'] = $data['head']['res_msg'] ?? 'ERROR'; // 提示
        $response_data['error__msg_des'] = $data['head']['res_msg'] ?? 'ERROR'; // 提示，详细
        $response_data['data'] = (object)[];
        $response_data['rawdata'] = $data;// 原始数据
        return $response_data;
    }
}
