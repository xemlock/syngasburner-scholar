<?php

// multilanguage textfield

function form_type_scholar_element_multilang_process($element)
{
    foreach ($element as $key => $value) {
        if (substr($key, 0, 1) != '#') {
            unset($element[$key]);
        }
    }

    return $element;
}

function form_type_scholar_element_multilang_value($element, $post = false)
{}

function theme_scholar_element_multilang($element)
{}

// vim: fdm=marker
