# 三方支付

整合微信服务商、支付包服务商、随行付、上海电银等三方服务商支付通道。

主要是以服务商模式进件，实现多种方式支付（二维码，条码，刷脸，小程序，公众号，H5支付）。
## 快速入门

首先说明，该SDK主要是为在中国大陆开展支付业务提供服务的，请确保你开展业务是否满足不同第三方支付服务商的合作条件，确保你以及商户的资金安全。

同时请确保你的商户进件时，提供的资料是合法的，正确的。

其他请结合个人或公司的自身条件决定是否使用该SDK

当你决定使用时，那么请借着往下看吧

### 先决条件

PHP > 5.6

Composer

```
[root@centos-devfull ~]# php -v
PHP 7.1.33 (cli) (built: Mar 28 2022 17:37:07) ( NTS )
Copyright (c) 1997-2018 The PHP Group
Zend Engine v3.1.0, Copyright (c) 1998-2018 Zend Technologies

[ansuns@centos-devfull root]$ composer -V
Composer version 1.10.26 2022-04-13 16:39:56
```

### Installing

当你的软件环境符合要求后，那么可以安装了
```
composer require ansuns/pay
```
使用例子
```php
use Ansuns\Pay\Pay;

$payConfig = [
    'org_id' => '', // 机构号
    'public_key' => '',  // 代理商公钥
    'private_key' => '',  // 代理商私钥
    'merc_id' => '',  // 商户号
];

$pay = new Pay(['chinaebi' => $payConfig]);

$opition['trancde'] = 'P05';
$opition['title'] = '测试支付';
$opition['notify_url'] = "https://www.xxx.com/pay_notify_url";  // 回调地址
$opition['pay_amount'] = 100;
$opition['mer_order_no'] = time();
$pay = new Pay(['chinaebi' => $payConfig]);
$res = $pay->driver('chinaebi')->gateway('pos')->apply($opition);

``` 

## Deployment

Add additional notes about how to deploy this on a live system

## Built With

* [Dropwizard](http://www.dropwizard.io/1.0.2/docs/) - The web framework used
* [Maven](https://maven.apache.org/) - Dependency Management
* [ROME](https://rometools.github.io/rome/) - Used to generate RSS Feeds

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/PurpleBooth/b24679402957c63ec426) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags).

## Authors

* **Billie Thompson** - *Initial work* - [PurpleBooth](https://github.com/ansuns)

See also the list of [contributors](https://github.com/ansuns/project/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Hat tip to anyone whose code was used
* Inspiration
* etc
