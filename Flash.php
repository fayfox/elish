<?php

namespace elish;

class Flash
{
    /**
     * 通过flash设置一条即显消息
     * @param string $message
     * @param string $status
     */
    public static function set(string $message, $status = 'error')
    {
        $_SESSION['flash'][$status][] = $message;
    }

    /**
     * 错误消息
     * @param string $message
     */
    public static function error(string $message) {
        self::set($message);
    }

    /**
     * 普通消息
     * @param string $message
     */
    public static function info(string $message) {
        self::set($message, 'info');
    }

    /**
     * 成功信息
     * @param string $message
     */
    public static function success(string $message) {
        self::set($message, 'success');
    }

    /**
     * 获取flash中的即显消息
     * @return array
     */
    public static function get(): array
    {
        $notification = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return $notification;
    }
}
