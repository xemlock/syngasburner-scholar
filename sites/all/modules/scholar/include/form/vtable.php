<?php

function theme_scholar_element_vtable($element) // {{{
{
    static $vtable_counter = 0;

    $id  = empty($element['#id']) ? ('vtable-' . $vtable_counter++) : $element['#id'];
    $arg = drupal_to_js('#' . $id);

    drupal_add_js("\$(function(){Scholar.formElements.vtable($arg)});", 'inline');

    return '<table id="' . $id . '" class="scholar-vtable"><tbody>' . $element['#children'] . '</tbody></table>';
} // }}}

function theme_scholar_element_vtable_row($element) // {{{
{
    $id = isset($element['#id']) ? (' id="' . $element['#id'] . '"') : '';

    return '<tr' . $id . '><td><div class="vtab"><div class="vtab-title"><a href="#!" onfocus="this.blur()">' . $element['#title'] . '</a></div><div class="vtab-description">' . $element['#description'] . '</div></div></td><td> ' . $element['#children'] . '</td></tr>';
} // }}}

// vim: fdm=marker
