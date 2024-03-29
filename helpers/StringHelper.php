<?php

namespace elish\helpers;

class StringHelper
{

    /**
     * 判断字符串以某个子串开始
     * @param string $str 字符串
     * @param string ...$needles string 子字符串
     * @return bool
     */
    public static function startWith(string $str, string ...$needles): bool
    {
        foreach ($needles as $needle) {
            if (strpos($str, $needle) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断字符串以某个子串开始
     * @param string $str 字符串
     * @param string ...$needles string 子字符串
     * @return bool
     */
    public static function endWith(string $str, string ...$needles): bool
    {
        foreach ($needles as $needle) {
            if (substr($str, -strlen($needle)) === $needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * 下划线分割转大小写分割<br>
     * 若$ucfirst为false，首字母小写，默认为所有分词首字母大写
     * @param string $str
     * @param bool $ucfirst
     * @return string
     */
    public static function underscore2case(string $str, bool $ucfirst = true): string
    {
        $explodes = explode('_', $str);
        foreach ($explodes as $key => &$e) {
            if (!$key && $ucfirst) {
                $e = ucfirst($e);
            } else if ($key) {
                $e = ucfirst($e);
            }
        }
        return implode('', $explodes);
    }

    /**
     * 判断字符串是否包含某个子串
     * @param string $str
     * @param string ...$needles
     * @return bool
     */
    public static function contains(string $str, string ...$needles): bool
    {
        foreach ($needles as $needle) {
            if (strpos($str, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

}