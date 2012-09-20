<?php

function scholar_generics_training_form(&$form_state, $record = null) // {{{
{
    $categories = scholar_category_options('generics', 'training');

    $form = scholar_generic_form(array(
        'title' => array(
            '#title' => t('Training title'),
            '#required' => true
        ),
        scholar_form_tablerow_open(),
        'start_date',
        scholar_form_tablerow_next(),
        'end_date',
        scholar_form_tablerow_close(),
        'suppinfo' => array(
            '#description' => t('Additional details about this training.'),
        ),
        'list' => array(
            '#type' => 'checkbox',
            '#title' => t('Include this training in auto-generated lists'),
            // Ustawienie to dotyczy stron osób oraz strony z wystąpieniami na konferencjach.
            '#description' => t('This setting applies to trainings page.'),
            '#default_value' => true,
        ),
        scholar_element_separator(),
        'category_id' => empty($categories) ? false : array(
            '#options' => $categories,
        ),
        'image_id',
        'files',
        'nodes',
        'events' => array(
            // dane poczatku i konca wydarzenia beda pobierane z danych szkolenia,
            // jezeli nie podano, a ma byc dolaczony event zglos blad
            'start_date' => false,
            'end_date'   => false,
        ),
        'submit' => array(
            'title' => empty($record) ? t('Save') : t('Save changes'),
            'cancel' => scholar_path('generics.training'),
        ),
    ), $record);

    _scholar_generics_training_tabs($record);

    return $form;
} // }}}

function _scholar_generics_training_list_row($row) // {{{
{
    if (empty($row)) {
        return array(
            array('data' => t('Date'),       'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'),      'field' => 'title'),
            array('data' => t('Listed'),     'field' => 'list'),
            array('data' => t('Operations'), 'colspan' => '3'),
        );
    }

    $start_date = substr($row['start_date'], 0, (int) $row['start_date_len']);
    $end_date = substr($row['end_date'], 0, (int) $row['end_date_len']);

    return array(
        $start_date . ($end_date ? ' &ndash; ' . $end_date : ''),
        check_plain($row['title']),
        intval($row['list']) ? t('Yes') : t('No'),
        scholar_oplink(t('edit'), 'generics.training', 'edit/%d', $row['id']),
        scholar_oplink($row['child_count'] ? t('details (!count)', array('!count' => $row['child_count'])) : t('details'), 'generics.training', 'details/%d?', $row['id']),
        scholar_oplink(t('delete'), 'generics.training', 'delete/%d', $row['id']),
    );
} // }}}

function _scholar_generics_training_details_row($row) // {{{
{
    if (empty($row)) {
        return array(
            scholar_tabledrag_handle(), // uchwyt tabledraga
            t('Time'),
            t('Title'),
            t('Weight'),
            array('data' => t('Operations'), 'colspan' => 2),
        );
    }

    $region = substr($row['start_date'], 0, 10);

    $start_date = substr($row['start_date'], 0, (int) $row['start_date_len']);
    $start_time = substr($start_date, 11, 5);
    $end_date = substr($row['end_date'], 0, (int) $row['end_date_len']);
    $end_time = substr($end_date, 11, 5);    
    
    return array(
        'region' => $region,
        'data' => array(
            scholar_tabledrag_handle(),
            $start_time . ($end_time ? ' &ndash; ' . $end_time : ''),
            _scholar_generics_theme_bib_authors($row['bib_authors'], ': ') . check_plain($row['title'])
                . ($row['category_name'] ? ' (' . $row['category_name'] . ')' : ''),
            $start_time ? '' : '@weight',
            scholar_oplink(t('edit'), 'generics.class', 'edit/%d', $row['id']),
            scholar_oplink(t('delete'), 'generics.class', 'delete/%d', $row['id']),
        ),
    );
} // }}}

/**
 * Lista zajęć w obrębie szkolenia, pogrupowanych w/g dnia, posortowanych
 * rosnąco po czasie, bez możliwości zmian wagi.
 *
 * @param object $record
 *     obiekt reprezentujący rekord szkolenia
 * @return array
 */
function scholar_generics_training_details_form(&$form_state, $record) // {{{
{
    $form = array('#record' => $record);

    $no_value = '<em>' . t('Not specified') . '</em>';

    $dl = array(
        t('Title'),      check_plain($record->title),
        t('Start date'), $record->start_date ? scholar_format_date($record->start_date) : $no_value,
        t('End date'),   $record->end_date ? scholar_format_date($record->end_date) : $no_value,
    );

    if ($record->url) {
        $dl[] = t('Website');
        $dl[] = l($record->url, $record->url);
    }

    if ($record->nodes) {
        $dl[] = t('Tag');
        $dl[] = '<code>[node]training.' . $record->id . '[/node]</code>';
    }

    $user = user_load((int) $record->user_id);
    $dl[] = t('Created');
    $dl[] = t('!time, by !user', array(
                '!time' => date('Y-m-d H:i:s', $record->create_time),
                '!user' => '<em>' . ($user ? l($user->name, 'user/' . $user->uid) : t('unknown user')) . '</em>',
            ));

    $form[] = scholar_form_fieldset(array(
        '#title'       => t('Training properties'),
        '#attributes'  => array('class' => 'scholar'),
        '#collapsible' => true,
        '#collapsed'   => false,
        array(
            '#type'  => 'markup',
            '#value' => theme_scholar_dl($dl),
        ),
    ));

    $children = scholar_generic_load_children($record->id, 'class', 'start_date, weight');

    if ($children) {
        $form[] = array(
            '#type' => 'markup',
            '#value' => '<div class="help">' . t('Here you can change the order of classes in this training. You can move classes by dragging-and-dropping them to a new position. Only classes without specified start time are movable.') . '</div>',
        );
        scholar_generics_weight_form($form,
            $children, '_scholar_generics_training_details_row', true);
    }

    _scholar_generics_training_tabs($record);

    return $form;
} // }}}

function _scholar_generics_training_tabs($record) // {{{
{
    if ($record) {
        $query = 'destination=' . $_GET['q'] . '&parent_id=' . $record->id;
        scholar_add_tab(t('Edit'), scholar_path('generics.training', 'edit/%d', $record->id), $query);
        scholar_add_tab(t('Add class'), scholar_path('generics.class', 'add'), $query);
        scholar_add_tab(t('Details'), scholar_path('generics.training', 'details/%d', $record->id));
        scholar_add_tab(t('Back to training list'), scholar_path('generics.training'));
    }
} // }}}

// vim: fdm=marker
