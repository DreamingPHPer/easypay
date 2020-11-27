<?php
namespace Dreamboy\Easypay\Mi\Kernel;

/**
 * 签名
 */
class Signature 
{
    
    /**
     * 生成签名
     * @param array $data 回调数据
     * @param string $key 密钥
     */
    public static function generate(array $data, string $key) 
    {
        // 过滤不需要参与签名的字段
        $data = array_filter($data, function($value, $key) {
            if (!in_array($key, ['signature']) && !empty($value)) {
                return true;
            }
        }, ARRAY_FILTER_USE_BOTH);

        // 排序
        ksort($data);

        // 生成签名字符串
        $string = urldecode(http_build_query($data));

        return hash_hmac('sha1', $string, $key);
    }

    /**
     * 生成签名
     * @param array $data 回调数据
     * @param string $key 密钥
     */
    public static function check($data, $key) 
    {
        $sign = $data['signature'] ?? '';
        return self::generate($data, $key) == $sign;
    }
}