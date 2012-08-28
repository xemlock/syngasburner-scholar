<?php

/**
 * @return array
 */
function form_type_scholar_element_people_value($element, $post = false) // {{{
{
    $value = array();

    if (false === $post && $element['#default_value']) {
        $post = $element['#default_value'];
    }

    if ($post) {
        foreach ((array) $post as $data) {
            if (empty($data['id'])) {
                continue;
            }

            $value[] = array(
                'id'         => intval($data['id']),
                'first_name' => isset($data['first_name']) ? strval($data['first_name']) : '',
                'last_name'  => isset($data['last_name']) ? strval($data['last_name']) : '',
                'weight'     => isset($data['weight']) ? intval($data['weight']) : 0,
            );
        }
    }

    return $value;
} // }}}

/**
 * @return string
 */
function theme_scholar_element_people($element) // {{{
{
    $params = array(
        '#' . $element['#id'],
        $element['#name'],
        url(scholar_admin_path('people/itempicker')),
        $element['#value'],
    );
    $params = implode(',', array_map('drupal_to_js', $params));

    drupal_add_js('misc/tabledrag.js', 'core');
    drupal_add_js('misc/tableheader.js', 'core');
    drupal_add_js("\$(function(){Scholar.formElements.people($params)})", 'inline');

    return theme_form_element($element, '<div id="' . $element['#id'] .'"><noscript><div class="error">' . t('JavaScript is required.') . '</div></noscript></div>');
} // }}}

// vim: fdm=marker
