<?php
namespace Dreamboy\Easypay\Wechat;

use Dreamboy\Easypay\Kernel\BasePayment;
use EasyWeChat\Factory;

/**
 * Class Payment
 * @package Dreamboy\Easypay\Wechat
 */
class Payment extends BasePayment {
    /**
     * Payment constructor.
     * @param array $config 支付配置
     */
    public function __construct($config)
    {
        $this->payment = Factory::payment($config);
    }

    /**
     * 获取预授权
     * @param $data 订单数据
     * @param bool $isContract 是否签约
     * @return array|\EasyWeChat\Kernel\Support\Collection|mixed|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function prepay($data, $isContract = false)
    {
        $returnData = $this->payment->order->unify($data, $isContract);
        return $this->getReturnData($returnData);
    }

    /**
     * 查询订单
     * @param string $outTradeNo 商户订单号
     * @param string $transactionId 外单号
     * @return array|\EasyWeChat\Kernel\Support\Collection|mixed|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function query($outTradeNo = '', $transactionId = '')
    {
        if (!empty($outTradeNo)) {
            $returnData = $this->payment->order->queryByOutTradeNumber($outTradeNo);
        } else if (!empty($transactionId)) {
            $returnData = $this->payment->order->queryByTransactionId($transactionId);
        }

        if (!empty($returnData)) {
            return $this->getReturnData($returnData);
        }

        throw new \Exception('参数 out_trade_no 和 transaction_id 不能同时为空');
    }

    /**
     * 关闭订单
     * @param $outTradeNo 商户订单号
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function close($outTradeNo)
    {
        $returnData = $this->payment->order->close($outTradeNo);
        return $this->getReturnData($returnData);
    }

    /**
     * 异步回调
     * @param \Closure $closure 匿名函数
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function notify(\Closure $closure)
    {
        $response = $this->payment->handlePaidNotify(function($message, $fail) use ($closure) {
            call_user_func($closure, $message, $fail);
        });

        return $response;
    }

    /**
     * 退款
     * @param $outRefundNo 退款单号
     * @param $totalFee 支付金额
     * @param $refundFee 退款金额
     * @param $outTradeNo 商户订单号 / 外单号
     * @param bool $isOutTradeNo 是否是商户订单号
     * @param array $config 退款配置
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function refund($outRefundNo, $totalFee, $refundFee, $outTradeNo, $isOutTradeNo = true, $config = [])
    {
        if ($isOutTradeNo) {
            $returnData = $this->payment->refund->byOutTradeNumber($outTradeNo, $outRefundNo, $totalFee, $refundFee, $config);
        } else {
            $returnData = $this->payment->refund->byTransactionId($outTradeNo, $outRefundNo, $totalFee, $refundFee, $config);
        }

        return $this->getReturnData($returnData);
    }

    /**
     * 退款单查询
     * @param string $outRefundNo 商户退款单号
     * @param string $refundNo 微信退款单号
     * @param string $outTradeNo 商户订单号
     * @param string $transactionId 微信订单号
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function refundQuery($outRefundNo = '', $refundNo = '', $outTradeNo = '', $transactionId = '')
    {
        if (!empty($outRefundNo)) {
            $returnData = $this->payment->refund->queryByOutRefundNumber($outRefundNo);
        } else if (!empty($refundNo)) {
            $returnData = $this->payment->refund->queryByRefundId($refundNo);
        } else if (!empty($outTradeNo)) {
            $returnData = $this->payment->refund->queryByOutTradeNumber($outTradeNo);
        } else if (!empty($transactionId)) {
            $returnData = $this->payment->refund->queryByTransactionId($transactionId);
        }

        if (!empty($returnData)) {
            return $this->getReturnData($returnData);
        }

        throw new \Exception('参数 out_refund_no、refund_no、out_trade_no、transaction_id 不能同时为空');
    }

    /**
     * 获取返回数据
     * @param $resultData
     * @return array
     */
    private function getReturnData($resultData)
    {
        if (
            $resultData['return_code'] == 'SUCCESS' &&
            $resultData['result_code'] == 'SUCCESS'
        ) {
            return $resultData;
        }

        $errMessage = $resultData['err_code_des'] ?? ($resultData['return_msg'] ?? '未知错误');

        throw new \Exception($errMessage);
    }
}