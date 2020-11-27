<?php
namespace Dreamboy\Easypay\Vivo;

use Dreamboy\Easypay\Kernel\Params;
use Dreamboy\Easypay\Vivo\Kernel\Signature;
use GuzzleHttp\Client;

/**
 * Vivo支付
 */
class Payment
{
    /**
     * 订单查询接口
     */
    protected $queryUrl = "https://pay.vivo.com.cn/vcoin/queryv2";

    /**
     * 支付配置
     */
    protected $config = [];

    /**
     * Payment constructor.
     * @param array $config 支付配置
     */
    public function __construct($config)
    {
        // 检查配置字段
        Params::contains($config, [
            'appid', 
            'appkey'
        ]);

        $this->config = $config;
    }

    /**
     * 预支付
     * @param array $data 支付参数
     * @param \Closure $closure 处理结果回调函数
     */
    public function prepay(array $data, \Closure $closure) 
    {
        try {
            // 检查支付参数
            Params::contains($data, [
                'cpOrderNumber', 
                'productName', 
                'productDesc', 
                'orderAmount', 
                'extuid', 
                'notifyUrl'
            ]);

            $data['appId'] = $this->config['appid'];
            $data['vivoSignature'] = Signature::generate($data, $this->config['appkey']);

            $closure(true, $data);
        } catch (\Exception $e) {
            $closure(false, $e->getMessage());
        }
    }

    /**
     * 订单查询
     * @param string $cpId
     * @param string $cpOrderNumber
     * @param string $orderNumber
     * @param string $orderAmount
     * @param \Closure $closure 处理结果回调函数
     */
    public function query(string $cpId, string $cpOrderNumber, string $orderNumber, int $orderAmount, \Closure $closure) 
    {
        try {
            $data = [
                'version' => '1.0.0',
                'signMethod' => 'MD5',
                'appId' => $this->config['appid'],
                'cpId' => $cpId,
                'cpOrderNumber' => $cpOrderNumber,
                'orderNumber' => $orderNumber,
                'orderAmount' => $orderAmount
            ];
    
            $data['signature'] = Signature::generate($data, $this->config['appkey']);
    
            $client = new Client();
            $result = $client->request('POST', $this->queryUrl, [
                'form_params' => $data
            ])->getBody()->getContents();
            $result = json_decode($result, true);
    
            if (empty($result) || !is_array($result)) {
                throw new \Exception('Vivo接口异常');
            }

            if (!Signature::check($result, $this->config['appkey'])) {
                throw new \Exception('验签失败');
            }

            if ($data['respCode'] != '200' || $data['tradeStatus'] != '0000') {
                throw new \Exception('订单支付失败');
            }
    
            $closure(true, $result);
        } catch (\Exception $e) {
            $closure(false, $e->getMessage());
        }
    }

    /**
     * 支付回调
     * @param array $data 支付回调参数
     * @param \Closure $closure 处理结果回调函数
     */
    public function notify(array $data, \Closure $closure) 
    {
        try {
            if (!Signature::check($data, $this->config['appkey'])) {
                throw new \Exception('验签失败');
            }

            if ($data['respCode'] != '200' || $data['tradeStatus'] != '0000') {
                throw new \Exception('订单支付失败');
            }

            return $closure(true, $data);
        } catch (\Exception $e) {
            return $closure(false, $e->getMessage());
        }
    }
}