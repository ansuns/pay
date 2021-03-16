# 三方支付聚合

#### 介绍
整合微信服务商、支付包服务商、随行付、上海电银等三方服务商支付通道；
主要是以服务商模式进件，支付...

#### 安装教程
使用Composer安装：composer require ansuns/pay dev-master

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
$opition['notify_url'] = "https://www.xxx.com//pay_notify_url";  // 回调地址
$opition['pay_amount'] = 100;
$opition['mer_order_no'] = time();
$pay = new Pay(['chinaebi' => $payConfig]);
$res = $pay->driver('chinaebi')->gateway('pos')->apply($opition);

``` 

#### 免责声明，本扩展包仅在私人项目运行通过