<?php

namespace elish\helpers;

class DesHelper
{
    public static function unPkcsPadding($str)
    {
        $pad = ord($str[strlen($str) - 1]);
        if ($pad > strlen($str)) {
            return false;
        }
        return substr($str, 0, -1 * $pad);
    }

    public static function decrypt(string $content, string $key): string
    {
        return rtrim(self::unPkcsPadding(openssl_decrypt(base64_decode($content), 'DES-ECB', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING)));
    }

}
