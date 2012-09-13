<?php

/**
 * Interfejs do pobierania wartości zmiennych konfiguracyjnych.
 */
function scholar_setting($name, $language = null)
{
    static $settings = array();

    $varname = scholar_setting_name($name, $language);

}

/**
 * Zwraca nazwę zmiennej konfiguracyjnej.
 */
function scholar_setting_name($name, $language = null)
{
    $varname = 'scholar_' . $name;

    if ($language) {
        $varname .= '_' . $language;
    }

    return $varname;
}

/**
 * Domyślna szerokość obrazów to 150px.
 * @return int
 */
function scholar_setting_image_width()
{
    return max(150, variable_get(scholar_setting_name('image_width'), null));
}

/**
 * @return string
 */
function scholar_setting_format_date($language)
{
    // domyslnie pelna data w formacie ISO 8601 (YYYY-MM-DD)
    return variable_get(scholar_setting_name('format_date', $language), 'Y-m-d');
}

/**
 * @return array
 */
function scholar_setting_format_daterange_same_month($language)
{
    $format = variable_get(scholar_setting_name('format_daterange_same_month', $language), null);

    if (!is_array($format) || !isset($format['start_date']) || !isset($format['end_date'])) {
        $format = array(
            'start_date' => 'Y-m-d',
            'end_date'   => 'd',
        );
    }

    return $format;
}

/**
 * @return array
 */
function scholar_setting_format_daterange_same_year($language = null)
{
    $format = variable_get(scholar_setting_name('format_daterange_same_year', $language), null);

    if (!is_array($format) || !isset($format['start_date']) || !isset($format['end_date'])) {
        $format = array(
            'start_date' => 'Y-m-d',
            'end_date'   => 'm-d',
        );
    }

    return $format;
}

// vim: fdm=marker
