<?php

namespace Ansuns\Pay\Gateways\Alipay;

use Ansuns\Pay\Gateways\Alipay;
use Ansuns\Pay\Service\ToolsService;

/**
 * 支付宝电子面单下载
 * Class BillGateway
 * @package Pay\Gateways\Alipay
 */
class Bill extends Alipay
{
    protected $field_map = [
        'transaction_id' => '支付宝交易号',
        'out_trade_no' => '商户订单号',
        'trade_type' => '业务类型',
        'goods_name' => '商品名称',
        'trade_create_time' => '交易创建时间',
        'trade_finish_time' => '交易完成时间',
        'store_number' => '门店编号',
        'store_name' => '门店名称',
        'operator' => '操作员',
        'terminal_number' => '终端号',
        'account' => '对方账户',
        'order_money' => '订单金额（元）',
        'receiv_money' => '商家实收（元）',
        'red_packets' => '支付宝红包（元）',
        'set_points_treasure' => '集分宝（元）',
        'discount' => '支付宝优惠（元）',
        'business_discount' => '商家优惠（元）',
        'coupon_money' => '券核销金额（元）',
        'coupon_name' => '券名称',
        'business_red_packets' => '商家红包消费金额（元）',
        'card_monty' => '卡消费金额（元）',
        'refund_no' => '退款批次号/请求号',
        'fee' => '服务费（元）',
        'profit_money' => '分润（元）',
        'memo' => '备注',
        'pid' => '支付宝PID',
    ];

    /**
     * 当前接口方法
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.data.dataservice.bill.downloadurl.query';
    }


    /**
     * 应用并返回参数
     * @return array|bool
     */
    protected function getProductCode()
    {
        return '';
    }

    /**
     * @param string $zipName 需要解压的文件路径加文件名
     * @param string $dir 解压后的文件夹路径
     * @return bool
     */
    public function extractZipToFile($zipName, $dir)
    {
        $zip = new \ZipArchive;
        $unzip_files = [];
        if ($zip->open($zipName) === true) {
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $file_num = $zip->numFiles;
            for ($i = 0; $i < $file_num; $i++) {
                $statInfo = $zip->statIndex($i, \ZipArchive::FL_ENC_RAW);
                $filename = $this->transcoding($statInfo['name']);
                if ($statInfo['crc'] == 0) {
                    //新建目录
                    if (!is_dir($dir . '/' . substr($filename, 0, -1))) mkdir($dir . '/' . substr($filename, 0, -1), 0775, true);
                } else {
                    //拷贝文件
                    $file_name = ToolsService::array_iconv($filename);
                    $file_name = str_replace('业务明细', 'day', $file_name);
                    $file_name = str_replace('汇总', 'all', $file_name);
                    copy('zip://' . $zipName . '#' . $zip->getNameIndex($i), $dir . '/' . $file_name);
                    $unzip_files[] = $dir . '/' . $file_name;
                }
            }
            $zip->close();
            return $unzip_files;
        } else {
            return false;
        }
    }

    public function transcoding($fileName)
    {
        $encoding = mb_detect_encoding($fileName, ['UTF-8', 'GBK', 'BIG5', 'CP936']);
        if (DIRECTORY_SEPARATOR == '/') {    //linux
            $filename = iconv($encoding, 'UTF-8', $fileName);
        } else {  //win
            $filename = iconv($encoding, 'GBK', $fileName);
        }
        return $filename;
    }

    public function read_csv($file_name = '')
    {
        if (!file_exists($file_name)) {
            throw new \Exception('文件不存在:' . $file_name);
        }
        $file = fopen($file_name, "r");
        while (!feof($file)) {
            $data[] = fgetcsv($file);
        }
        fclose($file);
        $data = eval('return ' . iconv('gbk', 'utf-8', var_export($data, true)) . ';');
        foreach ($data as $key => $value) {
            if (!$value) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * 应用并返回参数
     * @param array $options
     * @return array|bool
     * @throws \Ansuns\Pay\Exceptions\GatewayException
     */
    public function apply(array $options = [])
    {
        $options['bill_date'] = isset($options['bill_date']) ? $options['bill_date'] : date('Y-m-d', strtotime('-1 day'));
        $options['bill_type'] = isset($options['bill_type']) ? $options['bill_type'] : 'trade';
        $this->config = array_merge($this->config, $options);
        $result = $this->getResult($options, $this->getMethod());
        $bill_download_url = $result['bill_download_url'];
        $path = 'bill_alipay';
        $unzip_path = ToolsService::get_absolute_path($path);
        $result = curl()->download_file($bill_download_url);
        $result = $this->extractZipToFile($result, $unzip_path);
        $bill_data = [];
        foreach ($result as $file) {
            if (strpos($file, 'all') === false) {
                $result = $this->read_csv($file);
                $pid = $this->userConfig->get('pid');
                foreach (array_slice($result, 5) as $data) {
                    if (count($data) < 20) {
                        continue;
                    } elseif (count($data) > 25) {
                        throw new \Exception('支付宝账单字段可能有变化,下载失败,请核实');
                    } else {
                        $data[] = $pid;
                        foreach ($data as $k => $v) {
                            $data[$k] = str_replace(array("/r", "/n", "/r/n"), "", $v);
                            $data[$k] = preg_replace("/\xA1/i", "", $data[$k]); //去掉制表符
                            $data[$k] = trim($data[$k]);
                        }
                        $bill_data[] = array_combine(array_keys($this->field_map), $data);//处理数组 key值
                    }
                }
                break;
            }
        }
        return $bill_data;
    }
}