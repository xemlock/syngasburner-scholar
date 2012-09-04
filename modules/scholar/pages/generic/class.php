<?php

function scholar_generics_class_form(&$form_state, $record = null)
{
    $parents    = scholar_generic_parent_options('training');
    $categories = scholar_category_options('generics', 'class');

    $form = scholar_generic_form(array(
        'title' => empty($parents) ? array('#required' => true) : array(
            '#required' => true,
        ),
        'start_date' => array(
            '#maxlength' => 16,
            '#description' => t('Date and time format: YYYY-MM-DD HH:MM. The time part (hours and minutes) can be omitted.'),
        ),
        'authors' => array(
            '#title'       => t('Lecturers'),
        ),
        'parent_id' => empty($parents) ? false : array(
            '#title'       => t('Training'),
            '#options'     => $parents,
            '#description' => t('A training during which this class is carried out.'),
            // jezeli w adresie strony podano identyfikator konferencji
            // ustaw ja jako domyslna wartosc pola
            '#default_value' => isset($_GET['conference']) ? intval($_GET['conference']) : null,
        ),
        'category_id' => empty($categories) ? false : array(
            '#options'     => $categories,
            '#description' => t('Specify class type, e.g. lecture, exercise etc.'),
        ),
        'suppinfo' => array(
            '#description' => t('Additional details about this class.'),
        ),
        'files',
        'nodes',
        'events' => array(
            'start_date' => false,
            'end_date'   => false,
        ),
    ), $record);

    $form['submit'] = array(
        '#type'  => 'submit',
        '#value' => empty($record) ? t('Save') : t('Save changes'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_list_path("generics.training"),
    );

    return $form;
}

// vim: fdm=marker
