<?php

function form_type_scholar_element_multi_value($element, $post = false) // {{{
{
    $value = array();
    $from  = '';
    $to    = '';

    if (false === $post && isset($element['#default_value'])) {
        $post = $element['#default_value'];
    }

    if ($post && isset($post['from']) && isset($post['to'])) {
        $from = (string) $post['from'];
        $to   = (string) $post['to'];

        if (strlen($from) && strlen($to)) {
            $value['from'] = $from;
            $value['to']   = $to;
        }
    }

    return $value;
}

function theme_scholar_element_multi($element) // {{{
{
    $from = array(
        '#parents' => $element['#parents'],
        '#value' => $element['#value'] ? $element['#value']['from'] : '',
        '#attributes' => array('title' => t('Start date')),
    );
    $from['#parents'][] = 'from';

    $to = array(
        '#parents' => $element['#parents'],
        '#value' => $element['#value'] ? $element['#value']['to'] : '',
        '#attributes' => array('title' => t('End date')),
    );
    $to['#parents'][] = 'to';

    $value = '<table class="scholar-"><tr><td>' . scholar_theme_textfield($from) . '</td><td>&ndash;<td></td><td>' . scholar_theme_textfield($to) . '</td></tr></table>';

    return theme_form_element($element, $value);
} // }}}
