<?php

/**
 * Sprawdza poprawność podanej wartości pod kątem bycia poprawną datą publikacji
 * naukowej. Najwcześniejszą poprawną datą jest dzień wydania pierwszego numeru
 * {@link http://en.wikipedia.org/wiki/Philosophical_Transactions_of_the_Royal_Society Philosophical Transactions of the Royal Society}
 * - 6 marca 1665. Najpóźniejszą jest aktualna data.
 *
 * @param string $value
 * @return bool
 */
function scholar_validate_publication_date($value) // {{{
{
    $date = scholar_parse_date($value);

    if ($date) {
        // dopelniamy wartosc do pelnej daty YYYY-MM-DD
        $time = $date['iso'] . substr('0001-01-01', strlen($date['iso']));

        // 6 marca 1665, data wydania pierwszego numeru
        // Philosophical Transactions of the Royal Society
        $min = '1665-03-06';
        $max = date('Y-m-d');

        // nie korzystamy z mktime, poniewaz nie potrafi on pod Windowsami
        // obsluzyc dat sprzed 1970-01-01, patrz:
        // http://php.net/manual/en/function.mktime.php
        if (strcmp($min, $time) <= 0 && strcmp($time, $max) <= 0) {
            return true;
        }
    }

    return false;
} // }}}

/**
 * Sprawdza poprawność wartości pod kątem bycia poprawnym absolutnym adresem
 * URL. Dopuszczalne są jedynie protokoły HTTP(s) i FTP(s).
 *
 * @return bool
 */
function scholar_validate_url($value) // {{{
{
    $scheme = '(ftp|http)s?:\/\/';
    $host = '[a-z0-9](\.?[a-z0-9\-]*[a-z0-9])*';
    $port = '(:\d+)?';
    $path = '(\/[^\s]*)*';

    return preg_match("/^$scheme$host$port$path$/i", $value);
} // }}}

// vim: fdm=marker
