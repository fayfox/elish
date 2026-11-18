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
        self::json([
            'code' => 0,
            'msg' => $msg
        ]);
    }

    /**
     * 返回成功数据
     * @param $data
     */
    public static function data($data)
    {
        self::json([
            'code' => 0,
            'data' => $data
        ]);
    }

    /**
     * 返回错误消息
     * @param string $msg
     * @param int $code 状态码
     */
    public static function error(string $msg, $code = 10000)
    {
        self::json([
            'code' => $code,
            'msg' => $msg
        ]);
    }

    /**
     * 输出 JSON。nginx 下发了 X-Request-Id 时，追加 traceId
     * @param array $payload
     */
    private static function json(array $payload)
    {
        header('Content-Type: application/json');
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        if ($requestId !== '') {
            $payload['traceId'] = $requestId;
        }
        echo json_encode($payload);
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
