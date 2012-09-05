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
    $multiple = isset($element['#multiple']) && $element['#multiple'];

    _form_set_class($element, array('form-select'));

    $attrs = isset($element['#attributes']) ? (array) $element['#attributes'] : array();
    $attrs['id']   = $element['#id'];
    $attrs['name'] = $element['#name'] . ($multiple ? '[]' : '');

    if ($multiple) {
        $attrs['multiple'] = 'multiple';
    }

    $size = isset($element['#size']) ? max(0, $element['#size']) : 0;
    if ($size) {
        $attrs['size'] = $size;
    }

    return '<select' . drupal_attributes($attrs) . '>' . form_select_options($element) . '</select>';
} // }}}


// vim: fdm=marker
