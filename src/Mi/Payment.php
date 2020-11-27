<?php
namespace Dreamboy\Easypay\Mi;

use Dreamboy\Easypay\Mi\Kernel\Signature;
use GuzzleHttp\Client;

/**
 * 小米支付
 */
class Payment 
{
    /**
     * 订单查询接口
     */
    protected $queryUrl = "http://mis.migc.xiaomi.com/api/biz/service/queryOrder.do";

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
        $this->config = $config;
    }

    /**
     * 支付通知
     * @param array $data 回调数据
     * @param \Closure $closure 闭包函数
     */
    public function notify(array $data, \Closure $closure) 
    {
        try {
            $checkResult = Signature::check($data, $this->config['appkey']);
            if ($checkResult !== true) {
                throw new \Exception('签名错误');
            }

            if ($data['orderStatus'] != 'TRADE_SUCCESS') {
                throw new \Exception('交易失败');
            }

            $closure(true, $data);
        } catch (\Exception $e) {
            $closure(false, $e->getMessage());
        }
    }

    /**
     * 订单查询
     */
    public function query($cpOrderId, $uid, \Closure $closure) 
    {
        try {
            $data = [
                'appId' => $this->config['appid'] ?? '',
                'cpOrderId' => $cpOrderId,
                'uid' => $uid
            ];
    
            $data['signature'] = Signature::generate($data, $this->config['appkey']);
    
            $client = new Client();
            $result = $client->request('GET', $this->queryUrl, [
                'form_params' => $data
            ])->getBody()->getContents();
            $result = json_decode($result, true);

            if (empty($result) || !is_array($result)) {
                throw new \Exception('小米接口异常');
            }

            if (!Signature::check($result, $this->config['appkey'])) {
                throw new \Exception('验签失败');
            }

            $closure(true, $result);
        } catch (\Exception $e) {
            $closure(false, $e->getMessage());
        }
    }
}