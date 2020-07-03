<?php
/**
 * Created by PhpStorm.
 * User: ANSUNS
 * Date: 2020/7/3
 * Time: 11:46
 */

namespace Ansuns;


abstract class BasicPayInterface
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
    abstract public function refund(array $options);

    /**
     * 关闭订单
     * @param $options
     * @return mixed
     */
    abstract public function close(array $options);

    /**
     * 查询订单
     * @param $out_trade_no
     * @return mixed
     */
    abstract public function find(string $out_trade_no);

    /**
     * 通知验证
     * @param array $data
     * @param null $sign
     * @param bool $sync
     * @return mixed
     */
    abstract public function verify($data, $sign, $sync);

    /**
     * 网络模拟请求
     * @param string $url 网络请求URL
     * @param array|string $data 请求数据
     * @param array $options
     * @return bool|string
     */
    abstract public function post($url, $data, $options = []);

}