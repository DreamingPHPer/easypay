<?php
namespace Dreamboy\Easypay\Kernel;

/**
 * 签名
 */
class Params {
    /**
     * 判断指定键名在指定数组中是否存在
     * @param array $data
     * @param array $keys
     */
    public static function contains($data, $keys) 
    {
        array_walk($keys, function($value, $index) use ($data) {
            if (!isset($data[$value])) {
                throw new \Exception('参数' . $value . '不能为空');
            }
        });
    }
}