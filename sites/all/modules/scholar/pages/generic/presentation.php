<?php

function scholar_generics_presentation_form(&$form_state, $record = null) // {{{
{
    // prezentacje moga nalezec do konferencji
    $categories = scholar_category_options('generics', 'presentation');

    // pusty tytul oznacza uczestnictwo w konferencji bez zadnego
    // wystapienia publicznego. Jezeli brak zdefiniowanych konferencji
    // ustaw pole tytulu jako wymagane.
    $form = scholar_generic_form(array(
        'title' => array(
            '#description' => t('Leave empty to mark conference attendance if no public presentation was given.'),
        ),
        'start_date' => array(
            '#title'       => t('Date'),
            '#attributes' => array('class' => 'scholar-datepicker'),
        ),
        'authors' => array(
            '#title'       => t('Authors'),
            '#required'    => true,
            '#description' => t('Remember about correct order, if there is more than one author or contributor.'),
        ),
        'parent_id' => array(
            '#title'       => t('Conference'),
            '#required'    => true,
            '#options'     => scholar_generic_parent_options('conference', true),
            '#description' => t('A conference during which this presentation was given.'),
            // jezeli w adresie strony podano identyfikator konferencji
            // ustaw ja jako domyslna wartosc pola
            '#default_value' => isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null,
        ),
        'category_id' => empty($categories) ? false : array(
            '#options'     => $categories,
            '#description' => t('Specify presentation type, e.g. speech, poster, etc.'),
        ),
        'suppinfo' => array(
            '#description' => t('Additional details about this presentation.'),
        ),
        'files',
        'nodes',
        'events' => array(
            // prezentacje odbywaja sie jednego dnia
            'start_date' => array(
                '#title' => t('Date'),
            ),
            'end_date'   => false,
        ),
        'submit' => array(
            'title'  => empty($record) ? t('Save') : t('Save changes'),
            'cancel' => scholar_path('generics.presentation'),
        ),
    ), $record);

    return $form;
} // }}}

function scholar_generics_presentation_form_validate($form, &$form_state) // {{{
{
    $values = $form_state['values'];

    $empty_title  = empty($values['title']) || ctype_space((string) $values['title']);
    $empty_parent = empty($values['parent_id']) || 0 == intval($values['parent_id']);

    if ($empty_title && $empty_parent) {
        form_set_error('title', t('Presentation title is required if no conference is chosen.'));
    }
} // }}}

function _scholar_generics_presentation_form_process_values(&$values) // {{{
{
    // jezeli pusty tytul, czyli obecnosc na konferencji bez wystapienia
    // publicznego, usun kategorie
    $values['start_date'] = substr($values['start_date'], 0, 10);
    $values['end_date']   = $values['start_date'];

    $values['title'] = trim($values['title']);

    if (empty($values['title'])) {
        $values['category_id'] = null;
    }
} // }}}

function _scholar_generics_presentation_list_row($row) // {{{
{
    if (empty($row)) {
        return array(
            array('data' => t('Date'),     'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Authors'),  'field' => 'bib_authors'),
            array('data' => t('Title'),    'field' => 'title'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    $title = trim($row['title']);

    return array(
        substr($row['start_date'], 0, 10),
        check_plain($row['bib_authors']),
        empty($title) ? '<em>' . t('attendance only') . '</em>' : check_plain($title),
        scholar_oplink(t('edit'), 'generics.presentation', 'edit/%d', $row['id']),
        scholar_oplink(t('delete'), 'generics.presentation', 'delete/%d', $row['id']),
    );
} // }}}

// vim: fdm=marker
