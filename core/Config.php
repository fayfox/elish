<?php

namespace elish\core;

class Config
{
    /**
     * 获取配置
     * @param string $name 配置文件名称
     * @return mixed
     */
    public static function getFile(string $name)
    {
        return require BASEPATH . "config/{$name}.php";
    }
}
