<?php

function scholar_people_form(&$form_state, $id = null) // {{{
{
    $record = $id ? scholar_load_record('people', $id, scholar_admin_path('people')) : null;

    $form = scholar_generic_form(array(
        'first_name' => array(
            '#required' => true,
        ),
        'last_name' => array(
            '#required' => true,
        ),
        'image_id' => array(
            '#title' => t('Photo'),
        ),
        'files',
        'nodes',
    ), $record);

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => t('Save changes'),
    );
    $form['cancel'] = array(
        '#type'     => 'scholar_element_cancel',
        '#value'    => scholar_admin_path('people'),
    );

    return $form;
} // }}}

/**
 * Zapisanie do bazy nowej osoby, lub modyfikacja istniejącej na
 * podstawie danych przesłanych w formularzu.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_people_form_submit($form, &$form_state) // {{{
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

    drupal_goto('admin/scholar/people');
} // }}}

function scholar_people_form_validate($form, &$form_state) // {{{
{
    return true;
} // }}}

function scholar_people_delete_form(&$form_state, $id) // {{{
{
    $record = scholar_load_record('people', $id, scholar_admin_path('people'));

    $form = array('#record' => $record);
    $form = confirm_form($form,
        t('Are you sure you want to delete person (%first_name %last_name)?', 
            array(
                '%first_name' => $record->first_name,
                '%last_name'  => $record->last_name,
            )
        ),
        scholar_admin_path('people'),
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    return $form;
} // }}}

function scholar_people_delete_form_submit($form, &$form_state) // {{{
{
    if ($record = $form['#record']) {
        scholar_delete_record('people', $record);

        $name = $record->first_name . ' ' . $record->last_name;
        drupal_set_message(t('%name deleted successfully.', array('%name' => $name)));
    }

    drupal_goto('admin/scholar/people');
} // }}}

/**
 * Lista osób.
 *
 * @return string
 */
function scholar_people_list() // {{{
{
    $header = array(
        array('data' => t('Last name'), 'field' => 'last_name', 'sort' => 'asc'),
        array('data' => t('First name'), 'field' => 'first_name'),
        array('data' => t('Operations'), 'colspan' => '2')
    );

    $query = db_query("SELECT * FROM {scholar_people}" . tablesort_sql($header));
    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['last_name']),
            check_plain($row['first_name']),
            l(t('edit'),   "admin/scholar/people/edit/{$row['id']}"), 
            l(t('delete'), "admin/scholar/people/delete/{$row['id']}"),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4),
        );
    }

    return theme('table', $header, $rows);
} // }}}

/**
 * Dostarcza rekordy osób do wybieralnej listy.
 *
 * @param array &$options OPTIONAL
 * @return array
 */
function scholar_people_itempicker(&$options = null) // {{{
{
    $options = array(
        'filterKey'    => 'full_name',
        'template'     => '{ full_name }',
        'emptyMessage' => t('No people found'),
    );
    $rows = array();

    $query = db_query("SELECT * FROM {scholar_people} ORDER BY last_name");
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
