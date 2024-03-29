<?php

namespace elish\core;

use elish\R;
use ErrorException;
use Throwable;

class ErrorHandler
{

    /**
     * 接管PHP自带报错
     */
    public function register()
    {
        ini_set('display_errors', false);
        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'));
        register_shutdown_function(array($this, 'handleFatalError'));
    }

    /**
     * 处理php报错
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     */
    public function handleError(int $code, string $message, string $file, int $line)
    {
        if (error_reporting() == 0) {
            //例如@屏蔽报错的时候，error_reporting()会返回0
            return;
        }

        //这里都是些notice之类的报错，直接输出
        if (php_sapi_name() == 'cli' || defined('STDIN')) {
            echo "\r\n" . self::getErrorLevel($code) . ": {$message} in {$file} on line {$line}";
        } else {
            echo '<br><b>', self::getErrorLevel($code),
            '</b>: ',
            $message,
            ' in ',
            '<b>',
            $file,
            '</b> on line <b>',
            $line,
            '</b><br>';
        }
    }

    /**
     * 获取错误级别描述
     * @param int|string $code
     * @return string
     */
    public static function getErrorLevel($code): string
    {
        $levels = [
            E_ERROR => 'PHP Fatal Error',
            E_PARSE => 'PHP Parse Error',
            E_CORE_ERROR => 'PHP Core Error',
            E_COMPILE_ERROR => 'PHP Compile Error',
            E_USER_ERROR => 'PHP User Error',
            E_WARNING => 'PHP Warning',
            E_CORE_WARNING => 'PHP Core Warning',
            E_COMPILE_WARNING => 'PHP Compile Warning',
            E_USER_WARNING => 'PHP User Warning',
            E_STRICT => 'PHP Strict Warning',
            E_NOTICE => 'PHP Notice',
            E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
            E_DEPRECATED => 'PHP Deprecated Warning',
        ];

        return $levels[$code] ?? 'Error';
    }

    /**
     * 处理致命错误
     */
    public function handleFatalError()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
            //致命错误，当成异常处理
            $exception = new ErrorException($error['message'], $error['type'], 1, $error['file'], $error['line']);
            $this->handleException($exception);
        }
    }

    /**
     * 处理未捕获的异常
     * @param Throwable $exception
     */
    public function handleException(Throwable $exception)
    {
        if (Request::isAjax()) {
            R::error($exception->getMessage());
        } else {
            if (php_sapi_name() == 'cli' || defined('STDIN')) {
                Logger::get()->error("全局异常: {$exception}");
            } else {
                echo render('error/debug', [
                    'exception' => $exception,
                ]);
            }
            die;
        }
    }

}
