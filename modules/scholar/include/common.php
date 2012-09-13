<?php

/**
 * Transliteracja z UTF-8 do ASCII.
 *
 * @param string $string
 * @return string
 */
function scholar_ascii($string) // {{{
{
    // http://stackoverflow.com/questions/5048401/why-doesnt-translit-work#answer-5048939
    // The transliteration done by iconv is not consistent across
    // implementations. For instance, the glibc implementation transliterates
    // é into e, but libiconv transliterates it into 'e.

    $string = str_replace(
        array("æ",  "Æ",   "ß",  "þ",  "Þ", "–", "’", "‘", "“", "”", "„"),
        array("ae", "Ae", "ss", "th", "Th", "-", "'", "'", "\"", "\"", "\""), 
        $string
    );

    if (ICONV_IMPL === 'glibc') {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    } else {
        // na podstawie http://smoku.net/artykuly/zend-filter-ascii
        $string = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT//IGNORE', $string);
        $string = strtr($string,
            "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe"
          . "\x9c\x9a\xba\x9d\x9f\x9e\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6"
          . "\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3\xd4"
          . "\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2"
          . "\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf0"
          . "\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe",
            "ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYT"
          . "sraaaalccceeeeiiddnnooooruuuuyt");
    }

    return $string;
} // }}}

/**
 * Przekierowanie bez żadnej wyrafinowanej obsługi parametru destination.
 * Używane tylko tam, gdzie musimy zignorować destination.
 *
 * @param string $path
 * @param string $query
 * @param string $fragment
 */
function scholar_goto($path, $query = null, $fragment = null) // {{{
{
    // drupal_goto jest fundamentalnie uposledzone ze wzgledu
    // na dzika obsluge destination
    $url = url($path, array(
        'query'    => $query,
        'absolute' => true,
        'fragment' => $fragment ? ltrim($fragment, '#') : null,
    ));
    $url = str_replace(array("\r", "\n"), '', $url);

    session_write_close();

    header('Status: 302 Found');
    header('Location: '. $url, true, 302);
    exit;
} // }}}

function scholar_add_tab($text, $path, $query = null) // {{{
{
    // funkcja udostepniana przez modul tabs
    if (function_exists('drupal_add_tab')) {
        drupal_add_tab($text, $path, $query);
    }
} // }}}

/**
 * Wykorzystuje locale_language_list().
 *
 * @param string $language
 * @param string $default
 */
function scholar_languages($language = null, $default = null) // {{{
{
    static $languages = null;

    if (null === $languages) {
        $languages = module_invoke('locale', 'language_list');
    }

    if (null === $language) {
        return $languages;
    }

    return isset($languages[$language]) ? $languages[$language] : '';
} // }}}

/**
 * Zwraca kod aktualnie używanego języka. Funkcja istnieje ze względu
 * na konflikt nazw zmiennych, globalnej i zmiennej zwykle używanej jako
 * argument funkcji.
 *
 * @return string
 */
function scholar_language() // {{{
{
    global $language;
    return (string) $language->language;
} // }}}

function _scholar_detect_locale($language) // {{{
{
    static $territories = null;

    // wygeneruj liste jezykow i krajow, w ktorych sie nimi mowi
    if (null === $territories) {
        $cid = 'scholar_language_territory';

        if (!($data = cache_get($cid))) {
            if (class_exists('Zend_Locale', true)) {
                $territories = Zend_Locale::getTranslationList('TerritoryToLanguage', $language->language);
                cache_set($cid, $territories);

            } else {
                // pusta tablica, nie zapisuj w cache'u
                $territories = array();
            }
        } else {
            $territories = (array) $data->data;
        }
    }

    $locale = false;

    switch ($language) {
        case 'en':
            // jezyk angielski obsluz osobno, poniewaz jest zbyt duzo
            // krajow, w ktorych jest on uzywany, ponadto uzywany algorytm
            // wykrywania krajow dalby jakis dziki rezultat.
            $locale = 'en_US';
            break;

        default:
            if (isset($territories[$language])) {
                // sprawdz czy istnieje kraj, ktory ma kod taki jak kod
                // jezyka pisany wielkimi literami. Jezeli tak, uzyj go.
                // Jezeli nie, uzyj pierwszego terytorium z listy.
                $territory = strtoupper($language);

                if (!in_array($territory, $territories)) {
                    $territory = reset($territories);
                }

                $locale = $language . '_' . $territory;
            }
            break;
    }

    return $locale;
} // }}}

