<?php

/**
 * @return string|null
 */
function form_type_scholar_textarea_value($element, $post = false) // {{{
{
    if (false === $post) {
        $value = isset($element['#default_value']) ? $element['#default_value'] : '';
    } else {
        $value = $post;
    }

    $value = trim((string) $value);

    return strlen($value) ? $value : '';
} // }}}

function theme_scholar_textarea($element) // {{{
{
    $element['#description'] .= t('Use BBCode markup, supported tags are listed <a href="#!">here</a>').

    $textarea = theme_textarea($element);
    return $textarea;
} // }}}
