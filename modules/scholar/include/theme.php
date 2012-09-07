<?php

/**
 * Generuje kod HTML listy definicji (DL).
 *
 * @param array $data
 *     elementy tablicy pobierane są po dwa, pierwszy z pary
 *     wypełnia zawartość tagu DT, drugi tagu DD. Ostatniemu 
 *     niesparowanemu elementowi odpowiada następnik będący
 *     pustym stringiem.
 * @return string
 */
function scholar_theme_dl($data, $attributes = array()) // {{{
{
    scholar_add_css();

    if (isset($attributes['class'])) {
        $attributes['class'] .= ' scholar-dl';
    } else {
        $attributes['class'] = 'scholar-dl';
    }

    $output = '<dl' . drupal_attributes($attributes) . '>';

    $keys = array_keys($data);
    for ($i = 0, $n = count($keys); $i < $n; $i += 2) {
        $curr = $keys[$i];
        $next = isset($keys[$i + 1]) ? $keys[$i + 1] : null;

        $output .= '<dt>' . $data[$curr] . '</dt>'
                .  '<dd>' . (isset($next) ? $data[$next] : '') . '</dd>';
    }

    $output .= '</dl>';

    return $output;
} // }}}

/**
 * Zwraca liczbę kolumn w tabeli na podstawie definicji nagłówka.
 *
 * @param array $header
 * @param array $rows
 *     jeżeli puste do tabeli zostanie dodany wiersz z informacją
 *     o braku rekordów
 * @param array $attributes
 *     jeżeli podano regions - tabela ustawi wszystkie niezbędne dane
 * @return int
 */
function scholar_table_colspan($header) // {{{
{
    $colspan = 0;
    foreach ($header as $col) {
        $colspan += isset($col['colspan']) ? max(1, $col['colspan']) : 1;
    }
    return $colspan;
} // }}}

function scholar_theme_table($header, $rows, $attributes = array(), $caption = null) // {{{
{
    scholar_add_css();

    if (isset($attributes['class'])) {
        $attributes['class'] .= ' scholar-table';
    } else {
        $attributes['class'] = 'scholar-table';
    }

    if (isset($attributes['regions'])) {
        $regions = (bool) $attributes['regions'];
        unset($attributes['regions']);
    } else {
        $regions = false;
    }

    $colspan = scholar_table_colspan($header);

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records'), 'colspan' => $colspan),
        );
    }

    // przeksztalc regiony w poprawne wiersze tabeli
    foreach ($rows as &$row) {
        if (is_array($row) && array_key_exists('region', $row)) {
            $row = array(
                'data' => array(
                    array(
                        'data'    => $row['region'],
                        'colspan' => $colspan,
                        'class'   => 'region',
                    ),
                ),
                'class' => 'region',
            );
        }
    }
    unset($row);

    return theme_table($header, $rows, $attributes, $caption);
} // }}}

/**
 * Definicja komórki tabeli która przechowuje (lub odpowiada, w przypadku
 * nagłówka tabeli) uchwyt do przenoszenia wierszy.
 */
function scholar_tabledrag_handle() // {{{
{
    static $handle = array('class' => 'tabledrag-handle');
    return $handle;
} // }}}

/**
 * Generuje kod HTML elementu, ale tylko tego elementu bez zadnych
 * wrapperow.
 */
function scholar_theme_select($element) // {{{
{
    scholar_element_set_class($element, 'form-select');

    $attrs = scholar_element_attributes($element);

    return '<select' . drupal_attributes($attrs) . '>' . form_select_options($element) . '</select>';
} // }}}

function scholar_theme_textfield($element) // {{{
{
    scholar_element_set_class($element, 'form-text');

    $attrs = scholar_element_attributes($element);
    $attrs['type'] = 'text';
    $attrs['value'] = $element['#value'];

    return '<input' . drupal_attributes($attrs) . '/>';
} // }}}

/**
 * @param string $title
 *     Tytuł hiperłącza
 * @param string $path_name
 *     Nazwa ścieżki w menu
 * @param string $subpath
 * @param ...
 *     Dodatkowe parametry do zastąpienia nimi symboli zastępczych w $subpath
 */
function scholar_oplink($title, $path_name, $subpath) // {{{
{
    $args = array_slice(func_get_args(), 1);
    $path = call_user_func_array('scholar_path', $args);

    return l($title, $path, array('query' => 'destination=' . $_GET['q']));
} // }}}

// vim: fdm=marker
