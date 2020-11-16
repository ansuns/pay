<?php
require __DIR__ . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";
$config_test = array(
    'partner' => '900029000000354',
    'easy_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC2WTfvas1JvvaRuJWIKmKlBLmkRvr2O7Fu3k/zvhJs+X1JQorPWq/yZduY6HKu0up7Qi3T6ULHWyKBS1nRqhhHpmLHnI3sIO8E/RzNXJiTd9/bpXMv+H8F8DW5ElLxCIVuwHBROkBLWS9fIpslkFPt+r13oKFnuWhXgRr+K/YkJQIDAQAB',


    'sign_key' => 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAIqUuxd92eEBXVneDWhfNP6XCkLcGBO1YAulexKX+OdlfZzB/4NNHkOAQQy84k3ZgIUPIk5hewLbA+XGrk9Wih5HG3ZQeFugeoTcx3vwo7AQv7KnmcKEWFNlOr/EhB3JndmcQnBRsIRRdCP+7nobfBqU0jS8dnpcQX1AtBRZRnkfAgMBAAECgYAe+u70ansZ1Q9EduKycY5MWAHAPqnXRhXppJ3l4zmOqV6ye6Aef1ADsRlZuqQw2S3lESQPN7WjRskRRiBTtjn8Atul9YeC7+QirP1K8seUP5gKB4bcjlzzl1m5dmxldkptJAmdzwYn8PRTW0+tFVyEaD/B8hKGxij4Gew0e8bwCQJBAOboG3ttBESsG2cAtmP1MfKRTjVdY7qRMXzBybcAeobBbmgCQgybVXXgjbGai+qwrQqcVRIp6p1yDWTZxVSuDWsCQQCZpBhcayOCMZR6F8dQJSuSSSIJw/GGN7IXfMYIqLxA2oGzlQ0B1DffOUe2wrid+WdpLuYCz2LYPQHDEgYM1dwdAkEAnfwhEYm9ad73wLnUEQAqdHTGtex316aP3XQZt4Q0UQ73o2IoHsgI6OYDDIlZQfIv8xqTeiIDzEXEtEPrp8yOkQJBAIWAzFZKFqHD2UO6M8vVcKX9fGFF7TH2ZX75Qc82Z9ZmyDs2sgW71QzX5hPN4cQLeqswQFeCw14orMZHfBBdKJUCQQDiWYk85okRugsWtxeJFhMEt2oUT+Kd8Yz5Aiz3J9XIS+zWtJrFlv+hXkVedPJ3xtBF32DZrCbxDn3UjXipRaCP',
    'merchant_id' => '900029000000354',
    'notify_url' => '127.0.0.1',
    'des_encode_key' => 'CueaiPrW9sIskbn9qkoPh9J3',
    'env' => 'test'
    // 'micro'=>true
);

$config_pro = array(
    'partner' => '900029000000354',
    'easy_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC2WTfvas1JvvaRuJWIKmKlBLmkRvr2O7Fu3k/zvhJs+X1JQorPWq/yZduY6HKu0up7Qi3T6ULHWyKBS1nRqhhHpmLHnI3sIO8E/RzNXJiTd9/bpXMv+H8F8DW5ElLxCIVuwHBROkBLWS9fIpslkFPt+r13oKFnuWhXgRr+K/YkJQIDAQAB',

    'sign_key' => 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBALLfMUbf4u8uDSeG0WR//LSvxv7qglKsHwws3mpyqUWJau1ZXcfMeNQf+OhGTFrKsyP1WS3kXa1ZErjIhdeX5Jq2TphgXWZ+HdNVtd/rmHo84cjHJZOOBSvSmVYzqsJT253LoX1ip2sx/zbobU+Sm//4I5Zo4/yuufWBElCL2cuPAgMBAAECgYEApkJ3Fx27Xf48E+VodDXSulA4c3GeuSFrqnF6Ow9g71WPohZS6QfRt7oQLjZJeoq2gFHpFpMRz7LfiAo6/e4deXEWtMRg4UHcZbdBlTR4oM8au1TTEq446VhllSNZ8qeHxL4zO4peVDNupp8rElOMTXiQLKOph+fioLi++tvqZGECQQDrvtI3QD68BEQXlwMcUHkczppTs3Gxtd5uElQ7BQCTpM1fBhFIzv+TNxhNLGo5+2z1MKjWNE1KBo1ZXZnJdSfLAkEAwj1ykXJkYWbNrTRGzh0ZjSg784c20TeEl2HZ4BvSdCwSRxZUdZYx4x6MUWISlnokaifXSJhnsU9vXoZR4xEKzQJBAN5SJdNfLgqIB2Mr0g4owh7tpFLdPpJ2Tl8FwBOswv96AwfjI/fC5vmBktRs13z45KdSjVb9Ggp+pVyqzfZUGwMCQGdPWWVEq2Em1ZQe7t3nmlR6ptBTBXPnjG0bzU8mXRwO6LXIial09hmvgMA0YmCInF+dyyJAdT5YWoqy9FDKGq0CQQCBstxGn3LUWcGxEmTtmDt3pkHVy2IYQl/xFCgWYW1xIrC7dO8GfLZLzUbq/yBGO1KCRaQpFYKbJNTETB0TlKSY',

    'merchant_id' => '900029000000354',

    'des_encode_key' => 's6yaiIycSFXufo4jEg3VmLs4',
    //'micro'=>true
    'env' => 'pro'
);
$config_pro_new = array(
    'channelid' => 'D01X20200320001',
    'merid' => '531000012967940',
    'termid' => '32767130',
    'channel_key' => 'd5riddkald4k4did',

    'sign_key' => 'df454lai8r2f8ddofd2ks012djkkdldk',
    //'micro'=>true
    'env' => 'pro',

);

