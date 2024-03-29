<?php

namespace elish\helpers;


class HttpHelper
{

    public static function get($uri, $params = [])
    {
        return self::exec('GET', $uri, $params);
    }

    public static function post($uri, $body, $params = [])
    {
        return self::exec('POST', $uri, $params, $body);
    }

    public static function exec($method, $url, $params = [], $body = null, $headers = [])
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
            CURLOPT_MAXREDIRS => 30,
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
        curl_close($curl);

        if ($httpCode == 0 && !$content) {
            throw new \RuntimeException("HTTP请求失败");
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