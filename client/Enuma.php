<?php

namespace elish\client;

use elish\helpers\StringHelper;

/**
 * 请求服务端
 */
class Enuma
{
    private static ?Enuma $_instance;
    private string $serverUrl;

    private function __construct()
    {
    }

    public static function getInstance(): Enuma
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->serverUrl = $_ENV['SERVER_URL'];
        }
        return self::$_instance;
    }

    public function exec($method, $uri, $params = [], $body = null, $headers = [])
    {
        $curl = curl_init();
        if ($params) {
            if (strpos($uri, '?') !== false) {
                $url = $this->serverUrl . $uri . '&' . http_build_query($params);
            } else {
                $url = $this->serverUrl . $uri . '?' . http_build_query($params);
            }
        } else {
            $url = $this->serverUrl . $uri;
        }

        if (!$this->hasHeader($headers, 'Content-Type')) {
            $headers[] = 'Content-Type: application/json';
        }

        if (!$this->hasHeader($headers, 'Accept')) {
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
        curl_close($curl);

        if ($httpCode == 0 && !$content) {
            throw new CallServerException("内部请求异常: 请求server失败");
        }

        $jsonData = json_decode($content, true);
        if ($httpCode >= 400) {
            throw new CallServerException("内部请求异常[{$httpCode}]: {$content}", $httpCode, $jsonData ?: $content);
        }

        if ($jsonData !== null) {
            return $jsonData;
        } else {
            // 非json格式，基本上就是直接返回了一个String的场景
            return $content;
        }
    }

    private function hasHeader($headers, $needle): bool
    {
        foreach ($headers as $header) {
            if (StringHelper::startWith(strtolower($header), $needle . ':')) {
                return true;
            }
        }

        return false;
    }

    public static function get($uri, $params = [])
    {
        return self::getInstance()->exec('GET', $uri, $params);
    }

    public static function post($uri, $body, $params = [])
    {
        return self::getInstance()->exec('POST', $uri, $params, $body);
    }

    public static function delete($uri, $params = [])
    {
        return self::getInstance()->exec('DELETE', $uri, $params);
    }

    public static function put($uri, $body, $params = [])
    {
        return self::getInstance()->exec('PUT', $uri, $params, $body);
    }

    private function __clone()
    {
    }
}
