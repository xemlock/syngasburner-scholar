<?php

/**
 * Interfejs do pobierania wartości zmiennych konfiguracyjnych,
 * zapisuje raz pobrane wartości do pamięci podręcznej.
 */
function scholar_setting($name, $language = null) // {{{
{
    static $settings = array();

    // jezeli nie podano jezyka, ustawienie bedzie brane dla biezacego
    if (null === $language) {
        $language = scholar_language();
    }

    $func = 'scholar_setting_' . $name;
    $key  = $func . ':' . $language;

    if (!array_key_exists($key, $settings)) {
        $settings[$key] = function_exists($func)
            ? call_user_func($func, $language)
            : null;
    }

    return $settings[$key];
} // }}}

/**
 * Zwraca nazwę zmiennej konfiguracyjnej.
 *
 * @param string $name
 * @param string $language
 */
function scholar_setting_name($name, $language = null) // {{{
{
    $varname = 'scholar_' . $name;

    if ($language) {
        $varname .= '_' . $language;
    }

    return $varname;
} // }}}

/**
 * Domyślna, minimalna szerokość obrazów to 150px. Ustawienie jest
 * niezależne od języka.
 *
 * @return int
 */
function scholar_setting_image_width() // {{{
{
    return max(150, variable_get(scholar_setting_name('image_width'), 0));
} // }}}

/**
 * Czy użyć atrybutów podpinających Lightbox do obrazów w preambule
 * generowanych stron.
 *
 * @return int
 */
function scholar_setting_image_lightbox() // {{{
{
   return max(0, variable_get(scholar_setting_name('image_lightbox'), 0));
} // }}}

/**
 * @return string
 */
function scholar_setting_format_date($language) // {{{
{
    // domyslnie pelna data w formacie ISO 8601 (YYYY-MM-DD)
    return variable_get(scholar_setting_name('format_date', $language), 'Y-m-d');
} // }}}

/**
 * @return array
 */
function scholar_setting_format_daterange_same_month($language) // {{{
{
    $format = variable_get(scholar_setting_name('format_daterange_same_month', $language), null);

    if (!is_array($format) || !isset($format['start_date']) || !isset($format['end_date'])) {
        $format = array(
            'start_date' => 'Y-m-d',
            'end_date'   => 'd',
        );
    }

    return $format;
} // }}}

/**
 * @return array
 */
function scholar_setting_format_daterange_same_year($language = null) // {{{
{
    $format = variable_get(scholar_setting_name('format_daterange_same_year', $language), null);

    if (!is_array($format) || !isset($format['start_date']) || !isset($format['end_date'])) {
        $format = array(
            'start_date' => 'Y-m-d',
            'end_date'   => 'm-d',
        );
    }

    return $format;
} // }}}

/**
 * Zwraca identyfikator formatu danych dla treści węzła. Domyślnie zwracana
 * jest wartość 2 odpowiadająca formatowi Full HTML.
 *
 * @return int
 */
function scholar_setting_node_format() // {{{
{
    return variable_get(scholar_setting_name('node_format'), 2);
} // }}}

/**
 * Zwraca stan uruchomienia pamięci podręcznej treści węzłów. Domyślnie
 * pamięć podręczna jest włączona.
 *
 * @return int
 */
function scholar_setting_node_cache() // {{{
{
    return variable_get(scholar_setting_name('node_cache'), 1);
} // }}}

/**
 * Zwraca czas ostatniej zmiany w tabelach modułu.
 *
 * @return int
 */
function scholar_setting_last_change()
{
    return variable_get(scholar_setting_name('last_change'), 0);
}

/**
 * Zwraca liczbę rekordów na stronę w listach rekordów.
 *
 * @return int
 */
function scholar_admin_page_size() // {{{
{
    return 25;
} // }}}

// vim: fdm=marker
