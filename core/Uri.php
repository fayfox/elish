<?php

namespace elish\core;

/**
 * 对url进行路由解析
 * @author fayfox
 *
 */
class Uri
{
    private static Uri $_instance;
    /**
     * 与$_SERVER['REQUEST_URI']相比，如果有二级或多级目录，会去掉目录
     */
    public string $action;

    public function __construct()
    {
        $this->_routing();

        self::$_instance = $this;
    }

    private function _routing()
    {
        if (php_sapi_name() == 'cli' || defined('STDIN')) {
            //命令行下执行
            $this->_parseCliArgs();
        } else {
            //http访问
            $this->_parseHttpArgs();
        }
    }

    /**
     * Cli方式运行
     * 命令格式如下：
     * php /var/www/html/faycms.com/www/public/index.php cms/tools/input/get key=value ajax=1;
     * php 文件路径 router 参数（多个参数空格隔开）
     * 注意：cli方式运行，router必须是4级
     */
    private function _parseCliArgs()
    {

    }

    private function _parseHttpArgs()
    {
        //若配置文件中未设置base_url，则系统猜测一个
        $base_url = '';

        if ($base_url) {
            //若未开启伪静态，需要加上index.php/
            if (defined('NO_REWRITE') && NO_REWRITE && substr($base_url, -10) != 'index.php/') {
                $base_url .= 'index.php/';
            }
        } else {
            //未设置$base_url，系统猜测一个
            $base_url = Request::getBaseUrl();
        }

        $base_url_params = parse_url($base_url);
        $base_url_path_length = strlen($base_url_params['path']);
        //过滤掉问号后面的部分
        if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
            $action = substr($_SERVER['REQUEST_URI'], $base_url_path_length, strpos($_SERVER['REQUEST_URI'], '?') - $base_url_path_length);
        } else {
            $action = substr($_SERVER['REQUEST_URI'], $base_url_path_length);
        }

        $this->action = $action;

    }

    public static function getAction()
    {
        return self::getInstance()->action;
    }

    public static function getInstance(): Uri
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}