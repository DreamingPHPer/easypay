<?php
/**
 * 公共方法
 */

if (!function_exists('transferKeyToOurFormat')) {
    /**
     * 根据自定义规则转换原始字段
     * @param $data
     * @param array $format
     * @return array
     */
    function transferKeyToOurFormat($data, $format = [])
    {
        $formatData = [];
        array_walk($data, function ($value, $key) use (&$formatData, $format) {
            if (isset($format[$key])) {
                $formatData[$format[$key]] = $value;
            } else {
                $formatData[$key] = $value;
            }
        });

        return $formatData;
    }
}