<?php

/**
 * @param object &$record
 */
function scholar_generics_conference_form(&$form_state, $record = null) // {{{
{
    $categories = scholar_category_options('generics', 'conference');

    $form = scholar_generic_form(array(
        '#id' => 'scholar-conference-form',
        'title' => array(
            '#title' => t('Conference name'),
            '#required' => true
        ),
        'start_date',/* => array(
            '#required' => true,
        ),*/
        // FIXME NIE NIE NIEw przeciwienstwie do modulu events, date konca trzeba podac zawsze,
        // albo jawnie okreslic, ze wydarzenie nie ma sprecyzowanego konca
        'end_date', /* => array(
            '#required' => true,
            '#field_suffix' => ' <label><input type="checkbox" name="end_date" value="-1" ' . ($record && empty($record->end_date) ? ' checked="checked"' : '') . ' /> ' . t('It is a long-term event with an unspecified ending date.') . '</label>',
        ), */
        'locality' => array(
            '#description' => t('Name of city or village where this conference is held.'),
        ),
        'country' => array(
            // zezwol na pusta nazwe kraju
            '#options' => array_merge(array('0' => ''), scholar_countries()),
        ),
        'suppinfo' => array(
            '#description' => t('Additional details about this conference.'),
        ),
        scholar_element_separator(),
        'category_id' => empty($categories) ? false : array(
            '#options' => $categories,
        ),
        'list' => array(
            '#type' => 'checkbox',
            // Uwzględnij prezentacje z tej konferencji przy automatycznym tworzeniu list
            '#title' => t('Include presentations from this conference in auto-generated lists'),
            // Ustawienie to dotyczy stron osób oraz strony z wystąpieniami na konferencjach.
            '#description' => t('This setting applies to person pages and conference presentations page.'),
            '#default_value' => true,
        ),
        scholar_element_separator(),
        'image_id',
        'url',
        'files',
        'nodes',
        'events' => array(
            // dane poczatku i konca wydarzenia beda pobierane z danych konferencji
            'start_date' => false,
            'end_date'   => false,
        ),
        'submit' => array(
            'title'  => empty($record) ? t('Save') : t('Save changes'),
            'cancel' => scholar_path('generics.conference'),
        ),
    ), $record);

    // dodaj wylaczanie pola country jezeli w miejsce miejscowosci podano 'internet'
    //drupal_add_js("$(function(){var f=$('#scholar-conference-form'),l=f.find('input[name=\"locality\"]'),c=f.find('select[name=\"country\"]'),d=function(){c[$.trim(l.val())=='internet'?'attr':'removeAttr']('disabled',true)};l.keyup(d);d()})", 'inline');

    /*
    $form['vtable']['presentations'] = array(
        '#type' => 'scholar_element_vtable_row',
        '#title' => t('Presentations'),
        '#description' => t('Change the order of presentations'),
    );*/

    _scholar_generics_conference_tabs($record);

    return $form;
} // }}}

function _scholar_generics_conference_tabs($record) // {{{
{
    if ($record) {
        $query = 'destination=' . $_GET['q'] . '&parent_id=' . $record->id;
        scholar_add_tab(t('Edit'), scholar_path('generics.conference', 'edit/%d', $record->id), $query);
        scholar_add_tab(t('Add presentation'), scholar_path('generics.presentation', 'add'), $query);
        scholar_add_tab(t('Presentations'), scholar_path('generics.conference', 'children/%d/presentation', $record->id));
        scholar_add_tab(t('List'), scholar_path('generics.conference'));
    }
} // }}}

function _scholar_generics_conference_form_process_values(&$values) // {{{
{
    // jezeli zaznaczono, ze konferencja ma nieokreslona date zakonczenia
    // (podano wartosc ujemna), ustaw jej date konca na NULL
    if ($values['end_date'] < 0) {
        $values['end_date'] = null;
    }

    if ($values['country'] == '0') {
        $values['country'] = null;
    }

    // dodaj czas do eventow
    foreach ($values['events'] as $language => &$event) {
        $title = trim($event['title']);
        if (0 == strlen($title)) {
            $title = $values['title'];
        }
        $event['title']      = $title;
        $event['start_date'] = $values['start_date'];
        $event['end_date']   = $values['end_date'];
        $event['language']   = $language;
        $event['image_id']   = $values['image_id'];
    }
} // }}}

