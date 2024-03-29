<?php

namespace elish\command;

use elish\core\Logger;
use elish\helpers\StringHelper;
use Symfony\Component\Console\Application;

/**
 * 注册command<p>
 * 约定command都在command目录下，且命名空间为<code>app\command</code>打头
 */
class Registration
{
    public static function register(Application $application, $subDir = '')
    {
        $baseDir = BASEPATH . '/command/';
        $files = scandir($baseDir . $subDir);
        foreach ($files as $file) {
            $newSubdir = ($subDir ? $subDir . DIRECTORY_SEPARATOR . $file : $file);
            if (StringHelper::endWith($file, '.php')) {
                if ($subDir) {
                    $class = 'app\\command\\' . $subDir . '\\' . str_replace('.php', '', $file);
                } else {
                    $class = 'app\\command\\' . str_replace('.php', '', $file);
                }
                $class = str_replace('/', '\\', $class);
                try {
                    $application->add(new $class());
                } catch (\Exception $e) {
                    Logger::get()->debug("注册command失败: " . $class . " " . $e->getMessage());
                }
            } else if (is_dir($baseDir . $newSubdir) && $file != '.' && $file != '..') {
                self::register($application, $newSubdir);
            }
        }
    }
}