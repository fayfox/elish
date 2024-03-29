<?php

namespace elish\core;

class Loader
{
    private static array $instances = array();

    /**
     * 获取一个单例（Model和Service事实上最终都是在调用此方法获取单例）
     * @param string $className
     * @return mixed
     */
    public static function singleton(string $className = __CLASS__)
    {
        return self::$instances[$className] ?? (self::$instances[$className] = new $className());
    }

}
