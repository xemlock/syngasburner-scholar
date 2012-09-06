<?php

/**
 * @param string $date
 *     data w formacie YYYY, YYYY-MM lub YYYY-MM-DD (ISO 8601)
 */
function scholar_parse_date($date) // {{{
{
    if (preg_match('/^\s*(?P<year>\d+)(?>-(?P<month>\d{1,2}))?(?>-(?P<day>\d{1,2}))?\s*$/', $date, $match)) {
        $y = intval($match['year']);
        $m = isset($match['month']) ? intval($match['month']) : null;
        $d = isset($match['day']) ? intval($match['day']) : null;

        // Jezeli $m jest nullem, tzn. ze $d tez jest nullem, wiec
        // rok jako liczba calkowita jest poprawny.
        // Jezeli $m nie jest nullem, musi byc z przedzialu 1..12.
        // Jezeli $d jest nullem, nic wiecej nie trzeba sprawdzac,
        // jezeli nie jest, trzeba sprawdzic pelna date.
        $valid = ((null === $m) || (1 <= $m && $m <= 12))
                 && ((null === $d) || checkdate($m, $d, $y));

        if ($valid) {
            return array(
                'year'  => $y,
                'month' => $m,
                'day'   => $d,
                'iso'   => null === $m
                    ? sprintf('%04d', $y)
                    : (null === $d
                        ? sprintf('%04d-%02d', $y, $m)
                        : sprintf('%04d-%02d-%02d', $y, $m, $d)
                    ),
            );
        }
    }

    return false;
} // }}}

/**
 * Niepodane części daty będą miały wartość NULL.
 * @param string $time
 *     czas w formacie HH lub HH:MM lub HH:MM:SS
 */
function scholar_parse_time($time) // {{{
{
    if (preg_match('/^\s*(?P<hour>\d+)(?>:(?P<minute>\d+))?(?>:(?P<second>\d+(\.\d*)?))?\s*$/', $time, $match)) {
        $h = intval($match['hour']);
        $m = isset($match['minute']) ? intval($match['minute']) : null;
        $s = isset($match['second']) ? floatval($match['second']) : null;

        if ($h < 24 && (null === $m || $m < 60) && (null === $s || $s < 60)) {
            if (null === $s) {
                $is = null;
                $ms = null;
            } else {
                $is = intval($s);
                $ms = round(($s - $is) * 1000);
            }

            return array(
                'hour'   => $h,
                'minute' => $m,
                'second' => $is,
                'millis' => $ms,
                'iso'    => null === $m
                    ? sprintf('%02d', $h)
                    : (null === $s
                        ? sprintf('%02d:%02d', $h, $m)
                        : sprintf('%02d:%02d:%02d', $h, $m, $s)
                    ),
            );
        }
    }

    return false;
} // }}}

// vim: fdm=marker
