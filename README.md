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
[ansuns@centos-devfull ~]# php -v
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

## 贡献

请阅读[contribution.md](https://gist.github.com/ansuns/b24679402957c63ec426)有关我们的行为准则以及向我们提交拉取请求的流程的详细信息。

## Authors

* **Ansuns** - *a unknow developer* - [HomePage](https://github.com/ansuns)

另请参见 [贡献者] 列表 (https://github.com/ansuns/project/contributors) 谁参与了这个项目。

## License

此项目是根据MIT许可证授权的-有关详细信息，请参阅[License.md]（License.md）文件
