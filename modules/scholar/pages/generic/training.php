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
        'submit' => array(
            'title' => empty($record) ? t('Save') : t('Save changes'),
            'cancel' => scholar_path('generics.training'),
        ),
    ), $record);

    _scholar_generics_training_tabs($record);

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

function _scholar_generics_training_children_class_form($row) // {{{
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
function scholar_generics_training_children_class_form(&$form_state, $record) // {{{
{
    $form = array('#record' => $record);

    $no_value = '<em>' . t('Not specified') . '</em>';

    $dl = array(
        t('Title'),      check_plain($record->title),
        t('Start date'), $record->start_date ? scholar_format_date($record->start_date) : $no_value,
        t('End date'),   $record->end_date ? scholar_format_date($record->end_date) : $no_value,
    );

    if ($conference->url) {
        $dl[] = t('Website');
        $dl[] = l($conference->url, $conference->url);
    }

    $form[] = array(
        '#type'        => 'fieldset',
        '#title'       => t('Training properties'),
        '#attributes'  => array('class' => 'scholar'),
        '#collapsible' => true,
        '#collapsed'   => false,
        array(
            '#type'  => 'markup',
            '#value' => scholar_theme_dl($dl),
        ),
    );
    $form[] = array(
        '#type' => 'markup',
        '#value' => '<div class="help">' . t('Here you can change the order of classes in this training. You can move classes by dragging-and-dropping them to a new location. Only classes without specified start time are movable.') . '</div>',
    );

    $children = scholar_generic_load_children($record->id, 'class', 'start_date, weight');
    scholar_generics_weight_form($form,
        $children, '_scholar_generics_training_children_class_form', true);

    _scholar_generics_training_tabs($record);
    drupal_set_title(t('Training'));

    return $form;
} // }}}

function _scholar_generics_training_tabs($record) // {{{
{
    if ($record) {
        $query = 'destination=' . $_GET['q'] . '&parent_id=' . $record->id;
        scholar_add_tab(t('Edit'), scholar_path('generics.training', 'edit/%d', $record->id), $query);
        scholar_add_tab(t('Add class'), scholar_path('generics.class', 'add'), $query);
        scholar_add_tab(t('Classes'), scholar_path('generics.training', 'children/%d/class', $record->id));
        scholar_add_tab(t('List'), scholar_path('generics.training'));
    }
} // }}}

// vim: fdm=marker
