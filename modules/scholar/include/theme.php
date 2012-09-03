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
function scholar_theme_dl($data, $attributes = array())
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
}

function scholar_theme_table($header, $rows, $attributes = array(), $caption = null)
{
    scholar_add_css();

    if (isset($attributes['class'])) {
        $attributes['class'] .= ' scholar-table';
    } else {
        $attributes['class'] = 'scholar-table';
    }

    return theme_table($header, $rows, $attributes, $caption);
}

// vim: fdm=marker
