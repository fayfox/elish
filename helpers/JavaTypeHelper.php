<?php

namespace elish\helpers;

/**
 * java类型
 */
class JavaTypeHelper
{
    /**
     * 根据三方文档给出的类型，获取java类型
     * @param string $type 文档中的类型
     * @return string
     */
    public static function getTypeByDoc(string $type): string
    {
        switch (strtolower($type)) {
            case 'int':
            case 'integer':
                return 'java.lang.Integer';
            case 'long':
                return 'java.lang.Long';
            case 'float':
                return 'java.lang.Float';
            case 'double':
                return 'java.lang.Double';
            case 'boolean':
            case 'bool':
                return 'java.lang.Boolean';
            case 'date':
            case 'datetime':
                $dateClass = $GLOBALS['config']['dateClass'] ?? 'localdatetime';
                if ($dateClass == 'localdatetime') {
                    return 'java.time.LocalDateTime';
                } elseif ($dateClass == 'date') {
                    return 'java.util.Date';
                }

                return 'java.lang.String';
            default:
                return 'java.lang.String';
        }
    }

    public static function getKotlinByDoc(string $type): string
    {
        switch (strtolower($type)) {
            case 'int':
            case 'integer':
                return 'Int';
            case 'long':
                return 'Long';
            case 'float':
                return 'Float';
            case 'double':
                return 'Double';
            case 'boolean':
            case 'bool':
                return 'Boolean';
            case 'date':
            case 'datetime':
                $dateClass = $GLOBALS['config']['dateClass'] ?? 'localdatetime';
                if ($dateClass == 'localdatetime') {
                    return 'java.time.LocalDateTime';
                } elseif ($dateClass == 'date') {
                    return 'java.util.Date';
                }

                return 'String';
            default:
                return 'String';
        }
    }

    /**
     * 将文本识别为bool类型
     * @param $value
     * @return bool
     */
    public static function boolval($value): bool
    {
        $trueTexts = ['true', '1', '是', 'yes', 'y', 'on', 'ok'];
        foreach ($trueTexts as $trueText) {
            if (mb_substr(strtolower($value), 0, mb_strlen($trueText, 'UTF-8'), 'UTF-8') == $trueText) {
                return true;
            }
        }

        return false;
    }

    public static function getSimpleClassName($fullClassName)
    {
        $pos = strrpos($fullClassName, '.');
        if ($pos === false) {
            return $fullClassName;
        }

        return substr($fullClassName, $pos + 1);
    }

    /**
     * 是否是日期类型
     * @param $javaType
     * @return bool
     */
    public static function isDateType($javaType): bool
    {
        return in_array($javaType, [
            'java.time.LocalDate',
            'java.util.Date',
        ]);
    }

    /**
     * 是否是日期时间类型
     * @param $javaType
     * @return bool
     */
    public static function isDatetimeType($javaType): bool
    {
        return $javaType == 'java.time.LocalDateTime';
    }
}