/**
 * Zwraca pełne locale, tzn. z kodem języka i kraju. Jeżeli ustalenie kraju
 * nie jest mozliwe funkcja zwraca false. Ustawienie to jest używane do
 * sortowania tablic za pomocą funkcji {@see scholar_asort}.
 *
 * @return string
 */
function scholar_get_locale() // {{{
{
    global $language;
    static $locale = null;

    if (null === $locale) {
        // sprawdz czy locale jest w ustawieniach, jezeli tak, uzyj go.
        // Jest to pomocne, gdy na serwerze nie ma locali w utf-8, lub
        // serwer stoi na Windowsach.
        $locale = variable_get('scholar_locale:' . $language->language, false);

        if (!$locale) {
            $locale = _scholar_detect_locale($language->language);
        }
    }

    return $locale;
} // }}}

/**
 * Działa tylko pod Linuksem, ponieważ ustawienia locali pod Windowsami
 * nie obsługują UTF-8.
 */
function scholar_asort(&$array, $callback = 'strcoll') // {{{
{
    $locale = scholar_get_locale();

    if ($locale) {
        $old_locale = setlocale(LC_COLLATE, 0);
        setlocale(LC_COLLATE, $locale);
    }

    // Locale aware string comparison requires that selected locale
    // is installed on system. Under Linux check available locale using:
    // $ locale -a

    // How to install a new locale (Ubuntu Linux):
    // $ cd /usr/share/locales
    // $ ./install-language-pack {locale_name}
    // $ dpkg-reconfigure locales

    if ($locale && ('strcoll' == $callback)) {
        // this of course won't work on Windows, see:
        // https://bugs.php.net/bug.php?id=46165
        $result = asort($array, SORT_LOCALE_STRING);

    } else {
        $result = uasort($array, $callback);
    }

    if ($locale) {
        setlocale(LC_COLLATE, $old_locale);
    }

    return $result;
} // }}}

/**
 * Zwraca listę wszystkich krajów lub nazwę kraju o podanym kodzie
 * ISO 3166-1 alpha-2.
 *
 * @param string $code
 * @param string $language_code
 * @return array|string
 */
function scholar_countries($code = null, $language_code = null) // {{{
{
    static $_cache = array();

    if (null === $language_code) {
        global $language;
        $language_code = $language->language;
    }

    $language_code = (string) $language_code;

    if (!isset($_cache[$language_code])) {
        $cid = 'scholar_countries:' . $language_code;

        if (!($data = cache_get($cid))) {
            if (class_exists('Zend_Locale', true)) {
                $countries = Zend_Locale::getTranslationList('Territory', $language_code, 2);

                // remove invalid countries
                // DD = East Germany
                // SU = USSR
                // VD = North Vietnam
                // ZZ = Unknown or Invalid Region
                foreach (array('DD', 'SU', 'VD', 'ZZ') as $invalid) {
                    if (isset($countries[$invalid])) {
                        unset($countries[$invalid]);
                    }
                }

                switch ($language_code) {
                    case 'pl':
                        // remove SAR part from China administered country names, as
                        // it is not obligatory, see: 
                        // http://en.wikipedia.org/wiki/Hong_Kong#cite_note-1
                        foreach ($countries as $key => $value) {
                            $countries[$key] = str_ireplace(', Specjalny Region Administracyjny Chin', '', $value);
                        }
                        break;
                }

                scholar_asort($countries);
                cache_set($cid, $countries);

            } else {
                // no Zend_Locale class available, write nothing to cache
                $countries = array();
            }

        } else {
            $countries = (array) $data->data;
        }

        $_cache[$language_code] = $countries;
    }

    if (null === $code) {
        return $_cache[$language_code];
    }

    return isset($_cache[$language_code][$code]) ? $_cache[$language_code][$code] : null;
} // }}}

/**
 * Funkcja mimo, że wolniejsza od odpowiednika, przyjmuje datę w formacie ISO 8601.
 * Nie ma ograniczenia na dolną wartość (np. pod Windowsami ujemne timestampy
 * nie są obsługiwane).
 * 
 * Obsługiwane symbole zastępcze:
 * d 	Day of the month, 2 digits with leading zeros               01 to 31
 * j 	Day of the month without leading zeros                      1 to 31
 * m 	Numeric representation of a month, with leading zeros       01 through 12
 * M	A short textual representation of a month, three letters    Jan through Dec
 * n 	Numeric representation of a month, without leading zeros    1 through 12
 * Y 	A full numeric representation of a year, 4 digits           Examples: 1999 or 2003
 */
