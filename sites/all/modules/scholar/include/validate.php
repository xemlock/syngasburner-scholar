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
 * Sprawdza poprawność podanej wartości pod kątem bycia poprawnym kolorem CSS.
 *
 * @return false|string
 */
function scholar_validate_color($color) { // {{{
    // trim white spaces (white spaces are ignored in CSS)
    $color = trim(strtolower($color));

    // CSS 2: extended color list
    $colors = array(
        'aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure',
        'beige', 'bisque', 'black', 'blanchedalmond', 'blue', 'blueviolet',
        'brown', 'burlywood', 'cadetblue', 'chartreuse', 'chocolate',
        'coral', 'cornflowerblue', 'cornsilk', 'crimson', 'cyan',
        'darkblue', 'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgreen',
        'darkgrey', 'darkkhaki', 'darkmagenta', 'darkolivegreen',
        'darkorange', 'darkorchid', 'darkred', 'darksalmon',
        'darkseagreen', 'darkslateblue', 'darkslategray', 'darkslategrey',
        'darkturquoise', 'darkviolet', 'deeppink', 'deepskyblue',
        'dimgray', 'dimgrey', 'dodgerblue', 'firebrick', 'floralwhite',
        'forestgreen', 'fuchsia', 'gainsboro', 'ghostwhite', 'gold',
        'goldenrod', 'gray', 'green', 'greenyellow', 'grey', 'honeydew',
        'hotpink', 'indianred', 'indigo', 'ivory', 'khaki', 'lavender',
        'lavenderblush', 'lawngreen', 'lemonchiffon', 'lightblue',
        'lightcoral', 'lightcyan', 'lightgoldenrodyellow', 'lightgray',
        'lightgreen', 'lightgrey', 'lightpink', 'lightsalmon',
        'lightseagreen', 'lightskyblue', 'lightslategray',
        'lightslategrey', 'lightsteelblue', 'lightyellow', 'lime',
        'limegreen', 'linen', 'magenta', 'maroon', 'mediumaquamarine',
        'mediumblue', 'mediumorchid', 'mediumpurple', 'mediumseagreen',
        'mediumslateblue', 'mediumspringgreen', 'mediumturquoise',
        'mediumvioletred', 'midnightblue', 'mintcream', 'mistyrose',
        'moccasin', 'navajowhite', 'navy', 'oldlace', 'olive', 'olivedrab',
        'orange', 'orangered', 'orchid', 'palegoldenrod', 'palegreen',
        'paleturquoise', 'palevioletred', 'papayawhip', 'peachpuff',
        'peru', 'pink', 'plum', 'powderblue', 'purple', 'red', 'rosybrown',
        'royalblue', 'saddlebrown', 'salmon', 'sandybrown', 'seagreen',
        'seashell', 'sienna', 'silver', 'skyblue', 'slateblue',
        'slategray', 'slategrey', 'snow', 'springgreen', 'steelblue',
        'tan', 'teal', 'thistle', 'tomato', 'turquoise', 'violet', 'wheat',
        'white', 'whitesmoke', 'yellow', 'yellowgreen',
    );

    if (in_array($color, $colors)) {
        return $color;
    }

    // #rrggbb
    if (preg_match('/^\#[0-9a-f]{6}$/i', $color)) {
        return $color;
    }

    // #rgb
    if (preg_match('/^\#[0-9a-f]{3}$/i', $color)) {
        // From CSS level 1 spec: the three-digit RGB notation (#rgb)
        // is converted into six-digit form (#rrggbb) by replicating digits
        $r = substr($color, 1, 1);
        $g = substr($color, 2, 1);
        $b = substr($color, 3, 1);
        return "#$r$r$g$g$b$b";
    }

    // rgb(r,g,b), each part can be an integer or a precentage value
    $part_re = '\s*(\d+%?)\s*';
    if (preg_match('/^rgb\(' . $part_re . ',' . $part_re . ',' . $part_re . '\)$/i', $color, $matches)) {
        // first element containing the whole match is now useless, and
        // can safely be used as a storage for hash character
        $matches[0] = '#'; 
        for ($i = 1, $n = count($matches); $i < $n; ++$i) {
            $part = $matches[$i];
            if (substr($part, -1) == '%') {
                $part = round(substr($part, 0, -1) * 255 / 100.);
            }
            // From CSS level 1 spec: Values outside the numerical ranges
            // should be clipped.
            $part = dechex(min($part, 255));
            if (strlen($part) < 2) {
                $part = '0' . $part;
            }
            $matches[$i] = $part;
        }
        return implode('', $matches);
    }

    // no rgba support for compatibility with older browsers

    // unable to normalize
    return false;
} // }}}

/**
 * @return false|string
 */
function scholar_validate_url($url) // {{{
{
    $url = trim($url);

    if (false === strpos($url, '://')) {
        // wzgledny URL
        $url = valid_url($url, false) ? $url : false;
        if ($url) {
            // dolacz sciezke bazowa do wzglednego adresu
            global $base_url;
            $url = $base_url . '/' . ltrim($url, '/');
        }
    } else {
        // absolutny URL
        $url = valid_url($url, true) ? $url : false;
    }

    return $url;
} // }}}

// vim: fdm=marker
