<?php

namespace Ansuns\Pay\Service;


class ToolsService
{
    protected static $instance = null;

    protected function __construct()
    {
        //disallow new instance
    }

    protected function __clone()
    {
        //disallow clone
    }

    public function __wakeup()
    {
        self::$instance = $this;
    }

    /**
     * 单例化对象
     */
    public static function get_instance()
    {
        //单例方法,用于访问实例的公共的静态方法
        if (is_null(self::$instance) || !isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 字符串命名风格转换
     * type 0  驼峰转下划线  1 下划线转驼峰
     * @param string $name 字符串
     * @param integer $type 转换类型
     * @param bool $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public static function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }

    public static function check_password($string)
    {
        $msg = '密码必须6位以上包含字母和数字';
        if (!preg_match("/[A-Za-z]/", $string) || !preg_match("/\d/", $string)) {
            return $msg;
        }
        if (strlen($string) < 6) {
            return $msg;
        }
        return true;
    }

    /**
     * 产生随机字符串
     * @param int $length 指定字符长度
     * @param string $str 字符串前缀
     * @return string
     */
    public static function createNoncestr($length = 32, $str = "")
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000 * ( double )microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    /**
     * 根据文件后缀获取文件MINE
     * @param array $ext 文件后缀
     * @param array $mine 文件后缀MINE信息
     * @return string
     * @throws LocalCacheException
     */
    public static function getExtMine($ext, $mine = [])
    {
        $mines = self::getMines();
        foreach (is_string($ext) ? explode(',', $ext) : $ext as $e) {
            $mine[] = isset($mines[strtolower($e)]) ? $mines[strtolower($e)] : 'application/octet-stream';
        }
        return join(',', array_unique($mine));
    }

    /**
     * 获取所有文件扩展的mine
     * @return array
     * @throws LocalCacheException
     */
    private static function getMines()
    {
        $mines = cache('all_ext_mine');
        if (empty($mines)) {
            $content = file_get_contents('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');
            preg_match_all('#^([^\s]{2,}?)\s+(.+?)$#ism', $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                foreach (explode(" ", $match[2]) as $ext) {
                    $mines[$ext] = $match[1];
                }
            }
            cache('all_ext_mine', $mines);
        }
        return $mines;
    }

    /**
     * 创建CURL文件对象
     * @param $filename
     * @param string $mimetype
     * @param string $postname
     * @return \CURLFile|string
     * @throws LocalCacheException
     */
    public static function createCurlFile($filename, $mimetype = null, $postname = null)
    {
        is_null($postname) && $postname = basename($filename);
        is_null($mimetype) && $mimetype = self::getExtMine(pathinfo($filename, 4));
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename, $mimetype, $postname);
        }
        return "@{$filename};filename={$postname};type={$mimetype}";
    }

    /**
     * 获取客户端IP地址
     *
     * @param integer $type
     *            返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return mixed
     */
    public static function get_client_ip($type = 0)
    {
        $type = $type ? 1 : 0;
        static $ip = null;
        if ($ip !== null) {
            return $ip[$type];
        }
        if (isset ($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset ($arr[$pos]);
            }
            $ip = trim($arr[0]);
        } elseif (isset ($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset ($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }

    /**
     * 判断时间是否过期
     * @param string $datetime
     * @return boolean
     **/
    public static function is_expired($datetime)
    {
        //时间大于2038年 不能直接用strtotime //http://www.jb51.net/article/117320.htm
        $datetime = new \DateTime($datetime);
        $expired_time = $datetime->format('U');
        return (time() > $expired_time);
    }

    /**
     * 数组转xml内容
     * @param array $data
     * @return null|string|string
     */
    public static function arr2json($data)
    {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function ($matches) {
            return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");
        }, ($jsonData = json_encode($data)) == '[]' ? '{}' : $jsonData);
    }

    /**
     * 数组转XML内容
     * @param array $data
     * @return string
     */
    public static function arr2xml($data)
    {
        return "<xml>" . self::_arr2xml($data) . "</xml>";
    }

    /**
     * 解析XML格式的字符串
     * @param string $str
     * @return boolean 解析正确就返回解析结果,否则返回false,说明字符串不是XML格式
     */
    public static function xml_parser($str)
    {
        try {
            $xml_parser = xml_parser_create();
            if (!xml_parse($xml_parser, $str, true)) {
                xml_parser_free($xml_parser);
                return false;
            } else {
                return (json_decode(json_encode(simplexml_load_string($str)), true));
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 解析XML内容到数组
     * @param string $xml
     * @return array
     */
    public static function xml2arr($xml)
    {
        try {
            if (!self::xml_parser($xml)) {
                return [];
            }
            $disableEntities = libxml_disable_entity_loader(true);
            $result =
                json_decode(self::arr2json(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            libxml_disable_entity_loader($disableEntities);
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 解析XML内容到数组
     * @param string $xml
     * @return string
     */
    public static function xml2json($xml)
    {
        return json_encode(self::xml2arr($xml), JSON_UNESCAPED_UNICODE);
    }

    /**
     * XML内容生成
     * @param array $data 数据
     * @param string $content
     * @return string
     */
    private static function _arr2xml($data, $content = '')
    {
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = 'item';
            $content .= "<{$key}>";
            if (is_array($val) || is_object($val)) {
                $content .= self::_arr2xml($val);
            } elseif (is_string($val)) {
                $content .= '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $val) . ']]>';
            } else {
                $content .= $val;
            }
            $content .= "</{$key}>";
        }
        return $content;
    }


    /**
     * 替换字符串中间位置字符为星号
     *
     * @param [type] $str
     *            [description]
     * @return [type] [description]
     * @author xieyongfa<xieyongfa@ecarde.cn>
     * @dateTime 2016-05-17T10:29:46+0800
     */
    public static function replaceToStar($str)
    {
        $len = strlen($str);
        return substr_replace($str, str_repeat('*', $len), floor(($len) / 2), $len);
    }

    /**多维数组交集
     * @param $array1
     * @param $array2
     * @return array
     */
    public static function many_array_intersect($array1, $array2)
    {
        $out_arr = [];
        foreach ($array1 as $key => $val) {
            if (in_array($val, $array2)) {
                $out_arr[] = $val;
            }
        }
        return $out_arr;
    }

    /**合并数据
     * @param $ary
     * @return mixed
     */
    public static function merge_arr($ary)
    {
        // 先删除key
        $ary = array_values($ary);
        for ($x = 0; $x < count($ary) - 1; $x++) {
            foreach ($ary[$x] as $k => $v) {
                if (key_exists($k, $ary[$x + 1])) {
                    $ary[$x + 1][$k] += $v;
                } else {
                    $ary[$x + 1][$k] = $v;
                }
            }
        }
        return end($ary);
    }

    /**
     * @param $multi_array
     * @param $sort_key
     * @param int $sort
     * @return array|bool
     */
    public static function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC)
    {
        if (is_array($multi_array)) {
            foreach ($multi_array as $row_array) {
                if (is_array($row_array)) {
                    $key_array[] = $row_array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_array, $sort, $multi_array);
        return $multi_array;
    }

    /**
     * @param null $timestamp
     * @return false|string
     */
    public static function format_timestamp($timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        return Date('Y-m-d H:i:s', $timestamp);
    }

    public static function mbStrSplit($string, $len = 1)
    {
        $start = 0;
        $str_len = strlen($string);
        $array = [];
        while ($str_len) {
            $array[] = mb_strcut($string, $start, $len, "utf8");
            $string = mb_strcut($string, $len, $str_len, "utf8");
            $str_len = strlen($string);
        }
        return $array;
    }

    public static function unicode_to_utf8($name)
    {
        $json = '{"str":"' . $name . '"}';
        $arr = json_decode($json, true);
        return empty($arr) ? $name : $arr['str'];
    }

    public static function array_iconv($data, $output = 'utf-8')
    {
        try {
            $encode_arr = ['UTF-8', 'ASCII', 'GBK', 'GB2312', 'BIG5', 'JIS', 'eucjp-win', 'sjis-win', 'EUC-JP'];
            $encoded = mb_detect_encoding($data, $encode_arr);
            if (!is_array($data)) {
                return mb_convert_encoding($data, $output, $encoded);
            } else {
                foreach ($data as $key => $val) {
                    $key = ToolsService::array_iconv($key, $output);
                    if (is_array($val)) {
                        $data[$key] = ToolsService::array_iconv($val, $output);
                    } else {
                        $data[$key] = mb_convert_encoding($data, $output, $encoded);
                    }
                }
                return $data;
            }
        } catch (\Exception $e) {
            return $data;
        }
    }


    /**
     * 字符串处理函数
     *
     * @param string $start
     *            开始寻找的字符串
     * @param string $end
     *            结束寻找的字符串
     * @param string $str
     *            所要查找的字符串
     * @return string
     */
    public static function search_str($start, $end, $str)
    {
        $strLen = strlen($str);
        if (empty ($str)) {
            return false;
        }
        if (empty ($start) && empty ($end)) {
            return false;
        }
        if (empty ($start)) {
            $endPosition = strpos($str, $end);
            $endLen = strlen($end);
            return trim(substr($str, 0, $endPosition));
        }
        if (empty ($end)) {
            $startPosition = strpos($str, $start);
            $startLen = strlen($start);
            return trim(substr($str, $startPosition + $startLen));
        }
        $strarr = explode($start, $str);
        if (!isset ($strarr[1])) {
            return false;
        } else {
            $str = $strarr[1];
            $strarr = explode($end, $str);
            return trim($strarr[0]);
        }
    }

    /**
     * 获取guid
     *
     * @return string
     */
    public static function create_guid()
    {
        mt_srand(( double )microtime() * 10000); // optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid =
            substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12,
                4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);
        return strtolower($uuid);
    }

    /**
     * object转array
     *
     * @param array $array
     *            对象或者数组
     * @return array
     */
    public static function object2array($array)
    {
        $array = json_decode(json_encode($array), true);
        return $array;
    }

    /**
     * object转array
     *
     * @param array $array
     *            对象或者数组
     * @return array
     */
    public static function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = self::object_array($value);
            }
        }
        return $array;
    }

    public static function cre_qrcode($text = null)
    {
        include __DIR__ . '/../extend/phpqrcode/phpqrcode.php';
        $pathname = "file/qrcode/"; // 二维码图片保存路径
        if (!is_dir($pathname)) {
            mkdir($pathname); // 若目录不存在则创建之
        }
        // 二维码图片保存路径(若不生成文件则设置为false)
        $filename = md5($text) . ".png";
        if (file_exists($pathname . $filename)) {
            return $pathname . $filename;
        }

        $level = "L"; // 二维码容错率，默认L
        $size = 8; // 二维码图片每个黑点的像素，默认4
        $padding = 2; // 二维码边框的间距，默认2
        $saveandprint = true; // 保存二维码图片并显示出来，$filename必须传递文件路径
        // 生成二维码图片
        $cre = \QRcode::png($text, $pathname . $filename, $level, $size, $padding, $saveandprint);
        if (file_exists($pathname . $filename)) {
            return $pathname . $filename;
        }
        return false;
    }

    public static function create_password($pw_length = 6)
    {
        $rand_pwd = '';
        for ($i = 0; $i < $pw_length; $i++) {
            $rand_pwd .= chr(mt_rand(33, 126));
        }
        return $rand_pwd;
    }

    public static function is_url($str)
    {
        return preg_match("/^http(s)?:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\’:+!]*([^<>\"])*$/",
            $str) ? true : false;
    }

    /**
     * @param $IDCard
     * @param int $format通过身份证获取生日
     * @return mixed
     */
    public static function getIDCardInfo($IDCard, $format = 1)
    {
        $result['error'] = 0;//0：未知错误，1：身份证格式错误，2：无错误
        $result['flag'] = '';//0标示成年，1标示未成年
        $result['tdate'] = '';//生日，格式如：2012-11-15
        if (!preg_match("/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/", $IDCard)) {
            $result['error'] = 1;
            return $result;
        } else {
            if (strlen($IDCard) == 18) {
                $tyear = intval(substr($IDCard, 6, 4));
                $tmonth = intval(substr($IDCard, 10, 2));
                $tday = intval(substr($IDCard, 12, 2));
            } elseif (strlen($IDCard) == 15) {
                $tyear = intval("19" . substr($IDCard, 6, 2));
                $tmonth = intval(substr($IDCard, 8, 2));
                $tday = intval(substr($IDCard, 10, 2));
            }

            if ($tyear > date("Y") || $tyear < (date("Y") - 100)) {
                $flag = 0;
            } elseif ($tmonth < 0 || $tmonth > 12) {
                $flag = 0;
            } elseif ($tday < 0 || $tday > 31) {
                $flag = 0;
            } else {
                if ($format) {
                    $tdate = $tyear . "-" . $tmonth . "-" . $tday;
                } else {
                    $tdate = $tmonth . "-" . $tday;
                }

                if ((time() - mktime(0, 0, 0, $tmonth, $tday, $tyear)) > 18 * 365 * 24 * 60 * 60) {
                    $flag = 0;
                } else {
                    $flag = 1;
                }
            }
        }
        $result['error'] = 2;//0：未知错误，1：身份证格式错误，2：无错误
        $result['isAdult'] = $flag;//0标示成年，1标示未成年
        $result['birthday'] = $tdate;//生日日期
        return $result;
    }

    /**
     * 判断字符串是否为 Json 格式
     * @param string $json_str 字符串
     *
     * @return array|bool|object 成功返回转换后的对象或数组，失败返回 false
     */
    public static function is_json($json_str)
    {
        try {
            if (empty($json_str) || !is_string($json_str)) {
                return false;
            }
            return is_array(json_decode($json_str, true));
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function format_microtime($tag = 'Y-m-d-H-i-s.x')
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = self::ncPriceCalculate($usec, '+', $sec, 6);
        list($usec, $sec) = explode(".", $time);
        $date = date($tag, $usec);
        return str_replace('x', $sec, $date);
    }

    public static function get_bill_number()
    {
        return self::format_microtime('YmdHisx');
    }

    public static function md5_guid($str)
    {
        return substr($str, 0, 8) . '-' . substr($str, 8, 4) . '-' . substr($str, 12, 4) . '-' . substr($str, 16,
                4) . '-' . substr($str, 20, 12);
    }

    public static function is_guid($str)
    {
        return preg_match("/^\w{8}-(\w{4}-){3}\w{12}$/", $str);
    }

    public static function is_mobile($phone_number)
    {
        ///^1[23456789]\d{9}$/
        return preg_match("/^1\d{10}$/", $phone_number);
    }

    public static function is_oil_card_id($oil_card_id)
    {
        return preg_match("/^1\d{18}$/", $oil_card_id) || preg_match("/^9\d{15}$/", $oil_card_id);
    }

    public static function is_email($email_address)
    {
        if (strstr($email_address, '-')) {
            return false;
        }
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        return preg_match($pattern, $email_address);
    }

    public static function find_number($str)
    {
        preg_match_all('/\d+/', $str, $arr);
        $arr = join('', $arr[0]);
        return $arr;
    }

    public static function unique_rand($length, $num = 1, $old_arr = [])
    {
        $count = 0;
        $min = (int)('1' . str_repeat(0, $length - 1));
        $max = (int)str_repeat(9, $length);
        $arr = [];
        while ($count < $num) {
            for ($x = 0; $x <= $num; $x++) {
                $rand = mt_rand($min, $max);
                $arr[] = $rand;
            }
            $arr = array_flip(array_flip($arr));
            if (!empty($old_arr)) {
                $arr = array_diff($arr, $old_arr);
            }
            $count = count($arr);
        }
        $return = array_slice($arr, 0, $num);
        return (count($return) == 1) ? reset($return) : $return;
    }

    /**
     * endwith()
     * 判断是否以特定的字符串结束，查找文件后缀用
     * string strrchr ( string haystack, string needle )
     * @param string $haystack
     * @param string $needle
     * @return boolean $result
     *
     */
    public static function end_with($haystack, $needle)
    {
        $haystack = (string)$haystack; //转换成字符串
        $needles = is_array($needle) ? $needle : [$needle];
        foreach ($needles as $key => $val) {
            $val = (string)$val; //转换成字符串
            $length = strlen($val);
            if ($length == 0) {
                return true;
            }
            if (substr($haystack, -$length) === $val) {
                return true;
            }
        }
        return false;
    }

    public static function start_with($haystack, $needle)
    {
        $haystack = (string)$haystack; //转换成字符串
        $needles = (is_array($needle) ? $needle : [$needle]);
        foreach ($needles as $key => $val) {
            $val = (string)$val; //转换成字符串
            $length = strlen($val);
            if ($length == 0) {
                return true;
            }
            if (substr($haystack, 0, strlen($val)) === $val) {
                return true;
            }
        }
        return false;
    }

    /**
     * PHP精确计算  主要用于货币的计算用
     * @param $n1 第一个数
     * @param $symbol 计算符号 + - * / %
     * @param $n2 第二个数
     * @param string $scale 精度 默认为小数点后两位
     * @return  string
     * @throws \Exception
     */
    public static function ncPriceCalculate($n1, $symbol, $n2, $scale = '2')
    {
        $res = null;
        switch ($symbol) {
            case "+"://加法
                $res = bcadd($n1, $n2, $scale);
                break;
            case "-"://减法
                $res = bcsub($n1, $n2, $scale);
                break;
            case "*"://乘法
                $res = bcmul($n1, $n2, $scale);
                break;
            case "/"://除法
                $res = bcdiv($n1, $n2, $scale);
                break;
            case "%"://求余、取模
                $res = bcmod($n1, $n2, $scale);
                break;
            default:
                throw new Exception('非法的操作符');
        }
        return $res;
    }

    /**
     * 价格由元转分
     * @param $price 金额
     * @return int 数字
     */
    public static function ncPriceYuan2fen($price)
    {
        $price = (int)self::ncPriceCalculate(100, "*", self::ncPriceFormat($price));
        return $price;
    }

    /**
     * 价格由分转元
     * @param $price 金额
     * @return int 数字
     */
    public static function ncPriceFen2yuan($price)
    {
        $price = self::ncPriceCalculate($price, "/", 100);
        return $price;
    }

    /**
     * 价格格式化
     *
     * @param int $price
     * @return string    $price_format
     */
    public static function ncPriceFormat($price)
    {
        $price_format = number_format($price, 2, '.', '');
        return $price_format;
    }

    public static function toTree(
        $arr,
        $keyNodeId = 'guid',
        $keyParentId = 'parent_guid',
        $keyChildrens = 'sub',
        &$refs = null
    )
    {
        $refs = [];
        foreach ($arr as $offset => $row) {
            $arr[$offset][$keyChildrens] = [];
            $refs[$row[$keyNodeId]] = &$arr[$offset];
        }

        $tree = [];
        foreach ($arr as $offset => $row) {
            $parentId = $row[$keyParentId];
            if ($parentId) {
                if (!isset($refs[$parentId])) {
                    $tree[] = &$arr[$offset];
                    continue;
                }
                $parent = &$refs[$parentId];
                $parent[$keyChildrens][] = &$arr[$offset];
            } else {
                $tree[] = &$arr[$offset];
            }
        }
        return $tree;
    }

    public static function treeSort(array $arr, $parent_guid = null, $id = 'guid', $parentId = 'parent_guid')
    {
        $parent_guid = $parent_guid ?: self::get_empty_guid();
        if (empty($arr)) {
            return $arr;
        }
//    if (count($arr) == 1) {
//        return $arr;
//    }
        $newArr = [];
        foreach ($arr as $key => $item) {
            if ($parent_guid == $item[$parentId]) {
                in_array($item, $newArr) || $newArr[] = $item;
                unset($arr[$key]);
                $sub = ToolsService::treeSort($arr, $item[$id]);
                $newArr = array_merge($newArr, $sub);
            }
        }
        return $newArr;
    }

    public static function formatTree(
        array $array,
        $parent_guid = null,
        $id = 'guid',
        $parentId = 'parent_guid',
        $keyChildrens = 'sub'
    )
    {
        $parent_guid = $parent_guid ?: self::get_empty_guid();
        $arr = [];
        $tem = [];
        foreach ($array as $v) {
            $v['open'] = true;
            if ($v[$parentId] == $parent_guid) {
                $tem = self::formatTree($array, $v[$id], $id, $parentId, $keyChildrens);
                //判断是否存在子数组
                $tem && $v[$keyChildrens] = $tem;
                $arr[] = $v;
            }
        }
        return $arr;
    }

    public static function treeToHtml($tree)
    {
        $html = '';
        foreach ($tree as $t) {
            if (empty($t['sub'])) {
                $html .= "<li>{$t['title']}";
                // $html .= " <a class='btn btn-xs btn-primary'>编辑</a>";
                //  $html .= " <a class='btn btn-xs btn-danger delete'>删除</a>";
                $html .= "</li>";
            } else {
                $html .= "<li>" . $t['title'];
                //  $html .= " <a class='btn btn-xs btn-primary'>编辑</a>";
                //  $html .= " <a class='btn btn-xs btn-danger delete'>删除</a>";
                $html .= self::treeToHtml($t['sub']);
                $html .= "</li>";
            }
        }
        return $html ? '<ul>' . $html . '</ul>' : $html;
    }

    public static function remove_html_tag($str)
    {
        //清除HTML代码、空格、回车换行符
        //trim 去掉字串两端的空格
        //strip_tags 删除HTML元素
        $str = trim($str);
        $str = str_replace('/<script[^>]*?>(.*?)<\/script>/si', '', $str);
        $str = str_replace('/<style[^>]*?>(.*?)<\/style>/si', '', $str);
        $str = strip_tags($str, "");
        $str = str_replace("\t", "", $str);
        $str = str_replace("\r\n", "", $str);
        $str = str_replace("\r", "", $str);
        $str = str_replace("\n", "", $str);
        $str = str_replace(" ", "", $str);
        $str = str_replace("&nbsp;", "", $str);
        return trim($str);
    }

    public static function toOneLevelArray($arr)
    {
        foreach ($arr as $key => $val) {
            if (!empty($val['children'])) {
                //发现有子元素 全部压入新数组,然后unset掉
                foreach ($val['children'] as $k => $v) {
                    $arr[] = $v;
                }
                unset($arr[$key]['children']);
                return self::toOneLevelArray($arr);
            }
        }
        return $arr;
    }

    public static function createParentNode($arr, $pid = 0)
    {
        foreach ($arr as $key => $val) {
            $arr[$key]['pid'] = $pid;
            if (!empty($val['children'])) {
                $arr[$key]['children'] = self::createParentNode($val['children'], $val['id']);
            }
        }
        return $arr;
    }

    /**
     * Emoji原形转换为String
     * @param string $content 要处理的表情内容
     * @param boolean $replace_to_empty 是否替换成空
     * @return string
     */
    public static function emoji_encode($content, $replace_to_empty = false)
    {
        return json_decode(preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($str) use ($replace_to_empty) {
            return $replace_to_empty ? '' : addslashes($str[0]);
        }, json_encode($content)));
    }

    /**
     * Emoji字符串转换为原形
     * @param string $content
     * @return string
     */
    public static function emoji_decode($content)
    {
        return json_decode(preg_replace_callback('/\\\\\\\\/i', function () {
            return '\\';
        }, json_encode($content)));
    }

    /**
     * 安全URL编码
     * @param array|string $data
     * @return string
     */
    public static function encode_url($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(serialize($data)));
    }

    /**
     * 安全URL解码
     * @param string $string
     * @return string
     */
    public static function decode_url($string)
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = strlen($data) % 4;
        !!$mod4 && $data .= substr('====', $mod4);
        return unserialize(base64_decode($data));
    }

    /**
     * 一维数据数组生成数据树
     * @param array $list 数据列表
     * @param string $id 父ID Key
     * @param string $pid ID Key
     * @param string $son 定义子数据Key
     * @return array
     */
    public static function arr2tree($list, $id = 'id', $pid = 'pid', $son = 'sub')
    {
        $tree = $map = array();
        foreach ($list as $item) {
            $map[$item[$id]] = $item;
        }
        foreach ($list as $item) {
            if (isset($item[$pid]) && isset($map[$item[$pid]])) {
                $map[$item[$pid]][$son][] = &$map[$item[$id]];
            } else {
                $tree[] = &$map[$item[$id]];
            }
        }
        unset($map);
        return $tree;
    }

    /**
     * 一维数据数组生成数据树
     * @param array $list 数据列表
     * @param string $id ID Key
     * @param string $pid 父ID Key
     * @param string $path
     * @return array
     */
    public static function arr2table($list, $id = 'id', $pid = 'pid', $path = 'path', $ppath = '')
    {
        $_array_tree = self::arr2tree($list, $id, $pid);
        $tree = [];
        foreach ($_array_tree as $_tree) {
            $_tree[$path] = $ppath . '-' . $_tree[$id];
            $_tree['spl'] = str_repeat("&nbsp;&nbsp;&nbsp;├&nbsp;&nbsp;", substr_count($ppath, '-'));
            if (!isset($_tree['sub'])) {
                $_tree['sub'] = array();
            }
            $sub = $_tree['sub'];
            unset($_tree['sub']);
            $tree[] = $_tree;
            if (!empty($sub)) {
                $sub_array = self::arr2table($sub, $id, $pid, $path, $_tree[$path]);
                $tree = array_merge($tree, (Array)$sub_array);
            }
        }
        return $tree;
    }

    /**
     * 获取数据树子ID
     * @param array $list 数据列表
     * @param int $id 起始ID
     * @param string $key 子Key
     * @param string $pkey 父Key
     * @return array
     */
    public static function getArrSubIds($list, $id = 0, $key = 'id', $pkey = 'pid')
    {
        $ids = array(intval($id));
        foreach ($list as $vo) {
            if (intval($vo[$pkey]) > 0 && intval($vo[$pkey]) == intval($id)) {
                $ids = array_merge($ids, self::getArrSubIds($list, intval($vo[$key]), $key, $pkey));
            }
        }
        return $ids;
    }

    public static function array_to_xml($arr, $root = '')
    {
        $xml = '';
        if ($root) {
            $xml .= '<' . $root . '>';
        }
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . self::array_to_xml($val, $root) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        if ($root) {
            $xml .= '</' . $root . '>';
        }
        return $xml;
    }

    public static function get_memory_get_usage()
    {
        return round(memory_get_usage() / 1024 / 1024, 4);
    }

    public static function trim_array($array)
    {
        return (!is_array($array)) ? trim($array) : array_map([self::class, __FUNCTION__], $array);
    }

    public static function md5_16($str)
    {
        return substr(md5($str), 8, 16);
    }

    /**
     * 将字符串参数变为数组
     * @param $query
     * @return array array (size=10)
     * 'm' => string 'content' (length=7)
     * 'c' => string 'index' (length=5)
     * 'a' => string 'lists' (length=5)
     * 'catid' => string '6' (length=1)
     * 'area' => string '0' (length=1)
     * 'author' => string '0' (length=1)
     * 'h' => string '0' (length=1)
     * 'region' => string '0' (length=1)
     * 's' => string '1' (length=1)
     * 'page' => string '1' (length=1)
     */
    public static function convert_url_query($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }

    public static function output_url()
    {
        $url = urldecode(urldecode(input('url')));
        curl()->set_cookies(input('cookies'))->get($url)->output();
    }

    /**
     * 将参数变为字符串
     * @param $array_query
     * @return string string 'm=content&c=index&a=lists&catid=6&area=0&author=0&h=0®ion=0&s=1&page=1' (length=73)
     */
    public static function get_url_query($array_query)
    {
        $tmp = array();
        foreach ($array_query as $k => $param) {
            $tmp[] = $k . '=' . $param;
        }
        $params = implode('&', $tmp);
        return $params;
    }

    public static function url_to_params($url)
    {
        $url = urldecode($url);//先解码
        $arr = parse_url($url);//解析callback,
        return isset($arr['query']) ? self::convert_url_query($arr['query']) : [];//将参数字符串转换成数组
    }

    //获取文件目录列表,该方法返回数组
    public static function get_current_dir($dir)
    {
        $files = array();
        if (is_dir($dir)) {
            if ($handle = opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($dir . "/" . $file)) {
                            $files[] = $file;
                        }
                    }
                }
                closedir($handle);
                return $files;
            }
        }
    }

    public static function scan_dir($dir)
    {
        $files = array();
        if (is_dir($dir)) {
            if ($handle = opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($dir . "/" . $file)) {
                            $files[$file] = self::scan_dir($dir . "/" . $file);
                        } else {
                            $files[] = self::array_iconv($dir . "/" . $file);
                        }
                    }
                }
                closedir($handle);
                return $files;
            }
        }
    }

    public static function get_array_value_mb_strlen($array)
    {
        $length = 0;
        foreach ($array as $key => $val) {
            $length += mb_strlen($val);
        }
        return $length;
    }

    /**
     * 将unicode转换成字符
     * @param int $unicode
     * @return string UTF-8字符
     **/
    public static function unicode2Char($unicode)
    {
        if ($unicode < 128) {
            return chr($unicode);
        }
        if ($unicode < 2048) {
            return chr(($unicode >> 6) + 192) .
                chr(($unicode & 63) + 128);
        }
        if ($unicode < 65536) {
            return chr(($unicode >> 12) + 224) .
                chr((($unicode >> 6) & 63) + 128) .
                chr(($unicode & 63) + 128);
        }
        if ($unicode < 2097152) {
            return chr(($unicode >> 18) + 240) .
                chr((($unicode >> 12) & 63) + 128) .
                chr((($unicode >> 6) & 63) + 128) .
                chr(($unicode & 63) + 128);
        }
        return false;
    }

    /**
     * 将字符转换成unicode
     * @param string $char 必须是UTF-8字符
     * @return int
     **/
    public static function char2Unicode($char)
    {
        switch (strlen($char)) {
            case 1 :
                return ord($char);
            case 2 :
                return (ord($char[1]) & 63) |
                    ((ord($char[0]) & 31) << 6);
            case 3 :
                return (ord($char[2]) & 63) |
                    ((ord($char[1]) & 63) << 6) |
                    ((ord($char[0]) & 15) << 12);
            case 4 :
                return (ord($char[3]) & 63) |
                    ((ord($char[2]) & 63) << 6) |
                    ((ord($char[1]) & 63) << 12) |
                    ((ord($char[0]) & 7) << 18);
            default :
                trigger_error('Character is not UTF-8!', E_USER_WARNING);
                return false;
        }
    }

    /**
     * 全角转半角
     * @param string $str
     * @return string
     **/
    public static function sbc2Dbc($str)
    {
        return preg_replace(
        // 全角字符
            '/[\x{3000}\x{ff01}-\x{ff5f}]/ue',
            // 编码转换
            // 0x3000是空格，特殊处理，其他全角字符编码-0xfee0即可以转为半角
            '($unicode=char2Unicode(\'\0\')) == 0x3000 ? " " : (($code=$unicode-0xfee0) > 256 ? unicode2Char($code) : chr($code))',
            $str
        );
    }

    /**
     * 半角转全角
     * @param string $str
     * @return string
     **/
    public static function dbc2Sbc($str)
    {
        return preg_replace(
        // 半角字符
            '/[\x{0020}\x{0020}-\x{7e}]/ue',
            // 编码转换
            // 0x0020是空格，特殊处理，其他半角字符编码+0xfee0即可以转为全角
            '($unicode=char2Unicode(\'\0\')) == 0x0020 ? unicode2Char（0x3000） : (($code=$unicode+0xfee0) > 256 ? unicode2Char($code) : chr($code))',
            $str
        );
    }

    public static function get_empty_guid()
    {
        return '00000000-0000-0000-0000-000000000000';
    }

    /**
     * 判断时间是否空guid
     * @param string $str
     * @return boolean
     **/
    public static function is_empty_guid($str)
    {
        return $str == self::get_empty_guid();
    }

    /**
     * 付完成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。格式化返回datetime格式
     * @param string $str
     * @return string
     **/
    public static function format_time($str)
    {
        return substr($str, 0, 4) . '-' . substr($str, 4, 2) . '-' . substr($str, 6, 2) . ' ' . substr($str, 8,
                2) . ':' . substr($str, 10, 2) . ':' . substr($str, 12, 2);  // bcd
    }

    public static function _arrayToFsTableData($array)
    {
        $data = [];
        foreach ($array as $key => $val) {
            $data[] = ['key_name' => $key, 'value' => $val,];
        }
        return $data;
    }

    public static function _getFsFormData($data, $json_key)
    {
        $form_data = urldecode($data['fsFormData']);
        $form_data = json_decode($form_data, true);
        $table_data = urldecode($data['fsTableData']);
        $table_data = json_decode($table_data, true);
        $form_data[$json_key] = $table_data;
        return $form_data;
    }

    public static function _parseFsFormDataAttr($data, $json_key)
    {
        $form_data = self::_getFsFormData($data, $json_key);
        $json_data = [];
        foreach ($form_data[$json_key] as $key => $val) {
            if (!empty($val['name'])) {
                $json_data[] = [
                    'guid' => isset($val['guid']) ? $val['guid'] : '',
                    'name' => $val['name']
                ];
            }
        }
        $form_data[$json_key] = $json_data;
        return $form_data;
    }

    public static function _parseFsFormData($data, $json_key)
    {
        $form_data = self::_getFsFormData($data, $json_key);
        $json_data = [];
        foreach ($form_data[$json_key] as $key => $val) {
            if (!empty($val['key_name'])) {
                if (!isset($val['value'])) {
                    $val['value'] = '';
                }
                if ($val['value'] == 'true') {
                    $val['value'] = true;
                }
                if ($val['value'] == 'false') {
                    $val['value'] = false;
                }
                $json_data[$val['key_name']] = $val['value'];
            }
        }
        $form_data[$json_key] = $json_data;
        return $form_data;
    }

    /**
     * @param string $path
     * @return string
     */
    public static function get_absolute_path($path)
    {
        $root_path = App::getRootPath();
        $path = $root_path . "public/" . ltrim($path, '/');
        return str_replace('\\', '/', $path);
    }

    /**
     * @param string $url
     * @return string
     */
    public static function web_to_path($url)
    {
        $request = request();
        $domain = $request->domain();
        return str_replace($domain, '', $url);
    }

    /**
     * @param String $endTime
     * @return String time
     * @todo Count Down (倒计时)
     * @example
     * $endTime = '2014-07-13 8:15:00';
     * echo countDown($endTime);
     */
    public static function countDown($endTime)
    {
        $endTime = strtotime($endTime);
        $beiginTime = strtotime(date('Y-m-d H:i:s'));
        $timeDifference = $endTime - $beiginTime;
        switch ($timeDifference) {
            case $timeDifference < 0 :
                $timeDifference = '已经结束！';
                break;
            case $timeDifference < 60 :
                $timeDifference = $timeDifference . '秒';
                break;
            case $timeDifference < 3600 :
                $minutes = floor($timeDifference / 60);
                $seconds = floor($timeDifference - ($minutes * 60));
                $timeDifference = $minutes . '分' . $seconds . '秒';
                break;
            case $timeDifference < 86400 :
                $hours = floor($timeDifference / 3600);
                $minutes = floor(($timeDifference - ($hours * 3600)) / 60);
                $seconds = floor($timeDifference - ($hours * 3600) - ($minutes * 60));
                $timeDifference = $hours . '小时' . $minutes . '分' . $seconds . '秒';
                break;
            default:
                $days = floor(($timeDifference / 86400));
                $hours = floor(($timeDifference - ($days * 86400)) / 3600);
                $minutes = floor(($timeDifference - ($days * 86400) - ($hours * 3600)) / 60);
                $seconds = floor($timeDifference - ($days * 86400) - ($hours * 3600) - ($minutes * 60));
                $timeDifference = $days . '天' . $hours . '小时' . $minutes . '分' . $seconds . '秒';
                break;
        }
        return $timeDifference;
    }

    /**
     * 把jsonp转为php数组
     * @param string $jsonp jsonp字符串
     * @param boolean $assoc 当该参数为true时，将返回array而非object
     * @return array
     */
    public static function jsonp_decode($jsonp, $assoc = true)
    {
        $jsonp = trim($jsonp);
        if (isset($jsonp[0]) && $jsonp[0] !== '[' && $jsonp[0] !== '{') {
            $begin = strpos($jsonp, '(');
            if (false !== $begin) {
                $end = strrpos($jsonp, ')');
                if (false !== $end) {
                    $jsonp = substr($jsonp, $begin + 1, $end - $begin - 1);
                }
            }
        }
        return json_decode($jsonp, $assoc);
    }

    /**
     * 生成签名内容
     * @param $data
     * @return string
     */
    public static function getSignContent($data)
    {
        $buff = '';
        foreach ($data as $k => $v) {
            $buff .= ($k != 'sign' && $v != '' && !is_array($v)) ? $k . '=' . $v . '&' : '';
        }
        return trim($buff, '&');
    }

    /**
     * 生成内容签名
     * @param array $data
     * @param string $key
     * @return string
     * @throws \Exception
     */
    public static function getSign($data, $key)
    {
        if (is_null($key)) {
            throw new \Exception('getSign Failed Missing Parameter -- [key]');
        }
        ksort($data);
        $string = md5(self::getSignContent($data) . '&key=' . $key);
        return strtoupper($string);
    }

    /**
     * 生成hash密码
     * @param string $password
     * @param string|mixed $cost
     * @return string
     * @throws \Exception
     */
    public static function generatePasswordHash($password, $cost = null)
    {
        if ($cost === null) {
            $cost = 13;
        }
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => $cost]);
    }

    /**
     * 验证hash密码
     * @param string $password
     * @param string $hash
     * @return bool
     * @throws \Exception
     */
    public static function validatePasswordHash($password, $hash)
    {
        if (!is_string($password) || $password === '') {
            return false;
        }
        if (!preg_match('/^\$2[axy]\$(\d\d)\$[\.\/0-9A-Za-z]{22}/', $hash, $matches)
            || $matches[1] < 4
            || $matches[1] > 30
        ) {
            return false;
        }
        return password_verify($password, $hash);
    }

    /**
     * @param $lat1
     * @param $lng1
     * @param $lat2
     * @param $lng2
     * @param float $radius 星球半径
     * @return int
     */
    public static function getDistance($lat1, $lng1, $lat2, $lng2, $radius = 6378.137)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * $radius;
        return $s;
    }

    /**
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @param float $radius 星球半径
     * @return float 距离千米
     */
    public static function distance($lat1, $lon1, $lat2, $lon2, $radius = 6378.137)
    {
        $rad = floatval(M_PI / 180.0);
        $lat1 = floatval($lat1) * $rad;
        $lon1 = floatval($lon1) * $rad;
        $lat2 = floatval($lat2) * $rad;
        $lon2 = floatval($lon2) * $rad;
        $theta = $lon2 - $lon1;
        $dist = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($theta));
        if ($dist < 0) {
            $dist += M_PI;
        }
        return $dist = $dist * $radius;
    }

    /**
     * @desc 仅仅获取这个类的方法，不要父类的
     * @param class int Y N 类名
     * @return array 本类的所有方法构成的一个数组
     */
    public static function get_this_class_methods($class)
    {
        $array1 = get_class_methods($class);
        if ($parent_class = get_parent_class($class)) {
            $array2 = get_class_methods($parent_class);
            $array3 = array_diff($array1, $array2);
        } else {
            $array3 = $array1;
        }
        return (array)$array3;
    }

    /**
     * RSA数据加密解密
     * @param string $data
     * @param string $public_key
     * @return string
     * @throws \Exception
     */
    public static function rsa_encode($data, $public_key)
    {
        if (empty($data)) {
            throw new \Exception('data参数不能为空');
        }
        //公钥加密
        $public_key = openssl_pkey_get_public($public_key);
        if (!$public_key) {
            throw new \Exception('公钥不可用');
        }
        $return_en = openssl_public_encrypt($data, $crypted, $public_key);
        if (!$return_en) {
            throw new \Exception('加密失败,请检查RSA秘钥');
        }
        return base64_encode($crypted);
    }

    /**
     * RSA数据加密解密
     * @param string $data
     * @param string $private_key
     * @return string
     * @throws \Exception
     */
    public static function rsa_decode($data, $private_key)
    {
        if (empty($data)) {
            throw new \Exception('data参数不能为空');
        }
        //私钥解密
        $private_key = openssl_pkey_get_private($private_key);
        if (!$private_key) {
            throw new \Exception('私钥不可用');
        }
        $return_de = openssl_private_decrypt(base64_decode($data), $decrypted, $private_key);
        if (!$return_de) {
            throw new \Exception('解密失败,请检查RSA秘钥');
        }
        return $decrypted;
    }

    /**
     * @param $cookies
     * @return array
     */
    public static function cookies_to_array($cookies)
    {
        $cookies_array = [];
        $array = explode(';', $cookies);
        foreach ($array as $key => $val) {
            $str = explode('=', $val);
            $cookies_array[trim($str[0])] = trim($str[1]);
        }
        return $cookies_array;
    }

    /**
     * 获取字符串的时间范围：2019-10-16 - 2019-10-16
     * @param string $date_str
     * @return array
     */
    public static function get_area_date(string $date_str = '')
    {
        $date_time = explode(' - ', $date_str);
        if (!is_array($date_time)) {
            return [date("Y-m-d H:i:s", time()), date("Y-m-d 23:59:59", time())];
        }
        return [date("Y-m-d H:i:s", strtotime($date_time[0])), date("Y-m-d 23:59:59", strtotime($date_time[1]))];
    }

    /**
     * 压缩文件夹成压缩文件
     * @param string $source
     * @param $destination
     * @return bool
     */
    public static function zip(string $source, $destination)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }
        $path = pathinfo($destination, PATHINFO_DIRNAME);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZIPARCHIVE::CREATE)) {
            return false;
        }
        $source = str_replace('\\', DS, realpath($source));
        if (is_dir($source) === true) {
            $files =
                new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source),
                    \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);
                // Ignore "." and ".." folders
                if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..'))) {
                    continue;
                }
                $file = realpath($file);
                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . DS, '', $file . '/'));
                } else {
                    if (is_file($file) === true) {
                        $zip->addFromString(str_replace($source . DS, '', $file), file_get_contents($file));
                    }
                }
            }
        } else {
            if (is_file($source) === true) {
                $zip->addFromString(basename($source), file_get_contents($source));
            }
        }
        return $zip->close();
    }

    /**
     * @return string
     */
    public static function createAccessMerchId()
    {
        $charid = md5(uniqid(mt_rand(), true));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        return $uuid;
    }

    /**
     *  数组转换成XML数据
     * @param array $array
     * @return string
     * @throws \Exception
     */
    public static function arrayToXml(array $array)
    {
        if (!is_array($array)) {
            throw new \Exception('`$arr`不是有效的array。');
        }
        $xml = "<xml>\r\n";
        $xml .= self::arrayToXmlSub($array);
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * @param $array
     * @return string
     * @throws \Exception
     */
    private static function arrayToXmlSub($array)
    {
        if (!is_array($array)) {
            throw new \Exception('`$array`不是有效的array。');
        }
        $xml = "";
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                if (is_numeric($key)) {
                    $xml .= self::arrayToXmlSub($val);
                } else {
                    $xml .= "<" . $key . ">" . self::arrayToXmlSub($val) . "</" . $key . ">\r\n";
                }
            } elseif (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">\r\n";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">\r\n";
            }
        }
        return $xml;
    }

    /**
     * XML数据转换成array数组
     * @param string $xml
     * @return array
     */
    public static function xmlToArray($xml)
    {
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $res = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return (array)$res;
    }
}