$config_pro_new = array(
    'channelid' => 'D01X20200320001',
    'merid' => '531000012967939',
    'termid' => '32767129',
    'channel_key' => 'd5riddkald4k4did',

    'sign_key' => 'df454lai8r2f8ddofd2ks012djkkdldk',
    //'micro'=>true
    'env' => 'pro',

);

$config_pro_mch = array(
    'clientCode' => '48310019',
    'sign_key' => 'ShnaghaiOuAikeji',
    'mode' => 'mch',

);

$config_pro_mcht = array(
    'clientCode' => '48310037',
    'sign_key' => 'ShnaghaiOuAikeji',
    'mode' => 'mch',

);

//
$config_pro_mch2 = array(
    'clientCode' => '48318888',
    'sign_key' => 'fudhsyrgnbpdeiba',
    'mode' => 'mch',

);

$config_pro_mch_new = array(
    'clientCode' => '48310019',
    'sign_key' => 'ShnaghaiOuAikeji',
    'mode' => 'mch',

);
$config_pro_upload = array(
    'clientCode' => '48318888',
    'sign_key' => 'fudhsyrgnbpdeiba',
    'mode' => 'upload',

);
$config_pro_upload_my = array(
    'clientCode' => '48310019',
    'sign_key' => 'ShnaghaiOuAikeji',
    'mode' => 'upload',

);
$pay = new \Ansuns\Pay\Pay(['bhecard' => $config_pro_mch_new]);
$act = 'findsss';
switch ($act) {
    case 'upload11':
        $pay = new \Ansuns\Pay\Pay(['bhecard' => $config_pro_upload_my]);
        $operaTrace = [
            'fileName' => __DIR__ . DIRECTORY_SEPARATOR . "qqlogo.jpg",
            'picMode' => '01'
        ];
        $res = $pay->driver('bhecard')->gateway('mch')->photo($operaTrace);
        file_put_contents('./result.txt', json_encode($res["fileId"]) . PHP_EOL, FILE_APPEND);
        break;
    case 'findsss':
        $operaTrace = 'A20201116095346';
        $res = $pay->driver('bhecard')->gateway('mch')->find($operaTrace);
        break;
    case 'mch1':
        $temp = [
            'name' => 'robin',
            'sex' => 1
        ];
        $date = [
            "2011.01.01", "2039.01.01"
        ];
        $fileiD = "5fb1d9653e10af7300741d17";
        $options2 = [
            'messageType' => 'AGMERAPPLY',
            'backUrl' => 'https://www.baidu.com',
            'merInfo' => [
                'merMode' => '2',//'=>'1',// string 1 N 商户类型（0-企业 1-个体户 2-个人）
                'merName' => '欧爱测试门店',//'=>'1',// string 100 N 注册名称
                'businName' => '欧爱测试门店',//'=>'1',// string 50 Y 经营名称 (店名)
                'merEngName' => 'ouaiceshi',// string 50 Y 英文名称
                'merType' => '5331',//'=>'1',// string 4 N 商户类型（MCC），银联定义的'=>'1',//CC 编码
                'standardFlag' => '0',//'=>'1',// string 1 N 行业大类 0-标准、1-优惠、2-减免
                'merArea' => '5848',//'=>'1',// string 4 N 商户区域：银联地区码
                'merAddr' => '深圳市龙华区大浪街道',//'=>'1',// string 100 N 注册地址
                'businBegtime' => '0630',//'=>'1',// string 10 Y 营业时间：开始时间，格式：HHMM
                'businEndtime' => '2230',//'=>'1',// string 10 Y 营业时间：结束时间，格式：HHMM
                'employeeNum' => '1',//'=>'1',// string 1 Y 公司规模:1：0-50 人；2：50-100 人；3:100 以上
                'businForm' => '02',//'=>'1',// string 2 Y 经营形态：02-普通店、01-连锁店
            ],
            'plusInfo' => [
                'merLegal' => '杨锦',//'=>'',// string 50 N 法定代表人姓名
                'legalType' => '0',//'=>'',// string 1 N
                'legalCode' => '450881199108052911',//'=>'',// string 30 N 法人证件号码
                'legalValidity' => $date,//'=>'',// string 30 Y 证件有效期 格式：["起始日期","截止日期"],JSON 格式字符串, 例如：["2011.01.01","2039.01.01"]
                'legalPhone' => '15813765522',//'=>'',// string 30 Y 手机号
                'legalMobile' => '02022263212',// string 30 Y 固定电话
                'linkMan' => '杨锦',//'=>'',// string 50 Y 商户联系人姓名
                'linkmanType' => '0',//'=>'',// string 1 Y
                'linkmanCode' => '450881199108052911',//'=>'',// string 30 Y 证件号码
                'linkmanValidity' => $date,//'=>'',// string 30 Y 证件有效期 格式：["起始日期","截止日期"],JSON 格式字符串
                'linkmanPhone' => '15813765522',//'=>'',// string
            ],
            'licInfo' => [
                //'licName' => '欧爱测试门店',
                'businScope' => '欧爱测试门店',
                'capital' => '1000',
                'licAddr' => '深圳市龙华区',
                'controlerName' => '姓名',
                'controlerLegalCode' => '450881199108052911',
                'controlerLegalType' => '0',
                'controlerLegalValidity' => $date
            ],
            'sysInfo' => [
//                [
//                    'termMode' => '2',
//                    'username' => 'ouaitt',
//                    'areaNo' => '0755',
//                    'termArea' => '0755',
//                    'installaddress' => '深圳市龙华区',
//                    'linkMan' => '杨锦',
//                    'linkPhone' => '15813765522',
//                    'termModelLic' => '0755',
//                ]
            ],
            'accInfo' => [
                'bankName' => '中国银行',
                'bankCode' => '313100000013',
                'account' => '69696666666666666',
                'accName' => '欧爱大数据',
                'accType' => '00',
                'legalType' => "0",
                'legalCode' => '450881199108052911',
                'accPhone' => '15813765522'
            ],
            //'accInfoBak' => $temp,
            'funcInfo' => [
                [
                    'funcId' => 2,
                    'calcVal' => '0.38'
                ],
                [
                    'funcId' => 3,
                    'calcVal' => '0.38'
                ]
            ],
            'picInfoList' => [
                ['picMode' => '01', 'fileId' => '5fb1da323e10af7300741d1c'],
                ['picMode' => '02', 'fileId' => '5fb1da3d3e10af7300741d22'],
                ['picMode' => '03', 'fileId' => '5fb1da3e3e10af7300741d24'],
                ['picMode' => '04', 'fileId' => '5fb1da3f3e10af7300741d26'],
                ['picMode' => '05', 'fileId' => '5fb1da3f3e10af7300741d28'],
                ['picMode' => '06', 'fileId' => '5fb1da403e10af7300741d2a'],

                ['picMode' => '07', 'fileId' => '5fb1da413e10af7300741d2c'],
                ['picMode' => '08', 'fileId' => '5fb1dae13e10af7300741d39'],
                ['picMode' => '09', 'fileId' => '5fb1dae33e10af7300741d3b'],
                ['picMode' => '10', 'fileId' => '5fb1dae33e10af7300741d3d'],

                ['picMode' => '11', 'fileId' => '5fb1db863e10af7300741d45'],
                ['picMode' => '12', 'fileId' => '5fb1db873e10af7300741d47'],
                ['picMode' => '14', 'fileId' => '5fb1db883e10af7300741d49'],
                ['picMode' => '15', 'fileId' => '5fb1db893e10af7300741d4b'],
            ],
            'operaTrace' => 'A' . date('YmdHis'),
        ];
        $res = $pay->driver('bhecard')->gateway('mch')->apply($options2);
        break;
    case 'refund11':
        $options = [
            'out_trade_no' => 'RE' . date('YmdHis') . rand(9999, 10000),
            'refund_fee' => 1,
            'subject' => '订单退款',
            //'out_trade_no' => 'o_dyg4sob1CKgdgue3yudDpGQtwE',
            'origin_trade_no' => 'ALIQR20201108200240469241',
            'opt' => 'zwrefund',
        ];
        // $res = $pay->driver('bhecard')->gateway('miniapp')->apply($options);
        // $options="WXQR20201105231124218253";
        $res = $pay->driver('bhecard')->gateway('miniapp')->refund($options);
        break;
    case'minipay':
        $options = [
            'tradetrace' => date('YmdHis') . rand(9999, 10000),
            'tradeamt' => 1,
            'body' => '欧爱测试购买',
            'openid' => 'o_dyg4sob1CKgdgue3yudDpGQtwE',
            'notifyurl' => 'https://www.baidu.com',
            'opt' => 'wxPreOrder',
        ];
        // $res = $pay->driver('bhecard')->gateway('miniapp')->apply($options);
        $options = "WXQR20201105231124218253";
        $res = $pay->driver('bhecard')->gateway('miniapp')->find($options);
        break;
    case'1pay':
        $options = [
            'tradetrace' => date('YmdHis') . rand(9999, 10000),
            'tradeamt' => 1,
            'body' => '欧爱测试购买',
            'openid' => 'o_dyg4sob1CKgdgue3yudDpGQtwE',
            'notifyurl' => 'https://www.baidu.com',
            'opt' => 'wxPreOrder',
        ];
        $options2 = [
            'tradetrace' => date('YmdHis') . rand(9999, 10000),
            'tradeamt' => 1,
            'body' => '欧爱测试购买',
            'openid' => '2088002093276180',
            'notifyurl' => 'https://www.baidu.com',
            'opt' => 'apPreOrder',

        ];
        $res = $pay->driver('bhecard')->gateway('mp')->apply($options);
        break;
    case 'pay';
        $options1 = [
            'subject' => '欧爱测试购买',
            'merchant_id' => '900029000000354',
            'out_trade_no' => date('YmdHis') . rand(9999, 10000),
            'amount' => 1,
            'pay_type' => 'wxJsPay',//wxJsPay:微信公众号支付； aliJsPay:支付宝服务窗支付；
            'open_id' => 'otvxTs_JZ6SEiP0imdhpi50fuSZg',
            'business_time' => date('Y-m-d H:i:s'),
            'notify_url' => '127.0.0.1',
        ];

        $options2 = [
            'subject' => '欧爱测试购买',
            'merchant_id' => '900029000000354',
            'out_trade_no' => date('YmdHis') . rand(9999, 10000),
            'amount' => 1,
            'pay_type' => 'aliJsPay',//wxJsPay:微信公众号支付； aliJsPay:支付宝服务窗支付；
            'open_id' => '2088002093276180',
            'business_time' => date('Y-m-d H:i:s'),
            'notify_url' => '127.0.0.1',
        ];

        $options = $options2;
        $res = $pay->driver('bhecard')->gateway('miniapp')->apply($options);
        break;
    case'newPay':
        $options = [
            "out_trade_no" => time() . rand(1000000, 9999999),
            "merchant_id" => '900029000000354',
            "acc" => "6214835650183187",
            "name" => "张山",
            "amount" => "100",
            "acc_type" => 2,
            "notify_url" => "http://www.baidu.com",
        ];
        $res = $pay->driver('bhecard')->gateway('miniapp')->newPay($options);
        break;
    case'find':
        $out_trade_no = '2020102210534210000';
        $out_trade_no = '1546050160003';
        $res = $pay->driver('bhecard')->gateway('miniapp')->find($out_trade_no);
        break;
    case 'mch':
        $options = [
            "merchant_id" => "900029000002554", // 商户号，是	合作伙伴商户号
            "name" => "测试商户A", // 商户名称，是
            "userType" => "2", //商户类型 1: 小微商户，2: 个体商户，3: 企业商户
            "businessProvince" => "13", // 商户经营省份
            "businessCity" => "1301", // 商户经营城市
            "contactTel" => "13999999999", // 电话
            "address" => "平安路1号101", // 商户地址
            "mobile" => "13999999999", // 商户手机号
            "email" => "13999999999@qq.com", // 商户邮箱，小微商户和企业商户为必填项
            "mccType" => "1", // 行业类别
            "licenseName" => "测试商户A", //营业执照名称，小微商户和企业商户为必填项
            "licenseNum" => "12345679890", //营业执照编号，小微商户和企业商户为必填项
            "licenceBeginDate" => "2000-01-01", //营业执照起始时间，小微商户和企业商户为必填项，格式yyyy-MM-dd
            "licenceEndDate" => "2020-12-31", //营业执照终止时间，小微商户和企业商户为必填项，格式yyyy-MM-dd
            "registeredCapital" => "100000000", //注册资本金
            "currency" => "人民币", //注册资本金币种
            "ip" => "192.168.1.1", //商户IP地址
            "mac" => "aabbccddeeff", //商户MAC地址
            "icp" => "ICP-0001", //商户ICP备案号
            "website" => "www.aaa.com", //商户网址
            "appName" => "AAA网", //商户网站或app名称，否
            "agentName" => "张三", //代办人姓名，入网代办人的姓名
            "agentIdNoType" => "1", //代办人证件类型
            "agentIdNo" => "310110199001010001", //代办人证件号码
            "agentIdExpireDate" => "2015-10-02", //代办人证件有效期
            "realName" => "李四", //法人姓名
            "idnoType" => "1", //法人证件类型
            "idno" => "310110199001010001", //法人证件号码
            "idExpireDate" => "2021-03-01", //法人证件有效期
            "scope" => "技术开发、技术咨询、技术服务、技术转让、技术推广;货物进出口、技术进出口、代理进出口。(企业依法自主选择经营项目,开展经营活动;依法须经批准的项目,经相关部门批准后依批准的内容开展经营活动;不得从事本市产业政策禁止和限制类项目的经营活动。)", //商户经营范围
            "accType" => "1", //银行账户类型，账户类型:1: 对公，2: 对私，企业商户只能进件对公账户
            "bankAccName" => "李四", //银行户名
            "bankAcc" => "6200000123456789", //银行账号
            "bank" => "中国建设银行", //开户行
            "province" => "31", //开户行所在省份
            "city" => "3101", //开户行所在城市
            "bankName" => "中国建设银行上海航华支行", //开户网点
            "bankAccIdno" => "310110199001010001", //持卡人身份证，若业务需要支持非法人结算卡,开通相应功能后填写该字段有效
            "bankAccMobile" => "13999999999", //持卡人手机号，若业务需要支持非法人结算卡,开通相应功能后填写该字段有效
        ];

        $options2 = [
            "merchant_id" => "900029000000354",
            "name" => "潘海山接口测试052301",
            "userType" => 2,
            "businessProvince" => 13,
            "businessCity" => 1301,
            "contactTel" => "13801010101",
            "address" => "XX大街1号",
            "mobile" => "13817264357",
            "email" => "511562751@qq.com",
            "mccType" => 2,
            "licenseName" => "潘海山接口测试3",
            "licenseNum" => "AAAABBBBCCCCDDDD",
            "licenceBeginDate" => "2010-01-01",
            "licenceEndDate" => "2050-01-01",
            "registeredCapital" => "1580000",
            "currency" => "人民币",
            "ip" => "192.168.1.1",
            "mac" => "aabbccdd1234",
            "icp" => "ICP-0001",
            "website" => "www.aaa.com",
            "appName" => "潘海山接口测试APP",
            "agentName" => "张三",
            "agentIdNoType" => 1,
            "agentIdNo" => "310110199001010001",
            "agentIdExpireDate" => "2024-12-31",
            "realName" => "潘海山",
            "idno" => "310228199203276017",
            "idnoType" => 1,
            "idExpireDate" => "2020-05-23",
            "scope" => "这里是运营范围",
            "accType" => 2,
            "bankAccName" => "潘海山",
            "bankAcc" => "6217001210033089498",
            "bank" => "中国建设银行",
            "province" => 31,
            "city" => 3101,
            "bankName" => "中国建设银行上海航华支行"
        ];
        $res = $pay->driver('bhecard')->gateway('mch')->apply($options2);

        break;
    case 'photo':
        $options = [
            "merchant_id" => '900029000000354',
            //'id_no' => '310110199001010001',
            'type' => 3331,
            'image_str' => __DIR__ . DIRECTORY_SEPARATOR . "qqlogo.jpg"

        ];
        $res = $pay->driver('bhecard')->gateway('mch')->photo($options);
        break;
    case 'createPageInfo':
        //注册分账账户
        //	{"b10" : "上海市xxx路xxx号","b20" : "√","b22" : "其他应扣款项","b14" : "张三","b15" : "13888888888","d1" : "20","d2" : "25","b7" : "麟翼公司",}
        $options = [
            "merchant_id" => '900029000000354',
            //'id_no' => '310110199001010001',
            'model_name' => '三方协议模板--新模板',
            'dynamic_map' => json_encode([
                'b10' => '上海市xxx路xxx号', 'b20' => 'YES', 'b22' => '其他应扣款项', 'b14' => '张三',
                'b15' => '13888888888', 'd1' => '20', 'b7' => '麟翼公司'
            ], 320)

        ];
        //var_dump($options);
        $res = $pay->driver('bhecard')->gateway('mch')->createPageInfo($options);
        break;
    case 'rate':
        $options = [
            "6" => '0.38',
            '7' => "0.38",

        ];
        $res = $pay->driver('bhecard')->gateway('mch')->feeMuiltSet($options);
        break;
}

