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
            '#description' => t('Date and time format: YYYY-MM-DD HH:MM.'),
        ),
        'authors' => array(
            '#title'       => t('Lecturers'),
            '#required'    => true,
        ),
        'parent_id' => empty($parents) ? false : array(
            '#title'       => t('Training'),
            '#options'     => $parents,
            '#description' => t('A training during which this class is .'),
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
            // zajecia odbywaja sie jednego dnia
            'start_date' => array(
                '#title' => t('Date'),
            ),
            'end_date'   => false,
        ),
    ), $record);

    $form['#validate'][] = 'scholar_presentation_form_validate';

    $form['submit'] = array(
        '#type'  => 'submit',
        '#value' => empty($record) ? t('Save') : t('Save changes'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_admin_path('presentation'),
    );

    return $form;
}

// vim: fdm=marker
