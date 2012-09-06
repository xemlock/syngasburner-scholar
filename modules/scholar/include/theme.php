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

function scholar_theme_table($header, $rows, $attributes = array(), $caption = null) // {{{
{
    scholar_add_css();

    if (isset($attributes['class'])) {
        $attributes['class'] .= ' scholar-table';
    } else {
        $attributes['class'] = 'scholar-table';
    }

    return theme_table($header, $rows, $attributes, $caption);
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
