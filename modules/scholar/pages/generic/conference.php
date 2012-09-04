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
        'start_date' => array(
            '#required' => true,
        ),
        // w przeciwienstwie do modulu events, date konca trzeba podac zawsze,
        // albo jawnie okreslic, ze wydarzenie nie ma sprecyzowanego konca
        'end_date' => array(
            '#required' => true,
            '#field_suffix' => ' <label><input type="checkbox" name="end_date" value="-1" ' . ($record && empty($record->end_date) ? ' checked="checked"' : '') . ' /> ' . t('It is a long-term event with an unspecified ending date.') . '</label>',
        ),
        'locality' => array(
            '#description' => t('Name of city or village where this conference is held.'),
        ),
        'country',
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
        '#value' => scholar_list_path('generics.conference'),
    );

    return $form;
} // }}}

function _scholar_generics_conference_form_process_values(&$values) // {{{
{
    // data poczatku i konca maja obcieta czesc zwiazana z czasem,
    // trzeba ja dodac aby byla poprawna wartoscia DATETIME
    $values['start_date'] .= ' 00:00:00';

    // jezeli zaznaczono, ze konferencja ma nieokreslona date zakonczenia
    // (podano wartosc ujemna), ustaw jej date konca na NULL
    if ($values['end_date'] < 0) {
        $values['end_date'] = null;
    }

    if (strlen($values['end_date'])) {
        $values['end_date'] .= ' 00:00:00';
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

function _scholar_generics_conference_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Date'),     'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'),    'field' => 'title'),
            array('data' => t('Country'),  'field' => 'country_name'),
            array('data' => t('Listed')),
            array('data' => t('Operations'), 'colspan' => '3'),
        );
    }

    return array(
        substr($row['start_date'], 0, 10),
        check_plain($row['title']),
        check_plain($row['country_name']),
        $row['list'] ? t('Yes') : t('No'),
        l(t('edit'),  scholar_admin_path('conference/edit/%d', $row['id'])),
        $row['child_count'] ? l(t('presentations (!count)', array('!count' => $row['child_count'])),  scholar_admin_path('conference/children/%d/presentation', $row['id'])) : '',
        l(t('delete'), scholar_admin_path('conference/delete/%d', $row['id'])),
    );
} // }}}

/**
 * Wywołuje formularz {@see scholar_conference_children_presentation_form}.
 *
 * @param object $record
 */
function scholar_generics_conference_children_presentation_list($record) // {{{
{
    return scholar_render_form('scholar_generics_conference_children_presentation_form', $record);
} // }}}

/**
 * Strona z listą wszystkich prezentacji podpiętych do danej
 * konferencji. Daje możliwość sortowania prezentacji.
 *
 * @param array &$form_state
 * @param object $conference
 */
function scholar_generics_conference_children_presentation_form(&$form_state, $conference) // {{{
{
    drupal_set_title(t('Conference presentations'));

    $presentations = scholar_generic_load_children($conference->id, 'presentation', 'start_date, weight');

    $form = array(
        'weight' => array(
            '#tree' => true,
        ),
    );

    $weight_options = array();
    $delta = 10;
    for ($i = -$delta; $i <= $delta; ++$i) {
        $weight_options[$i] = $i;
    }

    $subgroups = array();
    $d = array('query' => 'destination=' . scholar_admin_path('conference/children/%d/presentation', $conference->id));

    $tbody[] = array();
    $last_region = ''; // pierwszy region to ten bez daty
    foreach ($presentations as $row) {
        $form['weight'][$row['id']] = array(
            '#type' => 'hidden',
            '#default_value' => $row['weight'],
        );

        $subgroup = str_replace('-', '', substr($row['start_date'], 0, 10));

        if ($subgroup !== $last_region) {
            $rows[] = array(
                'data' => array(
                    array(
                        'data' => $subgroup,
                        'colspan' => 5,
                        'class' => 'region',
                    ),
                ),
                'class' => 'region',
            );
            $last_region = $subgroup;
        }

        if (strlen($subgroup)) {
            $subgroup = 'scholar-tbody-' . $subgroup;
        } else {
            $subgroup = 'scholar-tbody';
        }

        $subgroups[$subgroup] = true;

        $element = array(
            '#type' => 'select',
            '#attributes' => array('class' => 'tr-weight'),
            '#options' => $weight_options,
            '#parents' => array('weight', $row['id']),
            '#value' => $row['weight'],
            '#name' => 'weight[' . $row['id'] . ']',
            '#id' => 'weight-' . $row['id'],
        );

        $element['#type'] = 'hidden';
        
        $rows[] = array(
            'data' => array(
                check_plain($row['bib_authors']),
                check_plain($row['title']),
                theme_select($element),
                l(t('edit'),  scholar_admin_path('presentation/edit/' . $row['id']), $d),
                l(t('delete'), scholar_admin_path('presentation/delete/' . $row['id']), $d),
            ),
            'class' => 'draggable',
        );
    }

    $header = array(
        t('Authors'),
        t('Title'),
        t('Weight'),
        array('data' => t('Operations'), 'colspan' => 2),
    );

    // tabledrag totalnie nie dziala gdy jest wiecej niz jedno tbody
    drupal_add_tabledrag('scholar-conference-presentations', 'order', 'sibling', 'tr-weight');

    $form['#record'] = $conference;
    $form['properties'] = array(
        '#type' => 'fieldset',
        '#title' => t('Conference properties'),
        '#attributes' => array('class' => 'scholar'),
        '#collapsible' => true,
        '#collapsed' => true,
    );

    $location = trim($conference->locality);
    if (strcasecmp($location, 'internet')) {
        $country = scholar_countries($conference->country);
        if ($country) {
            $location .= ' (' . $country . ')';
        }
        $location = check_plain($location);
    } else {
        $location = '<em>internet</em>';
    }

    $form['properties'][] = array(
        '#type' => 'markup',
        '#value' => scholar_theme_dl(array(
            t('Title'),      check_plain($conference->title),
            t('Start date'), scholar_format_date($conference->start_date),
            t('End date'),   $conference->end_date ? scholar_format_date($conference->end_date) : ('<em>' . t('Not specified') . '</em>'),
            t('Location'),   $location,
        )),
    );
    $form[] = array(
        '#type' => 'markup',
        '#value' => 
            '<div class="help">' . t('Here you can change the order of presentations in this conference. You can move presentations by dragging-and-dropping them to a new location.') . '</div>' .
            scholar_theme_table($header, $rows, array('id' => 'scholar-conference-presentations', 'class' => 'region-locked')),
    );

    $form[] = array(
        '#type' => 'submit',
        '#value' => t('Save changes'),
    );

    scholar_add_tab(t('Add presentation'), scholar_admin_path('presentation/add'), $d['query'] . '&conference=' . $conference->id);
    scholar_add_tab(t('Edit'), scholar_admin_path('conference/edit/' . $conference->id));
    scholar_add_tab(t('List'), scholar_admin_path('conference'));

    return $form;
} // }}}

function scholar_generics_conference_children_presentation_form_submit($form, &$form_state) // {{{
{
    if ($form['#record']) {
        $record = $form['#record'];
        $values = $form_state['values'];

        if (scholar_generic_update_children_weights($record->id, (array) $values['weight'])) {
            drupal_set_message(t('Presentation order updated successfully.'));
        }

        drupal_goto(scholar_admin_path('conference'));
    }
} // }}}

