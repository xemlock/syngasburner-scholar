<?php

/**
 * @param string $date
 *     data w formacie YYYY-MM-DD
 */
function scholar_parse_date($date)
{
    if (preg_match('/^\s*(\d+)-(\d{1,2})-(\d{1,2})\s*$/', $date, $match)) {
        list(, $y, $m, $d) = $match;
        if (checkdate(intval($m), intval($d), intval($y))) {
            return array(
                'year'  => $y,
                'month' => $m,
                'day'   => $d,
                'iso'   => sprintf('%04d-%02d-%02d', $y, $m, $d), // ISO 8601
            );
        }
    }

    return false;
}

/**
 * @param string $time
 *     czas w formacie HH:MM lub HH:MM:SS
 */
function scholar_parse_time($time)
{
    if (preg_match('/^\s*(\d+):(\d+)(:(\d+(\.\d+)?))?\s*$/', $time, $match)) {
        // a co jesli nie zmaczuje sekund???
        list(, $h, $m, , $s, ) = $match;
        if ($h < 24 && $m < 60 && $s < 60) {
            $is = intval($s);
            $ms = $s - $is;
            return array(
                'hour'   => $h,
                'minute' => $m,
                'second' => $is,
                'millisecond' => round($ms * 1000),
                'iso'    => sprintf('%02d:%02d:%02d', $h, $m, $is),
            );
        }
    }

    return false;
}

// vim: fdm=marker
