<?php

namespace elish;

use elish\helpers\StringHelper;
use elish\helpers\UrlHelper;

/**
 * Json返回
 */
class R
{
    /**
     * 返回成功消息
     * @param $msg
     */
    public static function msg($msg)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'code' => 0,
            'msg' => $msg
        ]);
        die;
    }

    /**
     * 返回成功数据
     * @param $data
     */
    public static function data($data)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'code' => 0,
            'data' => $data
        ]);
        die;
    }

    /**
     * 返回成功消息
     * @param string $msg
     * @param int $code 状态码
     */
    public static function error(string $msg, $code = 10000)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'code' => $code,
            'msg' => $msg
        ]);
        die;
    }

    public static function redirect(string $url) {
        if (is_array($url) || !StringHelper::startWith($url, 'http')) {
            $url = UrlHelper::create($url);
        }
        header("location: {$url}");
        die;
    }

    /**
     * 返回上一页
     */
    public static function goback(){
        if(isset($_SERVER['HTTP_REFERER'])){
            self::redirect($_SERVER['HTTP_REFERER']);
        }else{
            die('<script>history.go(-1);</script>');
        }
    }
}
