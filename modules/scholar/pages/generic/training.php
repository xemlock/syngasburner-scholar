<?php

function scholar_training_form(&$form_state, $record = null) // {{{
{
    if ($record) {
        $record->start_date = substr($record->start_date, 0, 10);
        $record->end_date   = substr($record->end_date, 0, 10);
    }

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

    // dodaj wylaczanie pola country jezeli w miejsce miejscowosci podano 'internet'
    drupal_add_js("$(function(){var f=$('#scholar-conference-form'),l=f.find('input[name=\"locality\"]'),c=f.find('select[name=\"country\"]'),d=function(){c[$.trim(l.val())=='internet'?'attr':'removeAttr']('disabled',true)};l.keyup(d);d()})", 'inline');

    /*
    $form['vtable']['presentations'] = array(
        '#type' => 'scholar_element_vtable_row',
        '#title' => t('Presentations'),
        '#description' => t('Change the order of presentations'),
    );*/

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => empty($record) ? t('Save') : t('Save changes'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_admin_path('presentation'),
    );

    return $form;
} // }}}

function _scholar_training_list_spec($row = null) // {{{
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
        l(t('edit'),  scholar_admin_path('training/edit/%d', $row['id'])),
        $row['child_count'] ? l(t('presentations (!count)', array('!count' => $row['child_count'])),  scholar_admin_path('training/children/%d/presentation', $row['id'])) : '',
        l(t('delete'), scholar_admin_path('training/delete/%d', $row['id'])),
    );
} // }}}
