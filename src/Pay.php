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

    /**
     * @param $name
     * @param $arguments
     * @return false|mixed
     */
    public function __call($name, $arguments)
    {
        $pays = $this->getClass();
        return call_user_func_array([$pays->driver(ucfirst($this->class))->gateway(ucfirst($this->scene)), $name],
            $arguments);
    }

    /**
     * @return Pay
     */
    public function getClass()
    {
        $pay_parameter = $this->getPayPrameter();
        return new self([ucfirst($this->class) => $pay_parameter]);
    }

    /**
     * @return null
     */
    public function getPayPrameter()
    {
        if ($this->pay_parameter) {
            return $this->pay_parameter;
        }
        return $this->pay_parameter;
    }

    /**
     * 指定驱动器
     * @param string $driver
     * @return $this
     */
    public function driver(string $driver)
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
     * @return mixed
     */
    public function gateway(string $gateway = 'web')
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
    protected function createGateway(string $gateway)
    {
        if (!file_exists(__DIR__ . '/Gateways/' . ucfirst($this->drivers) . '/' . ucfirst($gateway) . '.php')) {
            throw new InvalidArgumentException("Gateway [$gateway] is not supported.");
        }
        $gateway = __NAMESPACE__ . '\\Gateways\\' . ucfirst($this->drivers) . '\\' . ucfirst($gateway);
        return new $gateway($this->config->get($this->drivers));
    }
}
