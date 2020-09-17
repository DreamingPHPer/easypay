<?php
namespace Dreamboy\Easypay;

use EasyWeChat\Kernel\Support\Str;

class Factory
{
    /**
     * @param $name
     * @param array $config
     * @return mixed
     */
    public static function make($name, array $config)
    {
        $namespace = Str::studly($name);
        $application = "\\Dreamboy\\Easypay\\{$namespace}\\Payment";

        return new $application($config);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::make($name, ...$arguments);
    }
}