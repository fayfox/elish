<?php

namespace elish\helpers;


use elish\core\Logger;

class HttpHelper
{
    /**
     * 发起GET请求
     *
     * @param string $uri
     * @param array $params
     * @return mixed
     */
    public static function get(string $uri, array $params = [])
    {
        return self::exec('GET', $uri, $params);
    }

    /**
     * 发起POST请求
     *
     * @param string $uri
     * @param mixed $body
     * @param array $params
     * @return mixed
     */
    public static function post(string $uri, $body, array $params = [])
    {
        return self::exec('POST', $uri, $params, $body);
    }

    /**
     * 发起HTTP请求
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param mixed $body
     * @param array $headers
     * @return mixed
     */
    public static function exec(string $method, string $url, array $params = [], $body = null, array $headers = [])
    {
        $curl = curl_init();
        if ($params) {
            if (strpos($url, '?') !== false) {
                $url .= ('&' . http_build_query($params));
            } else {
                $url .= ('?' . http_build_query($params));
            }
        }

        if (!self::hasHeader($headers, 'Content-Type')) {
            $headers[] = 'Content-Type: application/json';
        }

        if (!self::hasHeader($headers, 'Accept')) {
            $headers[] = 'Accept: application/json';
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($body) {
            $options[CURLOPT_POSTFIELDS] = is_string($body) ? $body : json_encode($body);
        }

        curl_setopt_array($curl, $options);

        $content = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        try {
            if ($httpCode == 0 && !$content) {
                Logger::get()->error("HTTP请求失败[{$httpCode}]: {$content}", [
                    'method' => $method,
                    'url' => $url,
                    'body' => $body
                ]);
                throw new \RuntimeException("HTTP请求失败: " . curl_error($curl));
            }
        } finally {
            curl_close($curl);
        }

        $jsonData = json_decode($content, true);
        if ($httpCode >= 400) {
            throw new \RuntimeException("HTTP请求失败[{$httpCode}]: {$content}");
        }

        if ($jsonData !== null) {
            return $jsonData;
        } else {
            // 非json格式，基本上就是直接返回了一个String的场景
            return $content;
        }
    }

    private static function hasHeader($headers, $needle): bool
    {
        foreach ($headers as $header) {
            if (StringHelper::startWith(strtolower($header), $needle)) {
                return true;
            }
        }

        return false;
    }
}