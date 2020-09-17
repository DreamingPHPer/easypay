<?php
namespace Dreamboy\Easypay\Alipay;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use Dreamboy\Easypay\Kernel\BasePayment;

/**
 * Class Payment
 * @package Dreamboy\Easypay\Alipay
 */
class Payment extends BasePayment
{
    /**
     * 交易类型
     * @var string
     */
    protected $tradeType = 'app'; // 默认是APP支付

    /**
     * Payment constructor.
     * @param array $config 支付配置
     */
    public function __construct($config)
    {
        $this->tradeType = $config['trade_type'] ?? 'app';

        Factory::setOptions($this->setConfig($config));
    }

    /**
     * @param $configData
     * @return Config
     */
    private function setConfig($configData)
    {
        $config = new Config();
        $config->protocol = 'https';
        $config->gatewayHost = 'openapi.alipay.com';
        $config->signType = 'RSA2';
        $config->appId = $configData['appId'] ?? '';

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $config->merchantPrivateKey = !empty($configData['merchantPrivateKeyPath']) ? trim(file_get_contents($configData['merchantPrivateKeyPath'])) : $configData['merchantPrivateKey'];
        $config->alipayCertPath = $configData['alipayCertPath'] ?? '';
        $config->alipayRootCertPath = $configData['alipayRootCertPath'] ?? '';
        $config->merchantCertPath = $configData['merchantCertPath'] ?? '';

        // 异步通知接收服务地址
        $config->notifyUrl = $configData['notifyUrl'] ?? '';

        return $config;
    }

    /**
     * 获取预授权
     * @param string $subject 订单描述
     * @param string $outTradeNo 商户订单号
     * @param numeric $totalAmount 订单金额
     * @return string
     * @throws \Exception
     */
    public function prepay($subject, $outTradeNo, $totalAmount)
    {
        switch ($this->tradeType) {
            case 'app':
                $result = Factory::payment()->app()->pay($subject, $outTradeNo, $totalAmount);
                break;
            default:
                throw new \Exception('当前支付方式暂不支持');

        }

        // 异常处理
        $responseChecker = new ResponseChecker();
        if (!$responseChecker->success($result)) {
            throw new \Exception($result->msg."，".$result->subMsg);
        }

        return $result->body;
    }

    /**
     * 查询订单
     * @param string $outTradeNo 商户订单号
     * @return string
     * @throws \Exception
     */
    public function query($outTradeNo)
    {
        // 查询订单信息
        $result = Factory::payment()->common()->query($outTradeNo);

        // 异常处理
        $responseChecker = new ResponseChecker();
        if (!$responseChecker->success($result)) {
            throw new \Exception($result->msg."，".$result->subMsg);
        }

        return $result->body;
    }

    /**
     * 关闭订单
     * @param string $outTradeNo 商户订单号
     * @return mixed
     * @throws \Exception
     */
    public function close($outTradeNo)
    {
        $result = Factory::payment()->common()->close($outTradeNo);

        // 异常处理
        $responseChecker = new ResponseChecker();
        if (!$responseChecker->success($result)) {
            throw new \Exception($result->msg."，".$result->subMsg);
        }

        return $result->body;
    }

    /**
     * 异步回调
     * @param array $data 回调参数
     * @param \Closure $closure 参数校验成功后的回调
     * @return mixed
     * @throws \Exception
     */
    public function notify($data, \Closure $closure)
    {
        $validateResult = Factory::payment()->common()->verifyNotify($data);
        if (!$validateResult) {
            throw new \Exception('回调参数校验失败');
        }

        return call_user_func_array($closure, $data);
    }

    /**
     * 退款
     * @param string $outTradeNo 商户订单号
     * @param numeric $refundAmount 退款金额
     * @return mixed
     * @throws \Exception
     */
    public function refund($outTradeNo, $refundAmount)
    {
        $result = Factory::payment()->common()->refund($outTradeNo, $refundAmount);

        // 异常处理
        $responseChecker = new ResponseChecker();
        if (!$responseChecker->success($result)) {
            throw new \Exception($result->msg."，".$result->subMsg);
        }

        return $result->body;
    }

    /**
     * 退款单查询
     * @param string $outTradeNo 商户订单号
     * @param string $outRequestNo 商户退款单号
     * @return mixed
     * @throws \Exception
     */
    public function refundQuery($outTradeNo, $outRequestNo)
    {
        $result = Factory::payment()->common()->queryRefund($outTradeNo, $outRequestNo);

        // 异常处理
        $responseChecker = new ResponseChecker();
        if (!$responseChecker->success($result)) {
            throw new \Exception($result->msg."，".$result->subMsg);
        }

        return $result->body;
    }
}