<?php

namespace elish\helpers;

use elish\core\Request;

class UrlHelper
{

    /**
     * 构建站内url
     * @param string $router 若为null，则返回首页，否则根据$base_url构建url
     * @param array $params 链接参数
     * @param string $anchor 锚点
     * @return mixed|string
     */
    public static function create($router = null, $params = array(), $anchor = ''): string
    {
        $base_url = self::getBaseUrl();
        if ($router === null) {
            return $base_url;
        } else {
            if ($anchor && substr($anchor, 0, 1) != '#') {
                //若指定锚点，且非井号开头，则加上井号
                $anchor = '#' . $anchor;
            }

            // 去除前导斜杠
            if (StringHelper::startWith($router, '/')) {
                $router = substr($router, 1);
            }

            if ($params && $query_string = http_build_query($params)) {
                return $base_url . $router . '?' . $query_string . $anchor;
            } else {
                return $base_url . $router . $anchor;
            }
        }
    }

    /**
     * 获取根目录
     * @return string
     */
    public static function getBaseUrl()
    {
        static $baseUrl;

        if (!$baseUrl) {
            if (!empty($_ENV['BASE_URL'])) {
                $baseUrl = $_ENV['BASE_URL'];
            } else {
                $baseUrl = Request::getBaseUrl();
            }

        }

        return $baseUrl;
    }

    /**
     * 返回public/assets/下的文件路径（第三方jquery类库等）
     * 主要是考虑到以后如果要做静态资源分离，只要改这个函数就好了
     * @param string $uri
     * @return string
     */
    public static function assets($uri)
    {
        return self::getBaseUrl() . $uri;
    }

}