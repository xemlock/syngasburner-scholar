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
        '#value' => scholar_admin_path('trainings/training'),
    );

    return $form;
} // }}}

function _scholar_generics_training_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Date'),     'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'),    'field' => 'title'),
            array('data' => t('Operations'), 'colspan' => '3'),
        );
    }

    return array(
        substr($row['start_date'], 0, 10),
        check_plain($row['title']),
        scholar_oplink(t('edit'), 'trainings/training/edit/%d', $row['id']),
        $row['child_count']
            ? scholar_oplink(t('presentations (!count)', array('!count' => $row['child_count'])), 'trainings/training/children/%d/presentation', $row['id'])
            : '',
        scholar_oplink(t('delete'), 'trainings/training/delete/%d', $row['id']),
    );
} // }}}
