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
[ansuns@centos-devfull ~]# php -v
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
    'org_id' => '', // org_id
    'public_key' => '',  // agent public_key
    'private_key' => '',  // agent public_key
    'merc_id' => '',  // merchant_id
];

$pay = new Pay(['chinaebi' => $payConfig]);

$opition['trancde'] = 'P05';
$opition['title'] = 'test pay';
$opition['notify_url'] = "https://www.xxx.com/pay_notify_url";  // callback url
$opition['pay_amount'] = 100;
$opition['mer_order_no'] = time();
$pay = new Pay(['chinaebi' => $payConfig]);
$res = $pay->driver('chinaebi')->gateway('pos')->apply($opition);

```

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/ansuns/b24679402957c63ec426) for details on our code of conduct, and the process for submitting pull requests to us.

## Authors

* **Ansuns** - *a unknow developer* - [HomePage](https://github.com/ansuns)

See also the list of [contributors](https://github.com/ansuns/project/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
