<?php

/**
 * @param string $date
 *     data w formacie YYYY, YYYY-MM lub YYYY-MM-DD (ISO 8601)
 * @return array
 *     tablica z kluczami year, month, day, iso, time. Pierwszy jest liczbą
 *     całkowitą, dwa następne liczbami całkowitymi albo nullami (jeżeli
 *     nie zostały podane), zaś iso jest reprezentacją tekstową podanej
 *     daty.
 */
function scholar_parse_date($date) // {{{
{
    if (preg_match('/^\s*(?>(?P<year>\d{4})(?>-(?P<month>\d{1,2})(?>-(?P<day>\d{1,2}))?)?)\s*$/', $date, $match)) {
        // rok musi skladac sie z czterech cyfr, wiecej cyfr nie miesci sie
        // w typie DATETIME w MySQL.
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
    if (preg_match('/^\s*(?>(?P<hour>\d+)(?>:(?P<minute>\d+)(?>:(?P<second>\d+)(?P<millis>\.\d*)?)?)?)\s*$/', $time, $match)) {
        $h = intval($match['hour']);
        $m = isset($match['minute']) ? intval($match['minute']) : null;
        $s = isset($match['second']) ? intval($match['second']) : null;
        $ms = isset($match['millis']) ? intval(round(floatval($match['millis']) * 1000)) : null;

        if ($h < 24 && (null === $m || $m < 60) && (null === $s || $s < 60)) {
            return array(
                'hour'   => $h,
                'minute' => $m,
                'second' => $s,
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

/**
 * Wartość true zwracana jest dla następujących ciągów znaków: '1', 'y',
 * 'yes', 't', 'true'. Wartości false odpowiadają '0', 'n', 'no', 'f',
 * 'false'. Dla wszystkie innych wartości zwracany jest null.
 *
 * @param string $value
 * @return null|bool
 */
function scholar_parse_bool($value) // {{{
{
    switch (strtolower(trim($value)))
    {
        case '1': case 'y': case 'yes': case 't': case 'true':
            return true;

        case '0': case 'n': case 'no':  case 'f': case 'false':
            return false;
    }

    return null;
} // }}}

// vim: fdm=marker
