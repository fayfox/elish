<?php

use elish\core\ErrorHandler;
use elish\core\Logger;

// 如果是在vendor目录下，定义BASEPATH
if (!defined('BASEPATH') && basename(dirname(__DIR__, 2)) == 'vendor') {
    define('BASEPATH', realpath(dirname(__DIR__, 3)) . DIRECTORY_SEPARATOR);
}

// 时区
if (!empty($_ENV['TZ'])) {
    date_default_timezone_set($_ENV['TZ']);
}

(new ErrorHandler())->register();

function pr($val)
{
    echo '<pre>';
    print_r($val);
    echo '</pre>';
}

/**
 * @param $var
 * @param int $depth 最大递归深度
 */
function dump($var, int $depth = 10)
{
    $output = '';
    dumpInternal($var, $depth, $output);
    echo '<pre>', $output, "\n</pre>";
}

function dumpInternal($var, $depth = 10, &$output = '', $level = 0)
{
    switch (gettype($var)) {
        case 'boolean':
            $output .= $var ? "<small>boolean</small> <font color=\"#75507b\">true</font>" : "<small>boolean</small> <font color=\"#75507b\">false</font>";
            break;
        case 'integer':
            $output .= "<small>int</small> <font color=\"#4e9a06\">{$var}</font>";
            break;
        case 'double':
            $output .= "<small>float</small> <font color=\"#f57900\">{$var}</font>";
            break;
        case 'string':
            $output .= "<small>string</small> <font color=\"#cc0000\">'" . htmlentities($var, ENT_QUOTES, 'UTF-8') . "'</font> <i>(length=" . mb_strlen($var, 'utf-8') . ")</i>";
            break;
        case 'resource':
            $output .= '{resource}';
            break;
        case 'NULL':
            $output .= "<font color=\"#3465a4\">null</font>";
            break;
        case 'unknown type':
            $output .= '{unknown}';
            break;
        case 'array':
            if ($level >= $depth) {
                $output .= "<b>array</b>\n" . str_repeat(' ', ($level + 1) * 4) . "<i><font>[...]</font></i>";
            } else if (empty($var)) {
                $output .= "<b>array</b>\n" . str_repeat(' ', ($level + 1) * 4) . "<i><font color=\"#888a85\">empty</font></i>";
            } else {
                $keys = array_keys($var);
                $spaces = str_repeat(' ', ($level + 1) * 4);
                $output .= "<b>array</b>";
                foreach ($keys as $key) {
                    $output .= "\n" . $spaces;
                    if (is_numeric($key)) {
                        $output .= $key;
                    } else {
                        $output .= "'{$key}'";
                    }
                    $output .= ' <font color="#888a85">=&gt;</font> ';
                    dumpInternal($var[$key], $depth, $output, $level + 1);
                }
            }
            break;
        case 'object':
        {
            $class_name = get_class($var);
            if ($level >= $depth) {
                $output .= "<b>object</b>(<i>" . $class_name . "</i>)\n" . str_repeat(' ', ($level + 1) * 4) . "<i><font>(...)</font></i>";
            } else {
                $output .= "<b>object</b>(<i>" . $class_name . "</i>)";
                $spaces = str_repeat(' ', ($level + 1) * 4);
                foreach ((array)$var as $key => $value) {
                    $key = trim($key);
                    $pre = substr($key, 0, strpos($key, "\0"));
                    if ($pre == $class_name) {
                        //private
                        $output .= "\n{$spaces}<i>private</i> '" . substr($key, strpos($key, "\0")) . "'";
                    } else if ($pre == '*') {
                        //protected
                        $output .= "\n{$spaces}<i>protected</i> '" . substr($key, 1) . "'";
                    } else {
                        //public
                        $output .= "\n{$spaces}<i>public</i> '{$key}'";
                    }
                    $output .= ' <font color=\"#888a85\">=&gt;</font> ';
                    dumpInternal($value, $depth, $output, $level + 1);
                }
            }
            break;
        }
    }
}

/**
 * 循环调用dump后die脚本
 */
function dd()
{
    if (php_sapi_name() == 'cli' || defined('STDIN')) {
        array_map(function ($x) {
            var_dump($x);
        }, func_get_args());
        die;
    } else {
        array_map(function ($x) {
            dump($x);
        }, func_get_args());
        die;
    }
}

/**
 * 渲染指定模板
 * @param string $__view_file__ web目录路径
 * @param array $__data__ 参数
 * @return string
 */
function render(string $__view_file__, array $__data__ = []): string
{
    ob_start();
    extract($__data__);
    if (file_exists(__DIR__ . '/web/' . $__view_file__ . '.php')) {
        require __DIR__ . '/web/' . $__view_file__ . '.php';
    } else if (file_exists(__DIR__ . '/web/' . $__view_file__ . '/index.php')) {
        require __DIR__ . '/web/' . $__view_file__ . '/index.php';
    } else {
        throw new RuntimeException("视图[{$__view_file__}]不存在");
    }

    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
