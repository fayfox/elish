<?php

namespace elish\helpers;

class ArrayHelper
{
    /**
     * 在$array数组中，是否包含至少一个$needles中给定的
     * @param array $needles
     * @param array $array
     * @return bool
     */
    public static function hasAny(array $needles, array $array): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $array)) {
                return true;
            }
        }

        return false;
    }
}