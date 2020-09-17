<?php
namespace Dreamboy\Easypay\Alipay;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use Alipay\EasySDK\Payment\Huabei\Models\HuabeiConfig;
use Dreamboy\Easypay\Kernel\BasePayment;

/**
 * Class Payment
 * @package Dreamboy\Easypay\Alipay
 */
class Payment extends BasePayment
{
    /**
     * Payment constructor.
     * @param array $config 支付配置
     */
    public function __construct($config)
    {
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
    public function prepay($data, $tradeType = 'app', $isHuabei = false)
    {
        $subject = $data['subject'] ?? '';
        $outTradeNo = $data['out_trade_no'] ?? '';
        $totalAmount = $data['total_amount'] ?? '';

        switch ($tradeType) {
            case 'app':
                $app = Factory::payment()->app();
                if ($isHuabei) {
                    $app->optional('enable_pay_channels', 'pcredit');
                }

                $result = $app->pay($subject, $outTradeNo, $totalAmount);
                break;
            case 'wap':
                $quitUrl = $data['quit_url'] ?? '';
                $returnUrl = $data['return_url'] ?? '';

                $wap = Factory::payment()->wap();
                if ($isHuabei) {
                    $wap->optional('enable_pay_channels', 'pcredit');
                }

                $result = $wap->pay($subject, $outTradeNo, $totalAmount, $quitUrl, $returnUrl);
                break;
            case 'faceToFace':
                $authCode = $data['auth_code'] ?? '';
                $result = Factory::payment()->faceToFace()->pay($subject, $outTradeNo, $totalAmount, $authCode);
                break;
            case 'huabei':
                $buyerId = $data['buyer_id'] ?? '';
                $extendParams = HuabeiConfig::fromMap([
                    'hb_fq_num' => $data['hb_fq_num'] ?? 0,
                    'hb_fq_seller_percent' => $data['hb_fq_seller_percent'] ?? 100
                ]);

                $result = Factory::payment()->huabei()->create($subject, $outTradeNo, $totalAmount, $buyerId, $extendParams);
                break;
            case 'page':
                $returnUrl = $data['return_url'] ?? '';
                $result = Factory::payment()->page()->pay($subject, $outTradeNo, $totalAmount, $returnUrl);
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