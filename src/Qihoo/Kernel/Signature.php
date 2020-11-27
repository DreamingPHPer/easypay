<?php
namespace Dreamboy\Easypay\Qihoo\Kernel;

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
         // 排序
         ksort($data);

         // 拼接字符串
         $string = implode('#', array_values($data)) . '#' . $key;
 
         // 返回加密字符串
         return md5($string);
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