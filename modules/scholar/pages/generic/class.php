<?php

function scholar_generics_class_form(&$form_state, $record = null)
{
    $categories = scholar_category_options('generics', 'class');

    $form = scholar_generic_form(array(
        'title' => empty($parents) ? array('#required' => true) : array(
            '#required' => true,
        ),
        'time' => array(
            '#title' => t('Date and time'),
            '#type' => 'scholar_element_timespan',
            '#description' => 'Date format: YYYY-MM-DD.',
            '#minhour' => 6, // wczesniej niz o 6. raczej nie ma zajec
            '#default_value' => empty($record) ? null : array(
                'date'  => substr($record->start_date, 0, 10),
                'start' => substr($record->start_date, 11, 5),
                'end'   => substr($record->end_date, 11, 5),
            ),
        ),
        'authors' => array(
            '#title'       => t('Speakers / lecturers'),
        ),
        'parent_id' => array(
            '#title'       => t('Training'),
            '#options'     => scholar_generic_parent_options('training', true),
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
        'submit' => array(
            'title'  => empty($record) ? t('Save') : t('Save changes'),
            'cancel' => scholar_path('generics.training'),
        ),
    ), $record);

    return $form;
}

function _scholar_generics_class_form_process_values(&$values) // {{{
{
    $values['start_date'] = $values['time']['date'] . ' ' . $values['time']['start_time'];
    $values['end_date']   = $values['time']['date'] . ' ' . $values['time']['end_time'];
} // }}}

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
        _scholar_generics_theme_bib_authors($row['bib_authors'], ': ') . check_plain($row['title']),
        scholar_oplink(t('edit'), 'generics.class', 'edit/%d', $row['id']),
        scholar_oplink(t('delete'), 'generics.class', 'delete/%d', $row['id']),
    );
} // }}}

// vim: fdm=marker
