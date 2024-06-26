<?php

namespace elish\helpers;

/**
 * 时间相关帮助方法
 */
class DateHelper
{
    /**
     * 返回明天零点的时间戳
     */
    public static function tomorrow()
    {
        return mktime(0, 0, 0, date('m', time()), date('d', time()) + 1, date('Y', time()));
    }

    /**
     * 返回n天后零点的时间戳
     * @param int $n 天数
     * @return int
     */
    public static function daysLater($n)
    {
        return mktime(0, 0, 0, date('m', time()), date('d', time()) + $n, date('Y', time()));
    }

    /**
     * 返回相n天前零点的时间戳
     * @param int $n 天数
     * @return int
     */
    public static function daysbefore($n)
    {
        return mktime(0, 0, 0, date('m', time()), date('d', time()) - $n, date('Y', time()));
    }

    /**
     * 返回本月第一天的零点和最后一天的23点59分59秒的时间戳
     */
    public static function thisMonth()
    {
        $result['first_day'] = mktime(0, 0, 0, date('m', time()), 1, date('y', time()));
        $result['last_day'] = mktime(23, 59, 59, date('m', time()) + 1, 0, date('y', time()));
        return $result;
    }

    /**
     * 返回指定月第一天的零点和最后一天的23点59分59秒时间戳
     * 默认为今年
     * @param int $month 月份（1-12）
     * @param int $year 年份，若为null，视为今年（默认为null）
     * @return array
     */
    public static function month($month, $year = null)
    {
        $year || $year = date('y', time());
        return array(
            'first_day' => mktime(0, 0, 0, $month, 1, $year),
            'last_day' => mktime(23, 59, 59, $month + 1, 0, $year),
        );
    }

    /**
     * 判断是否是本月
     * @param int $timestamp Unix时间戳
     * @return bool
     */
    public static function isThisMonth($timestamp)
    {
        return date('mY', $timestamp) == date('mY', time());
    }

    /**
     * 判断是否是昨天
     * @param int $timestamp Unix时间戳
     * @return bool
     */
    public static function isYesterday($timestamp)
    {
        return date('Ymd', $timestamp) == date('Ymd', self::yesterday());
    }

    /**
     * 返回昨天零点的时间戳
     */
    public static function yesterday()
    {
        return mktime(0, 0, 0, date('m', time()), date('d', time()) - 1, date('Y', time()));
    }

    /**
     * 判断是否是本周
     * @param int $timestamp Unix时间戳
     * @return bool
     */
    public static function isThisWeek($timestamp)
    {
        $week = self::thisWeek();
        return ($timestamp > $week['first_day'] && $timestamp < $week['last_day']);
    }

    /**
     * 返回本周第一天的零点和最后一天的23点59分59秒的时间戳
     */
    public static function thisWeek()
    {
        $result['first_day'] = mktime(0, 0, 0, date('m', time()), date('d', time()) - date('N', time()) + 1, date('Y', time()));
        $result['last_day'] = mktime(23, 59, 59, date('m', time()), date('d', time()) - date('N', time()) + 7, date('Y', time()));
        return $result;
    }

    /**
     * 根据main.php文件中设置的时间格式返回时间字符串
     * @param int $timestamp Unix时间戳
     * @return string
     */
    public static function format($timestamp)
    {
        if ($timestamp != 0) {
            if ($timestamp > 2147483647) {
                $timestamp = $timestamp / 1000;
            }
            return date('Y-m-d H:i:s', $timestamp);
        } else {
            return null;
        }

    }

    /**
     * 返回一个简单美化过的时间，例如：“刚刚”，“10秒前”，“昨天 17:43”，“3天前”等。
     * @param int $timestamp 时间戳，若不指定或指定为等价于0的值，则返回null
     * @return null|string
     */
    public static function niceShort($timestamp = null)
    {
        if ($timestamp == 0) {
            return null;
        }

        $dv = time() - $timestamp;
        if ($dv < 0) {
            //当前时间之后
            $dv = -$dv;
            if ($dv < 60) {
                //一分钟内
                return $dv . '秒后';
            } else if ($dv < 3600) {
                //一小时内
                return floor($dv / 60) . '分钟后';
            } else if (self::isToday($timestamp)) {
                //今天内
                return floor($dv / 3600) . '小时后';
            } else if ($dv < (time() - self::today()) + 86400 * 6) {
                //7天内
                return ceil(($dv - (time() - self::today())) / 86400) . '天后';
            } else if (self::isThisYear($timestamp)) {
                //今年
                return date('n月j日', $timestamp);
            } else {
                return date('y年n月j日', $timestamp);
            }
        } else {
            //当前时间之前
            if ($dv < 3) {
                return '刚刚';
            } else if ($dv < 60) {
                //一分钟内
                return $dv . '秒前';
            } else if ($dv < 3600) {
                //一小时内
                return floor($dv / 60) . '分钟前';
            } else if (self::isToday($timestamp)) {
                //今天内
                return floor($dv / 3600) . '小时前';
            } else if ($dv < (time() - self::today()) + 86400 * 6) {
                //7天内
                return ceil(($dv - (time() - self::today())) / 86400) . '天前';
            } else if (self::isThisYear($timestamp)) {
                //今年
                return date('n月j日', $timestamp);
            } else {
                return date('y年n月', $timestamp);
            }
        }
    }

    /**
     * 判断是否是今天
     * @param int $timestamp Unix时间戳
     * @return bool
     */
    public static function isToday($timestamp)
    {
        return date('Ymd', $timestamp) == date('Ymd', time());
    }

    /**
     * 返回今天零点的时间戳
     */
    public static function today()
    {
        return mktime(0, 0, 0, date('m', time()), date('d', time()), date('Y', time()));
    }

    /**
     * 判断是否是今年
     * @param int $timestamp Unix时间戳
     * @return bool
     */
    public static function isThisYear($timestamp)
    {
        return date('Y', $timestamp) == date('Y', time());
    }

    /**
     * 返回两个时间戳之间的时差，例如：“1分30秒”，“1小时20分6秒”，“1天3小时16分32秒”。
     * @param int $start_time 开始时间戳
     * @param int $end_time 结束时间戳
     * @return string
     */
    public static function diff($start_time, $end_time)
    {
        $dv = $end_time - $start_time;

        if ($dv < 60) {
            return $dv . '秒';
        } else if ($dv < 3600) {
            return floor($dv / 60) . '分' . ($dv % 60) . '秒';
        } else if ($dv < 86400) {
            $remainder = $dv % 3600;
            return floor($dv / 3600) . '小时' . floor($remainder / 60) . '分' . ($remainder % 60) . '秒';
        } else {
            $date_remainder = $dv % 86400;
            $minute_remainder = $date_remainder % 3600;
            return floor($dv / 86400) . '天' . floor($date_remainder / 3600) . '小时' . floor($minute_remainder / 60) . '分' . ($minute_remainder % 60) . '秒';
        }
    }

    /**
     * 当输入为空字符串时，返回空字符串，其它返回时间戳
     * @param int $timestamp
     * @return int
     */
    public static function strtotime($timestamp)
    {
        if ($timestamp === '') {
            return '';
        } else {
            return strtotime($timestamp);
        }
    }
}