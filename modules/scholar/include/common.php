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
function scholar_get_locale()
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
}

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

    // Windows: polish_Poland.28592
    // Linux: pl_PL, pl_PL.utf8, pl_PL.ISO8859-2
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
 * @return array|string
 */
function scholar_countries($code = null) // {{{
{
    global $language;
    static $countries;

    if (null === $countries) {
        $cid = 'scholar_countries:' . $language->language;

        if (!($data = cache_get($cid))) {
            if (class_exists('Zend_Locale', true)) {
                $countries = Zend_Locale::getTranslationList('Territory', $language->language, 2);

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

                switch ($language->language) {
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
    }

    if (null === $code) {
        return $countries;
    }

    return isset($countries[$code]) ? $countries[$code] : null;
} // }}}


// vim: fdm=marker
