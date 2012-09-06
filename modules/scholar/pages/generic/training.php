<?php

function scholar_generics_training_form(&$form_state, $record = null) // {{{
{
    $form = scholar_generic_form(array(
        '#id' => 'scholar-training-form',
        'title' => array(
            '#title' => t('Training title'),
            '#required' => true
        ),
        'start_date',
        'end_date',
        'suppinfo' => array(
            '#description' => t('Additional details about this training.'),
        ),
        scholar_element_separator(),
        'image_id',
        'files',
        'nodes',
        'events' => array(
            // dane poczatku i konca wydarzenia beda pobierane z danych szkolenia,
            // jezeli nie podano, a ma byc dolaczony event zglos blad
            'start_date' => false,
            'end_date'   => false,
        ),
    ), $record);

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => empty($record) ? t('Save') : t('Save changes'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_path('generics.training'),
    );

    return $form;
} // }}}

function _scholar_generics_training_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Start date'), 'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('End date'),   'field' => 'end_date'),
            array('data' => t('Title'),      'field' => 'title'),
            array('data' => t('Operations'), 'colspan' => '3'),
        );
    }

    return array(
        substr($row['start_date'], 0, (int) $row['start_date_len']),
        substr($row['end_date'], 0, (int) $row['end_date_len']),
        check_plain($row['title']),
        scholar_oplink(t('edit'), 'generics.training', 'edit/%d', $row['id']),
        scholar_oplink($row['child_count'] ? t('classes (!count)', array('!count' => $row['child_count'])) : t('classes'), 'generics.training', 'children/%d/class', $row['id']),
        scholar_oplink(t('delete'), 'generics.training', 'delete/%d', $row['id']),
    );
} // }}}

function scholar_generics_training_children_class_form(&$form_state, $training)
{
    $classes = scholar_generic_load_children($training->id, 'class', 'start_date');


    $form = array();
p($classes);
    return $form;
}