function scholar_date($format, $date, $language = null) // {{{
{
    if (null === $language) {
        $language = scholar_language();
    }

    $months = array('',
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    );

    $date = (array) $date;

    if (empty($date['year'])) {
        // nie powinno nigdy sie zdarzyc, jezeli podano poprawna date
        $date['year'] = 1;
    }

    if (empty($date['month'])) {
        $date['month'] = 1;
    }

    if (empty($date['day'])) {
        $date['day'] = 1;
    }

    return strtr($format, array(
        'd' => sprintf('%02d', $date['day']),
        'j' => $date['day'],
        'm' => sprintf('%02d', $date['month']),
        'M' => t($months[$date['month']], array(), $language),
        'n' => $date['month'],
        'Y' => sprintf('%04d', $date['year']),
    ));
} // }}}

/**
 * Formatuje datę lub zakres dat w postaci ODRF (Open Date Range Format).
 * Więcej {@see http://www.ukoln.ac.uk/metadata/dcmi/date-dccd-odrf/}.
 */
function scholar_format_date($date, $language = null) // {{{
{
    if (null === $language) {
        $language = scholar_language();    
    }

    if (is_int($date) || ctype_digit($date)) {
        // unix timestamp
        $parts = array(date('Y-m-d', $date));
    } else {
        // podziel ewentualny zakres dat na date poczatku i konca
        $parts = array_map('trim', explode('/', $date, 2));
    }

    switch (count($parts)) {
        case 2:
            // zakres dat
            $start_date_len = strlen($parts[0]);
            $end_date_len   = strlen($parts[1]);

            if ($start_date_len || $end_date_len) {
                // przynajmniej jedna z dat jest niepusta

                if (0 == $start_date_len) {
                    // nie ma daty poczatkowej, jest data koncowa
                    if ($end_date = scholar_parse_date($parts[1])) {
                        $format = scholar_setting('format_date', $language);
                        return '… – ' . scholar_date($format, $end_date, $language);
                    }

                } else if (0 == $end_date_len) {
                    // jest data poczatkowa, nie ma daty koncowej
                    if ($start_date = scholar_parse_date($parts[0])) {
                        $format = scholar_setting('format_date', $language);
                        return scholar_date($format, $start_date, $language) . ' – …';
                    }

                } else {
                    $start_date = scholar_parse_date($parts[0]);
                    $end_date   = scholar_parse_date($parts[1]);

                    if ($start_date && $end_date) {
                        if ($start_date['year'] != $end_date['year']) {
                            // rozne lata
                            $format = scholar_setting('format_date', $language);
                            $format = array(
                                'start_date' => $format,
                                'end_date'   => $format,
                            );

                        } else if ($start_date['month'] != $end_date['month']) {
                            // ten sam rok, rozne miesiace
                            $format = scholar_setting('format_daterange_same_year', $language);

                        } else if ($start_date['day'] != $end_date['day']) {
                            // ten sam rok, ten sam miesiac, rozny dzien
                            $format = scholar_setting('format_daterange_same_month', $language);

                        } else {
                            // ta sama data
                            $format = scholar_setting('format_date', $language);
                            return scholar_date($format, $start_date, $language);
                        }

                        return scholar_date($format['start_date'], $start_date, $language)
                             . ' – '
                             . scholar_date($format['end_date'], $end_date, $language); 
                    }
                }
            }
            break;

        case 1:
            $format = scholar_setting('format_date', $language);
            if ($date = scholar_parse_date($parts[0])) {
                return scholar_date($format, $date, $language);
            }
            break;
    }

    return false;
} // }}}

/**
 * Pobiera z bazy dane obrazu o podanym identyfikatorze, jednocześnie
 * wymuszając utworzenie miniatury o podanych rozmiarach. Tablica
 * z danymi obrazu jest wzbogacona o dwa pola: image_url i thumb_url
 * zawierające odpowiednio adres URL obrazu i adres URL miniatury obrazu.
 *
 * @param int $image_id
 * @param int $width
 * @param int $height
 * @return array
 */
function scholar_gallery_image($image_id, $width = null, $height = null) // {{{
{
    if (module_exists('gallery')) {
        $image_id = intval($image_id);
        $settings = array();

        $width = max(0, $width);
        if ($width) {
            $settings['width'] = $width;
        }

        $height = max(0, $height);
        if ($height) {
            $settings['height'] = $height;
        }

        $image = gallery_get_image($image_id, true);

        if ($image 
            && ($thumb_url = gallery_thumb_url($image, $settings))
            && ($image_url = gallery_image_url($image)))
        {
            $image['image_url'] = $image_url;
            $image['thumb_url'] = $thumb_url;
            return $image;
        }
    }

    return false;
} // }}}



// vim: fdm=marker
