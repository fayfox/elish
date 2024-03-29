<?php

namespace elish\helpers;

/**
 * 本地文件（代码文件）操作类
 */
class LocalFileHelper
{

    /**
     * 获取文件的一行或前后N行
     * @param string $file 文件路径
     * @param int $line 行号
     * @param int $adjacent 前后行数
     * @return string
     */
    public static function getFileLine(string $file, int $line, $adjacent = 0): string
    {
        if (!file_exists($file)) {
            return '';
        }
        $file = file($file);
        if ($adjacent) {
            $offset = $line - $adjacent - 1;//开始截取位置
            $offset < 0 && $offset = 0;
            $end = $line + $adjacent;//结束截取位置
            $file_line_count = count($file);//文件行数
            $end > $file_line_count && $end = $file_line_count;

            $fragment = array_slice($file, $offset, $end - $offset);
            return implode('', $fragment);
        } else {
            return $file[$line - 1];
        }
    }

    /**
     * 删除整个文件夹
     * 若第二个参数为true，则连同文件夹一同删除（包括自身）
     * @param string $path
     * @param bool|string $del_dir
     * @param int $level
     * @return bool
     */
    public static function deleteFiles($path, $del_dir = true, $level = 0){
        // Trim the trailing slash
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (!$current_dir = @opendir($path)){
            return false;
        }

        while(false !== ($filename = @readdir($current_dir))){
            echo $path.DIRECTORY_SEPARATOR.$filename, '<br>';
            if ($filename != "." and $filename != ".."){
                if (is_dir($path.DIRECTORY_SEPARATOR.$filename)){
                    // Ignore empty folders
                    if (substr($filename, 0, 1) != '.'){
                        self::deleteFiles($path.DIRECTORY_SEPARATOR.$filename, $del_dir, $level + 1);
                    }
                }else{
                    unlink($path.DIRECTORY_SEPARATOR.$filename);
                }
            }
        }
        @closedir($current_dir);

        if ($del_dir == true){
            return @rmdir($path);
        }
        return true;
    }
}