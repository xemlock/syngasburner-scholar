<?php

function scholar_pages_people_form(&$form_state, $id = null) // {{{
{
    $record = $id ? scholar_load_record('people', $id, scholar_path('people')) : null;
    $categories = scholar_category_options('people');

    $form = scholar_generic_form(array(
        'first_name' => array(
            '#required' => true,
        ),
        'last_name' => array(
            '#required' => true,
        ),
        'category_id' => empty($categories) ? false : array(
            '#title' => t('Affiliation'),
            '#options' => $categories,
            '#description' => t('Name of a scientific institution this person is primarily affiliated with.'),
        ),
        'image_id' => array(
            '#title' => t('Photo'),
        ),
        'files',
        'nodes',
        'submit' => array(
            'title' => empty($record) ? t('Save') : t('Save changes'),
            'cancel' => scholar_path('people'),
        ),
    ), $record);

    return $form;
} // }}}

/**
 * Zapisanie do bazy nowej osoby, lub modyfikacja istniejącej na
 * podstawie danych przesłanych w formularzu.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_pages_people_form_submit($form, &$form_state) // {{{
{
    $values = $form_state['values'];
    $is_new = empty($form['#record']);
    $record = $is_new ? new stdClass : $form['#record'];

    // jezeli wezly maja pusty tytul wstaw pelne imie i nazwisko
    foreach ($values['nodes'] as $language => &$node) {
        $title = trim($node['title']);
        if (empty($title)) {
            $title = $values['first_name'] . ' ' . $values['last_name'];
        }
        $node['title'] = $title;
    }
    unset($node);

    scholar_populate_record($record, $values);

    if (scholar_save_record('people', $record)) {
        $name = $record->first_name . ' ' . $record->last_name;
        drupal_set_message($is_new
            ? t('%name created successfully.', array('%name' => $name))
            : t('%name updated successfully.', array('%name' => $name))
        );
    }

    drupal_goto(scholar_path('people'));
} // }}}

function scholar_pages_people_form_validate($form, &$form_state) // {{{
{
    return true;
} // }}}

function scholar_pages_people_delete_form(&$form_state, $id) // {{{
{
    $record = scholar_load_record('people', $id, scholar_path('people'));

    $form = array('#record' => $record);
    $form = confirm_form($form,
        t('Are you sure you want to delete person (%first_name %last_name)?', 
            array(
                '%first_name' => $record->first_name,
                '%last_name'  => $record->last_name,
            )
        ),
        scholar_path('people'),
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    return $form;
} // }}}

function scholar_pages_people_delete_form_submit($form, &$form_state) // {{{
{
    if ($record = $form['#record']) {
        scholar_delete_record('people', $record);

        $name = $record->first_name . ' ' . $record->last_name;
        drupal_set_message(t('%name deleted successfully.', array('%name' => $name)));
    }

    drupal_goto(scholar_path('people'));
} // }}}

/**
 * Lista osób.
 *
 * @return string
 */
function scholar_pages_people_list() // {{{
{
    $header = array(
        array('data' => t('Name'),        'field' => 'last_name', 'sort' => 'asc'),
        array('data' => t('Affiliation'), 'field' => 'category_name'),
        array('data' => t('Operations'),  'colspan' => '2')
    );

    $query = scholar_people_recordset(null, $header);
    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['last_name'] . ' ' . $row['first_name']),
            check_plain($row['category_name']),
            scholar_oplink(t('edit'), 'people', 'edit/%d', $row['id']),
            scholar_oplink(t('delete'), 'people', 'delete/%d', $row['id']),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4),
        );
    }

    return theme_scholar_table($header, $rows);
} // }}}

/**
 * Dostarcza rekordy osób do wybieralnej listy.
 *
 * @param array &$options OPTIONAL
 * @return array
 */
function scholar_pages_people_itempicker(&$options = null) // {{{
{
    $options = array(
        'filterKey'    => 'full_name',
        'template'     => '{ full_name }',
        'emptyMessage' => t('No people found'),
    );

    $rows  = array();
    $query = scholar_people_recordset(null, 'last_name');

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            'id'         => $row['id'],
            'full_name'  => $row['last_name'] . ' ' . $row['first_name'],
            'first_name' => $row['first_name'],
            'last_name'  => $row['last_name'],
        );
    }

    return $rows;
} // }}}

// vim: fdm=marker
