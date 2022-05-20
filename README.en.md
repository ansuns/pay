# Third Party Payment

Integrate payment channels of wechat service providers, Alipay service providers, accompanying payment, 
Shanghai e-bank and other third-party service providers.

The products are mainly imported in the service provider mode to realize multiple payment methods 
(QR code, bar code, face brush, applet, official account, H5 payment).
## Getting Started

First of all, the SDK is mainly used to provide services for carrying out payment business in Chinese Mainland. Please ensure that your business meets the cooperation conditions of different third-party payment service providers, and ensure the capital security of you and merchants.

At the same time, please ensure that the information provided by your merchant is legal and correct.

For others, please decide whether to use the SDK in combination with the personal or company's own conditions

When you decide to use it, please look down
### Prerequisites


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

When your software environment meets the requirements, you can install it

```
composer require ansuns/pay
```
Use examples
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
