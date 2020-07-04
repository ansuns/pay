<?php

namespace Ansuns\Pay;

use Ansuns\Pay\Contracts\Config;
use Ansuns\Pay\Exceptions\InvalidArgumentException;

/**
 * Class Pay
 * @package Pay
 */
class Pay
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $drivers;

    /**
     * @var string
     */
    private $gateways;


    protected static $instance = null;
    public $driver;
    public $class;
    public $scene;
    public $pay_parameter = null;
    protected $debug;

    /**
     * Pay constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = new Config($config);
    }

    public function __call($name, $arguments)
    {
        $pays = $this->get_class();
        return call_user_func_array([$pays->driver(ucfirst($this->class))->gateway(ucfirst($this->scene)), $name],
            $arguments);
    }

    /**
     * @return Pay
     */
    public function get_class()
    {
        $pay_parameter = $this->get_pay_parameter();
        return new self([ucfirst($this->class) => $pay_parameter]);
    }

    public function get_pay_parameter()
    {
        if ($this->pay_parameter) {
            return $this->pay_parameter;
        }

//        $pay_parameter                       = $parameter['parameter'];
//        $pay_parameter['pay_parameter_guid'] = $parameter['guid'];
//        if ($this->debug) {
//            $pay_parameter['debug'] = true;
//        }
//        $this->class         = $parameter['class'];
//        $this->pay_parameter = $pay_parameter;
        return $this->pay_parameter;
    }

    /**
     * 指定驱动器
     * @param string $driver
     * @return $this
     */
    public function driver($driver)
    {
        if (is_null($this->config->get($driver))) {
            throw new InvalidArgumentException("Driver [$driver]'s Config is not defined.");
        }
        $this->drivers = $driver;
        return $this;
    }

    /**
     * 指定操作网关
     * @param string $gateway
     * @return \Pays\Contracts\GatewayInterface|\Pays\Gateways\Lianfutong|\Pays\Gateways\Wechat|\Pays\Gateways\Alipay
     */
    public function gateway($gateway = 'web')
    {
        if (!isset($this->drivers)) {
            throw new InvalidArgumentException('Driver is not defined.');
        }
        return $this->gateways = $this->createGateway($gateway);
    }

    /**
     * 创建操作网关
     * @param string $gateway
     * @return mixed
     */
    protected function createGateway($gateway)
    {
        if (!file_exists(__DIR__ . '/Gateways/' . ucfirst($this->drivers) . '/' . ucfirst($gateway) . '.php')) {
            throw new InvalidArgumentException("Gateway [$gateway] is not supported.");
        }
        $gateway = __NAMESPACE__ . '\\Gateways\\' . ucfirst($this->drivers) . '\\' . ucfirst($gateway);
        return new $gateway($this->config->get($this->drivers));
    }
}
