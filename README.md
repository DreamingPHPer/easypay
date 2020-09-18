# Easypay
此组件旨在帮助你快速接入各种支付，让支付接入变得更加简单。目前，已支持支付宝、微信的接入。

## 安装命令
```php
composer require dreamboy/easypay
```

## 使用方法
所有支付方式的使用都以如下类为入口：
 ```php
Dreamboy\Easypay\Factory
```

1、微信支付

```php
use Dreamboy\Easypay\Factory;
$payment = Factory::wechat($config);
```

2、支付宝支付 

```php
<?php
use Dreamboy\Easypay\Factory;
$payment = Factory::alipay($config);
```

每一个支付方式都包含有如下几个方法：

- 获取预授权：`prepay()`
- 查询订单：`query()`
- 退款：`refund()`
- 查询退款：`queryRefund()`

其他具体内容，请查看具体文件