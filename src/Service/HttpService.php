<?php

namespace Ansuns\Pay\Service;

use Ansuns\Pay\Exceptions\Exception;

/**
 * HTTP请求封装
 *
 * Class HttpService
 * @library HttpService
 * @author XieYongFa<215005377@qq.com>
 * @date 2018/11/03 18:00:00
 *
 * @method HttpService delete($url, $data = array()) public delete方法
 * @method HttpService put($url, $data = array()) public put方法
 * @method HttpService options($url, $data = array()) public options方法
 * @method HttpService head($url, $data = array()) public head方法
 *
 * */
class HttpService
{

    //单例对象
    private static $instance = null;
    private $ch = null;
    private $opts = array();

    //请求信息
    public $response = '';
    public $body = '';
    /**
     * @var string|array
     */
    public $url;
    public $header = '';
    public $request_info = [];
    public $errcode = 0;
    public $errmsg = '';
    public $method = '';
    public $data = '';
    public $request_used_time = 0;
    public $request_cookies = '';
    public $return_cookies = '';
    public $ignore_log = false;
    private $max_request_num = 20; //最大并发数量

    /**
     * 回调函数,作用到每一个返回的结果上
     */
    private $callback;


    private function __construct()
    {

    }

    private function __clone()
    {
        //disallow clone
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
     * 调用不存在的方法被调用
     */
    public function __call($method, $argc)
    {
        $method_array = ['GET', 'POST', 'PUT', 'HEAD', 'DELETE', 'OPTIONS'];
        if (!in_array(strtoupper($method), $method_array)) {
            throw new \Exception("错误:不支持{$method}方法,支持的方法有" . join(',', $method_array));
        }
        $this->set_method($method);
        $this->set_url($argc[0]);
        if (!empty($argc[1])) {
            $this->set_data($argc[1]);
        }
        return $this->curl_exec();
    }

    /**
     * @access public
     * @return $this
     */
    public function get($url, $data = array())
    {
        $this->data = $data;
        if ($data) {
            if (is_array($url)) {
                foreach ($url as $key => $val) {
                    $url[$key] .= (stripos($val, '?') !== false ? '&' : '?') . http_build_query($data);
                }
            } else {
                $url .= (stripos($url, '?') !== false ? '&' : '?') . http_build_query($data);
            }
        }
        $this->set_url($url);
        $this->set_method(__FUNCTION__);
        return $this->curl_exec();
    }


    /**
     * @param $url
     * @param array $data
     * @param $opitons
     * @return HttpService|bool|mixed
     * @throws \Exception
     */
    public function post($url, $data = array(), $opitons = [])
    {
        if (isset($opitons['headers'])) {
            $this->set_header($opitons['headers']);
        }

        $this->opts[CURLOPT_POST] = true;
        $this->set_url($url);
        $this->set_method(__FUNCTION__);
        $this->set_data($data);
        //判断是否有文件上传,进行处理
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value) && stripos($value, '@') === 0) {
                    $path = ltrim($value, '@');
                    if (file_exists($path) && is_file($path)) {
                        $data[$key] = curl_file_create($path);
                    }
                }
            }
            $this->set_data($data);
        }
        return $this->curl_exec();
    }

    public function set_callback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    public function set_data($data = array())
    {
        $this->opts[CURLOPT_POSTFIELDS] = $data;
        $this->data = $data;
        return $this;
    }

    public function ignore_log($ignore = true)
    {
        $this->ignore_log = $ignore;
        return $this;
    }

    public function set_method($method = 'GET')
    {
        $method = strtoupper($method);
        $this->opts [CURLOPT_CUSTOMREQUEST] = $method;
        $this->method = $method;
        return $this;
    }

    public function set_userpwd($userpwd = null)
    {
        $userpwd && $this->opts[CURLOPT_USERPWD] = $userpwd;
        return $this;
    }

    public function set_ssl_cer($ssl_cer = null)
    {
        if ($ssl_cer) {
            if (file_exists($ssl_cer) && is_file($ssl_cer)) {
                $this->opts[CURLOPT_SSLCERTTYPE] = 'PEM';
                $this->opts[CURLOPT_SSLCERT] = $ssl_cer;
            }
        }
        return $this;
    }

    public function is_https($url = null)
    {
        $url = $url ?: $this->url;
        return strtolower(substr($url, 0, 5)) == "https";
    }

    public function set_url($url)
    {
        $this->url = $url;
        if (is_string($url)) {
            $this->opts[CURLOPT_URL] = $url;
            if ($this->is_https($url)) {
                $this->ssl();
            }
        }
        return $this;
    }

    public function set_ssl_key($ssl_key = null)
    {
        if ($ssl_key) {
            if (file_exists($ssl_key) && is_file($ssl_key)) {
                $this->opts[CURLOPT_SSLKEYTYPE] = 'PEM';
                $this->opts[CURLOPT_SSLKEY] = $ssl_key;
            }
        }
        return $this;
    }

    public function set_content_type($content_type = 'application/json;charset=UTF-8')
    {
        return $this->set_header(['Content-Type: ' . $content_type]);
    }

    public function set_header($header = array())
    {
        //未定义header则初始化
        if (!isset($this->opts[CURLOPT_HTTPHEADER]) || !is_array($this->opts[CURLOPT_HTTPHEADER])) {
            $this->opts[CURLOPT_HTTPHEADER] = [];
        }
        //传入header不为空则处理
        if ($header && is_array($header)) {
            foreach ($header as $value) {
                //判断不存在则push
                if (!in_array($value, $this->opts[CURLOPT_HTTPHEADER])) {
                    $this->opts[CURLOPT_HTTPHEADER][] = $value;
                }
            }
        }
        return $this;
    }

    public function set_cookies($cookies = null)
    {
        if ($cookies) {
            $this->opts[CURLOPT_COOKIE] = $cookies;
            $this->request_cookies = $cookies;
        }
        return $this;
    }


    public function set_proxy(
        $host = null,
        $port = 80,
        $type = CURLPROXY_HTTP,
        $user = null,
        $pass = null,
        $auth = null
    )
    {
        $host && $this->opts[CURLOPT_PROXY] = $host;
        $port && $this->opts[CURLOPT_PROXYPORT] = $port;
        $this->opts[CURLOPT_PROXYTYPE] = $type;
        if ($user && $pass) {
            $this->opts[CURLOPT_PROXYUSERPWD] = $user . ':' . $pass;
        }
        $auth && $this->opts[CURLOPT_PROXYAUTH] = $auth;
        return $this;
    }

    //设置伪造host
    public function set_host($host)
    {
        $this->set_header(["Host: $host"]);
        return $this;
    }

    //设置伪造user_agent
    public function set_user_agent($user_agent = null)
    {
        $user_agent =
            $user_agent ?: 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.' . mt_rand(111,
                    999) . ' Safari/537.37';
        $this->set_header(["User-Agent: $user_agent"]);
        return $this;
    }

    //设置伪造ip
    public function set_ip($ip = null)
    {
        $ip = $ip ?: mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255);
        $this->set_header(["X-FORWARDED-FOR: $ip", "CLIENT-IP: $ip"]);
        return $this;
    }

    public function set_timeout($second)
    {
        if (isset($this->opts[CURLOPT_TIMEOUT_MS])) {
            unset($this->opts[CURLOPT_TIMEOUT_MS]);
        }
        if (isset($this->opts[CURLOPT_NOSIGNAL])) {
            unset($this->opts[CURLOPT_NOSIGNAL]);
        }
        $this->opts[CURLOPT_TIMEOUT] = $second;
        return $this;
    }

    public function set_timeout_millisecond($millisecond)
    {
        if (isset($this->opts[CURLOPT_TIMEOUT])) {
            unset($this->opts[CURLOPT_TIMEOUT]);
        }
        $this->opts[CURLOPT_NOSIGNAL] = true;
        $this->opts[CURLOPT_TIMEOUT_MS] = $millisecond;
        return $this;
    }

    public function ssl()
    {
        $this->opts[CURLOPT_SSL_VERIFYHOST] = 2;
        $this->opts[CURLOPT_SSL_VERIFYPEER] = 0;
        $this->opts[CURLOPT_SSLVERSION] = 1;
        return $this;
    }

    public function __wakeup()
    {
        self::$instance = $this;
    }

    public function __destruct()
    {
        $this->close();
        //获取当前class所有属性 逐一销毁,释放内存
        $reflect = new \ReflectionClass($this);
        $pros = $reflect->getDefaultProperties();
        foreach ($pros as $key => $val) {
            unset($$key);
        }
    }

    public function destroy()
    {
        unset($this->url, $this->method, $this->data, $this->request_cookies, $this->opts, $this->ignore_log);
    }

    public function close()
    {
        if (is_resource($this->ch)) {
            curl_close($this->ch);
        }
    }

    private function merge_opts()
    {
        //设置CURL OPT选项
        $opts = [
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_AUTOREFERER => true, //自动设置来路信息
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => array()
        ];
        //设置curl默认访问为IPv4,否则请求速度会非常慢
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        //因为键是数字 不能用array_merge 参考http://blog.csdn.net/sky_zhe/article/details/9005477
        $this->opts += $opts;
        return $this;
    }

    /**
     * Performs multiple curl requests
     *
     * @access private
     * @return bool|mixed|$this
     * @throws \Exception
     */
    private function curl_exec()
    {
        /* 初始化并执行curl请求 */
        $this->ch = curl_init();
        //将配置合并
        $this->merge_opts();
        if (is_array($this->url)) {
            return $this->rolling_curl();
        }
        curl_setopt_array($this->ch, $this->opts);
        file_put_contents('./curl_exec.txt', json_encode([$this->ch, $this->opts], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        $return = curl_exec($this->ch);
        $this->response = $return;
        $request_info = curl_getinfo($this->ch);
        $this->request_info = $request_info;
        $this->errcode = curl_errno($this->ch);

        $this->destroy();
        return $this;
    }

    public function get_response()
    {
        return $this->response;
    }

    public function get_cookies()
    {
        $this->return_cookies = '';
        if (preg_match_all('/Set-cookie:[\s]+([^=]+)=([^;]+)/i', $this->get_header(), $match)) {
            $cookies = [];
            foreach ($match[1] as $key => $val) {
                $cookies[] = $val . '=' . $match[2][$key];
            }
            $this->return_cookies = join(';', $cookies);
        }
        return $this->return_cookies;
    }

    public function request_info()
    {
        return $this->request_info;
    }

    public function request_header()
    {
        return $this->request_info['request_header'];
    }

    public function get_header_array()
    {
        $header = $this->get_header();
        $header = explode("\r\n", $header);
        $header_arr = [];
        foreach ($header as $key => $val) {
            if (!$val) {
                continue;
            }
            $k = ToolsService::search_str("", ":", $val);
            if ($k) {
                $header_arr[$k] = str_replace($k . ': ', '', $val);
            } else {
                $header_arr[] = $val;
            }
        }
        return $header_arr;
    }

    public function get_header()
    {
        $this->header = substr($this->get_response(), 0, $this->request_info['header_size']);
        return $this->header;
    }

    public function output()
    {
        ob_clean();
        $header = $this->get_header();
        $headers = explode("\r\n", $header);
        foreach ($headers as $header) {
            $header && header($header);
        }
        echo $this->get_body();
        exit();
    }

    public function get_body()
    {
        if ($this->errcode) {
            return false;
        }
        $this->body = substr($this->get_response(), $this->request_info['header_size']);
        return ToolsService::array_iconv($this->body);
    }

    public function get_body_array()
    {
        if ($this->errcode) {
            return false;
        }
        $body = $this->get_body();
        if (ToolsService::is_json($body)) {
            //PHP解析JSON得到科学计数法后的int
            //$json = '{"number": 12345678901234567890}';
            // var_dump(json_decode($json,1));//[ "number" => 1.2345678901235e+19]
            //var_dump(json_decode($json,1, 512, JSON_BIGINT_AS_STRING));//["number" => "12345678901234567890"]
            //http://cn2.php.net/manual/zh/function.json-decode.php
            return json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
        }
        return false;
    }

    function curlDownloadImg($remote, $cookies = null)
    {
        $mimes = [
            'image/x-ms-bmp' => 'bmp',
            'image/bmp' => 'bmp',
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/x-icon' => 'ico'
        ];
        try {
            $headers = get_headers($remote, 1);
            $type = $headers['Content-Type'];
            //不是图片类型
            if (empty($mimes[$type])) {
                //wr_log('发现未知图片类型:' . $type);
                return false;
            }
            $extension = $mimes[$type];
        } catch (Exception $e) {
            //有异常暂时默认jpg先,因为后面还有检测逻辑.兼容下载微信头像
            $extension = 'jpg';
        }
        // 获取响应的类型
        $path = 'file' . DS . 'downloads' . DS . md5($remote) . '.' . $extension;
        $local = ToolsService::get_absolute_path($path);
        return $this->download($local, $remote, $cookies);
    }

    public function download_file($remote)
    {
        // 获取响应的类型
        $path = 'file' . DS . 'downloads' . DS . md5($remote) . '.zip';
        $local = ToolsService::get_absolute_path($path);
        return $this->download($local, $remote);
    }

    protected function download($local, $remote, $cookies = null)
    {
        if (!file_exists($local)) {
            $cp = curl_init($remote);
            $fp = fopen($local, "w");
            curl_setopt($cp, CURLOPT_FILE, $fp);
            if ($cookies) {
                curl_setopt($cp, CURLOPT_COOKIE, $cookies);
            }
            curl_setopt($cp, CURLOPT_HEADER, 0);
            curl_exec($cp);
            curl_close($cp);
            fclose($fp);
        }
        //检测图片是否大小等于0,是则说明失败,删除掉,并返回false
        $file_obj = new \SplFileObject($local);
        $file_size = $file_obj->getSize();
        if ($file_size == 0) {
            unlink($local);
            return false;
        }
        return $local;

    }

    /**
     * Performs multiple curl requests
     *
     * @access private
     * @return array
     * @throws \Exception
     */
    private function rolling_curl()
    {
        $master = curl_multi_init();
        // start the first batch of requests
        $url_array = $this->url;
        $return = [];
        // start the first batch of requests
        $max = min(count($url_array), $this->max_request_num);
        $post_data = $this->data;
        $is_more_array = is_array($post_data) && count($post_data) !== count($post_data, 1);
        if ($is_more_array && count($post_data) != count($url_array)) {
            $this->errmsg = 'url个数和data数组长度不同';
            return false;
        }
        for ($i = 0; $i < $max; $i++) {
            $ch = curl_init();
            $this->set_url(array_pop($url_array));
            if ($is_more_array) {
                $this->set_data(array_pop($post_data));
            }
            curl_setopt_array($ch, $this->opts);
            curl_multi_add_handle($master, $ch);
        }
        do {
            while (($exec_run = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM) {
                ;
            }
            if ($exec_run != CURLM_OK) {
                break;
            }
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($master)) {
                // get the info and content returned on the request
                $info = curl_getinfo($done['handle']);
                $output = curl_multi_getcontent($done['handle']);
                // send the return values to the callback function.
                $callback = $this->callback;
                if (is_callable($callback)) {
                    $result = call_user_func($callback, $output);
                    $return[] = [
                        'output' => $output,
                        'request_info' => $info,
                        'result' => $result,
                    ];
                }
                // start a new request (it's important to do this before removing the old one)
                if (!empty($url_array)) {
                    $ch = curl_init();
                    $this->set_url(array_pop($url_array));
                    if ($is_more_array) {
                        $this->set_data(array_pop($post_data));
                    }
                    curl_setopt_array($ch, $this->opts);
                    curl_multi_add_handle($master, $ch);
                }
                // remove the curl handle that just completed
                curl_multi_remove_handle($master, $done['handle']);

            }

            // Block for data in / output; error handling is done by curl_multi_exec
            if ($running) {
                curl_multi_select($master, 30);
            }
        } while ($running);
        curl_multi_close($master);
        return $return;
    }
}

//curl方法不存在就设置一个curl方法
if (!function_exists('curl')) {
    function curl()
    {
        return HttpService::get_instance();
    }
}