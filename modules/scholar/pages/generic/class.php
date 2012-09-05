<?php

function scholar_generics_class_form(&$form_state, $record = null)
{
    $parents    = scholar_generic_parent_options('training');
    $categories = scholar_category_options('generics', 'class');

    $form = scholar_generic_form(array(
        'title' => empty($parents) ? array('#required' => true) : array(
            '#required' => true,
        ),
        array('#type' => 'markup', '#value' => '<table><tr><td valign="top">'),
        'start_date' => array(
            '#maxlength' => 16,
            '#description' => t('Date format: YYYY-MM-DD'),
        ),
        array('#type' => 'markup', '#value' => '</td><td width="100%" valign="top">'),
        'start_time' => array(
            '#title' => t('Hours'),
            '#type' => 'scholar_element_timespan',
        ),
        array('#type' => 'markup', '#value' => '</td></tr></table>'),
        'authors' => array(
            '#title'       => t('Lecturers'),
        ),
        'parent_id' => array(
            '#title'       => t('Training'),
            '#options'     => $parents,
            '#description' => t('A training during which this class is carried out.'),
            '#required'    => true,
            // jezeli w adresie strony podano identyfikator rodzica
            // ustaw ja jako domyslna wartosc pola
            '#default_value' => isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null,
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
        '#value' => scholar_path("generics.training"),
    );

    return $form;
}

function _scholar_generics_class_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Date'),     'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'),    'field' => 'title'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        substr($row['start_date'], 0, (int) $row['start_date_len']),
        check_plain($row['title']),
        scholar_oplink(t('edit'), 'generics.class', 'edit/%d', $row['id']),
        scholar_oplink(t('delete'), 'generics.class', 'delete/%d', $row['id']),
    );
} // }}}

// vim: fdm=marker
