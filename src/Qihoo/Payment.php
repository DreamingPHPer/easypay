<?php
namespace Dreamboy\Easypay\Qihoo;

use Dreamboy\Easypay\Kernel\Params;
use GuzzleHttp\Client;
use Dreamboy\Easypay\Qihoo\Kernel\Signature;

/**
 * 360支付
 */
class Payment
{
    /**
     * 360支付接口
     * @var string
     */
    protected $payUrl = 'https://mgame.360.cn/srvorder/get_token.json';

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
            'app_key', 
            'app_secret'
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
                'product_id',
                'product_name',
                'amount',
                'app_uid',
                'app_uname',
                'user_id',
                'app_order_id'
            ]);

            $data['app_key'] = $this->config['app_key'];
            $data['sign_type'] = 'md5';
            $data['sign'] = Signature::generate($data, $this->config['app_secret']);

            $client = new Client();
            $result = $client->request('POST', $this->payUrl, [
                'form_params' => $data
            ])->getBody()->getContents();
            $result = json_decode($result, true);
            
            if (empty($result) || !is_array($result)) {
                throw new \Exception('360接口异常');
            }

            if (isset($result['error_code'])) {
                throw new \Exception($result['error']);
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
            if (!Signature::check($data, $this->config['app_secret'])) {
                throw new \Exception('验签失败');
            }

            if ($data['gateway_flag'] != 'success') {
                throw new \Exception('交易失败');
            }

            return $closure(true, $data);
        } catch (\Exception $e) {
            return $closure(false, $e->getMessage());
        }
    }
}