//[{"service":"merchant.add.input","partner":"900029000000354","sign":"ATJIsTx6nx6faWzyQEQ4OLPzhqT1Ii394RwY4eYxR5mHCTRYEeF+0L21CSNK2TqWCi3daLmuQIHAepHovNUpcM+twHDKUHfrLDlT+vWL1n\/0t6kr1D8daj8ixZdpJNL2tLEHIttqeTwVZI9pGxMY99ZDT1F60QFyin0DRMGRD68=","sign_type":"RSA","charset":"UTF-8","biz_content":"{\"merchant_id\":\"900029000000354\",\"name\":\"\u6f58\u6d77\u5c71\u63a5\u53e3\u6d4b\u8bd5052301\",\"userType\":2,\"businessProvince\":13,\"businessCity\":1301,\"contactTel\":\"13801010101\",\"address\":\"XX\u5927\u88571\u53f7\",\"mobile\":\"13817264357\",\"email\":\"511562751@qq.com\",\"mccType\":2,\"licenseName\":\"\u6f58\u6d77\u5c71\u63a5\u53e3\u6d4b\u8bd53\",\"licenseNum\":\"AAAABBBBCCCCDDDD\",\"licenceBeginDate\":\"2010-01-01\",\"licenceEndDate\":\"2050-01-01\",\"registeredCapital\":\"1580000\",\"currency\":\"\u4eba\u6c11\u5e01\",\"ip\":\"192.168.1.1\",\"mac\":\"aabbccdd1234\",\"icp\":\"ICP-0001\",\"website\":\"www.aaa.com\",\"appName\":\"\u6f58\u6d77\u5c71\u63a5\u53e3\u6d4b\u8bd5APP\",\"agentName\":\"\u5f20\u4e09\",\"agentIdNoType\":1,\"agentIdNo\":\"310110199001010001\",\"agentIdExpireDate\":\"2024-12-31\",\"realName\":\"\u6f58\u6d77\u5c71\",\"idno\":\"310228199203276017\",\"idnoType\":1,\"idExpireDate\":\"2020-05-23\",\"scope\":\"\u8fd9\u91cc\u662f\u8fd0\u8425\u8303\u56f4\",\"accType\":2,\"bankAccName\":\"\u6f58\u6d77\u5c71\",\"bankAcc\":\"6217001210033089498\",\"bank\":\"\u4e2d\u56fd\u5efa\u8bbe\u94f6\u884c\",\"province\":31,\"city\":3101,\"bankName\":\"\u4e2d\u56fd\u5efa\u8bbe\u94f6\u884c\u4e0a\u6d77\u822a\u534e\u652f\u884c\"}"},"{\"merchant_add_input_response\":{\"code\":\"00\",\"msg\":\"BUSINESS_OK\",\"merchant_id\":\"900029000003092\",\"trade_status\":\"SUCCESS\"},\"sign\":\"ZBGoqWsYV8dxSGki27vYZea0+MdbfUdTac11SfnaTq4i4jb3a+cz9I5SRE0OsXIaflx+Hyt\/0tUGaP4eHH3bhmahDmIHh\/lTNMHuhOfDWy\/SLKfcheTVYCWcT2mz3IpqarL+cuZFsNOFjz4T7cjqF+kz7lKSkiNZ0mv3tHeYdWE=\"}"]
var_dump($res);