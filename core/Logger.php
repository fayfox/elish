<?php

namespace elish\core;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class Logger
{
    private static array $loggers = [];

    /**
     * 获取一个Monolog Logger实例
     * @param string $name
     * @param bool $file_handler 若为true，则会默认绑定一个以$name+日期为文件名的handler。仅在首次调用时有效
     * @return \Monolog\Logger
     */
    public static function get(string $name = 'default', bool $file_handler = true)
    {
        if (empty(self::$loggers[$name])) {
            $logger = new \Monolog\Logger($name);
            if ($file_handler) {
                //初始化一个记录到文件的handler
                $fileHandler = new StreamHandler(BASEPATH . "runtimes/logs/{$name}-" . date('Y-m-d') . '.log');
                //默认行尾会有2个空数组JSON，这里指定一下若为空不写进日志
                $fileHandler->setFormatter(new LineFormatter(null, null, true, true));
                $logger->pushHandler($fileHandler);
            }

            if (php_sapi_name() == 'cli' || defined('STDIN')) {
                // 创建一个输出到命令行的 handler
                $stdoutHandler = new StreamHandler('php://stdout', \Monolog\Logger::DEBUG);
                //默认行尾会有2个空数组JSON，这里指定一下若为空不写进日志
                $stdoutHandler->setFormatter(new LineFormatter(null, null, true, true));
                $logger->pushHandler($stdoutHandler);
            }

            self::$loggers[$name] = $logger;
        }

        return self::$loggers[$name];
    }

}
