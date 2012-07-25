<?php

function scholar_form_alter(&$form, &$form_state, $form_id)
{
    // Nie dopuszczaj do bezposredniej modyfikacji wezlow
    // aktualizowanych automatycznie przez modul scholar.
    // Podobnie z wykorzystawymi eventami.
    $node = $form['#node'];
//    echo '<pre>', __FUNCTION__, ': ', $form_id, '</pre>';
}

function scholar_nodeapi($node, $op)
{
    if ($op == 'load') {
//        echo '<pre>', $op, ': ', print_r($node, 1), '</pre>';
    }
}

function scholar_menu()
{
    $items = array();

    $items['scholar'] = array(
        'title' => t('Scholar'),
        'access arguments' => array('use scholar'),
        'page callback' => 'scholar_index',
    );
    $items['scholar/people'] = array(
        'title' => t('People'),
        'access arguments' => array('use scholar'),
        'page callback' => 'scholar_people_list',
        'parent' => 'scholar',
        // 'file' => 'gallery.gallery.php',
    );
    $items['scholar/people/add'] = array(
        'title' => t('Add person'),
        'access arguments' => array('use scholar'),
        'type' => MENU_LOCAL_TASK,
        'page callback' => 'scholar_people_add',
    );
    $items['scholar/people/edit/%id'] = array(
        'title' => t('Edit person'),
        'page callback' => 'drupal_get_form',
        'page arguments' => array('scholar_people_form', 3),
        'type' => MENU_CALLBACK,
        'access arguments' => array('use scholar'),
        // 'file' => 'gallery.gallery.php',
        'parent' => 'scholar/people',
    );

    return $items;
}

function scholar_index()
{
    return '<pre>' . print_r(func_get_args(), 1) . '</pre>';
}
function scholar_people_form()
{
    return '<pre>' . print_r(func_get_args(), 1) . '</pre>';
}
function scholar_people_list()
{
    return __FUNCTION__;
}

function scholar_people_add()
{
    return __FUNCTION__;
}
