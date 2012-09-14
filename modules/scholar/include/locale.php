<?php

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
