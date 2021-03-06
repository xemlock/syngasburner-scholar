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
function theme_scholar_dl($data, $attributes = array()) // {{{
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

function theme_scholar_table($header, $rows, $attributes = array(), $caption = null) // {{{
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
 * Generuje kod HTML znacznika SELECT na podstawie podanego elementu. Funkcja
 * zwraca znacznik bez zadnych wrapperow.
 *
 * @param array $element
 * @return string
 */
function theme_scholar_select_tag($element) // {{{
{
    scholar_element_set_class($element, 'form-select');

    $attrs = scholar_element_attributes($element);

    return '<select' . drupal_attributes($attrs) . '>' . form_select_options($element) . '</select>';
} // }}}

/**
 * Generuje kod HTML znacznika INPUT na podstawie podanego elementu. Funkcja
 * zwraca znacznik bez zadnych wrapperow.
 *
 * @param array $element
 * @return string
 */
function theme_scholar_textfield_tag($element) // {{{
{
    scholar_element_set_class($element, 'form-text');

    $attrs = scholar_element_attributes($element);
    $attrs['type'] = 'text';
    $attrs['value'] = $element['#value'];

    return '<input' . drupal_attributes($attrs) . '/>';
} // }}}

function theme_scholar_label($title, $required = false) // {{{
{
    $title    = filter_xss_admin($title);
    $required = $required ? '<span class="form-required" title="' . t('This field is required.') . '">*</span>' : '';

    return '<label>' . t('!title: !required', array('!title' => $title, '!required' => $required)) . '</label>';
} // }}}

function theme_scholar_textfield($element) // {{{
{
    if (isset($element['#fullwidth']) && $element['#fullwidth']) {
        scholar_element_add_class($element, 'fullwidth');
    }

    $textarea = theme_textfield($element);
    return $textarea;
} // }}}

function theme_scholar_textarea($element) // {{{
{
    if (isset($element['#bbcode']) && $element['#bbcode']) {
        $element['#description'] .= t('Use BBCode markup, supported tags are listed <a href="#!">here</a>');
    }

    if (isset($element['#fullwidth']) && $element['#fullwidth']) {
        scholar_element_add_class($element, 'fullwidth');
    }

    $textarea = theme_textarea($element);
    return $textarea;
} // }}}

/**
 * Przyciski radiowe w układzie poziomym.
 */
function theme_scholar_radios($element) // {{{
{
    $output = '<div class="form-radios">';

    $attrs = scholar_element_attributes($element);
    $attrs['type'] = 'radio';

    $id = $attrs['id'];

    if (isset($element['#options'])) {
        foreach ((array) $element['#options'] as $value => $label) {
            if (isset($attrs['checked'])) {
                unset($attrs['checked']);
            }
            if ($element['#default_value'] == $element['#value']) {
                $attrs['checked'] = 'checked';
            }
            $attrs['id']    = form_clean_id($id . '-' . $value);
            $attrs['value'] = $value;

            $output .= '<label class="option"><input' . drupal_attributes($attrs) . '/> ' . $label . '</label>';
        }
    }

    $output .= '</div>';

    return $output;
} // }}}

function theme_scholar_description($description) // {{{
{
    return '<div class="description">' . $description . '</div>';
} // }}}

/**
 * Owija zawartość elementu w DIV.scholar-element-wrapper.
 *
 * @param array $element
 * @param string $content
 * @return string
 */
function scholar_theme_element($element, $content) // {{{
{
    return theme('form_element', $element, '<div class="scholar-element-wrapper">' . $content . '</div>');
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
function scholar_oplink($title, $path_name, $subpath = null) // {{{
{
    $args = array_slice(func_get_args(), 1);
    $path = call_user_func_array('scholar_path', $args);

    // jezeli w sciezce znajduje sie znak zapytania, oznacza to, ze podano
    // sciezke wyszukiwania (query). Uzyj jej zamiast domyslnej zawierajacej
    // zmienna destination wskazujaca na biezaca sciezke Drupala.
    if (false !== ($pos = strpos($path, '?'))) {
        $query = substr($path, $pos + 1);
        $path  = substr($path, 0, $pos);
    } else {
        $query = 'destination=' . $_GET['q'];
    }

    return l($title, $path, array('query' => $query));
} // }}}

function scholar_language_label($language, $label = null) // {{{
{
    static $have_languageicons = null;

    if (null === $have_languageicons) {
        $have_languageicons = module_exists('languageicons');
    }

    $name = scholar_languages($language);

    if ($have_languageicons) {
        $dummy = new stdClass;
        $dummy->language = $language;

        $label = theme('languageicons_icon', $dummy, $name) . ' ' . $label;

    } else {
        $label = '[' . $name . '] ' . $label;
    }

    return '<span class="scholar-language-label">' . $label . '</span>';
} // }}}

// vim: fdm=marker