function _scholar_generics_conference_list_row($row) // {{{
{
    if (empty($row)) {
        return array(
            array('data' => t('Date'),     'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'),    'field' => 'title'),
            array('data' => t('Country'),  'field' => 'country_name'),
            array('data' => t('Listed')),
            array('data' => t('Operations'), 'colspan' => '3'),
        );
    }

    return array(
        substr($row['start_date'], 0, (int) $row['start_date_len']), // TODO scholar_format_date
        check_plain($row['title']),
        check_plain($row['country_name']),
        $row['list'] ? t('Yes') : t('No'),
        scholar_oplink(t('edit'), 'generics.conference', 'edit/%d', $row['id']),
        scholar_oplink($row['child_count'] ? t('details (!count)', array('!count' => $row['child_count'])) : t('details'), 'generics.conference', 'details/%d?', $row['id']),
        scholar_oplink(t('delete'), 'generics.conference', 'delete/%d', $row['id']),
    );
} // }}}

function _scholar_generics_conference_details_row($row) // {{{
{
    if (empty($row)) {
        return array(
            scholar_tabledrag_handle(),
            t('Title'),
            t('Weight'),
            array('data' => t('Operations'), 'colspan' => 2),
        );
    }

    $region =substr($row['start_date'], 0, 10);

    return array(
        'region' => $region,
        'data' => array(
            scholar_tabledrag_handle(), // miejsce na uchwyt tabledraga
            // daty nie trzeba pokazywac, bo jest w regionie
            _scholar_generics_theme_bib_authors($row['bib_authors'], ': ') . check_plain($row['title']),
            '@weight',
            scholar_oplink(t('edit'), 'generics.presentation', 'edit/%d', $row['id']),
            scholar_oplink(t('delete'), 'generics.presentation', 'delete/%d', $row['id']),
        ),
    );
} // }}}

/**
 * Strona z listą wszystkich prezentacji podpiętych do danej
 * konferencji. Daje możliwość sortowania prezentacji.
 *
 * @param object $record
 */
function scholar_generics_conference_details_form(&$form_state, $record) // {{{
{
    $children = scholar_generic_load_children($record->id, 'presentation', 'start_date, weight');

    $form = array(
        '#record' => $record,
    );

    $form['properties'] = array(
        '#type' => 'fieldset',
        '#title' => t('Conference properties'),
        '#attributes' => array('class' => 'scholar'),
        '#collapsible' => true,
        '#collapsed' => false,
    );

    $location = array();

    if ($locality = $record->locality) {
        $location[] = t($locality);
    }

    if ($country = scholar_countries($record->country)) {
        $location[] = $country;
    }

    $no_value = '<em>' . t('Not specified') . '</em>';

    $dl = array(
        t('Title'),      check_plain($record->title),
        t('Start date'), $record->start_date ? scholar_format_date($record->start_date) : $no_value,
        t('End date'),   $record->end_date ? scholar_format_date($record->end_date) : $no_value,
        t('Location'),   $location ? check_plain(implode(', ', $location)) : $no_value,
    );

    if ($record->url) {
        $dl[] = t('Website');
        $dl[] = l($record->url, $record->url);
    }

    $user = user_load((int) $record->user_id);
    $dl[] = t('Created');
    $dl[] = t('!time, by !user', array(
                '!time' => $record->create_time,
                '!user' => '<em>' . ($user ? l($user->name, 'user/' . $user->uid) : t('unknown user')) . '</em>',
            ));


    $form['properties'][] = array(
        '#type' => 'markup',
        '#value' => scholar_theme_dl($dl) 
    );

    if ($children) {
        $form[] = array(
            '#type'  => 'markup',
            '#value' => '<div class="help">' . t('Here you can change the order of presentations in this conference. You can move presentations by dragging-and-dropping them to a new position.') . '</div>',
        );
        scholar_generics_weight_form($form,
            $children, '_scholar_generics_conference_details_row', true);
    }

    _scholar_generics_conference_tabs($record);

    return $form;
} // }}}

// vim: fdm=marker
