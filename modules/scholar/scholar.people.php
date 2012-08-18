<?php

/*
 * Narzędzia do manipulowania rekordami osób
 *
 * @author xemlock
 * @version 2012-08-19
 */

/**
 * Pobiera z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param int $id               identyfikator osoby
 * @param bool $redirect        czy zgłosić błąd i przekierować do listy
 *                              osób, jeżeli osoba nie została znaleziona
 * @return object
 */
function scholar_load_person($id, $redirect = false) // {{{
{
    $query = db_query('SELECT * FROM {scholar_people} WHERE id = %d', $id);
    $record = db_fetch_object($query);

    if ($record) {
        // pobierz powiazane wezly i pliki
        $record->files = scholar_fetch_files($record->id, 'people');
        $record->nodes = scholar_fetch_nodes($record->id, 'people');
    
    } elseif ($redirect) {
        drupal_set_message(t('Invalid person identifier supplied (%id)', array('%id' => $id)), 'error');
        drupal_goto(scholar_admin_path('people'));
        exit;        
    }

    return $record;
} // }}}

/**
 * @param object &$person
 * @return bool
 */
function scholar_save_person(&$person) // {{{
{
    if (empty($person->id)) {
        $is_new = true;
        $success = scholar_db_write_record('scholar_people', $person);
    } else {
        $is_new = false;
        $success = scholar_db_write_record('scholar_people', $person, 'id');
    }

    if ($success) {
        scholar_save_files($person->id, 'people', $person->files);
        scholar_save_nodes($person->id, 'people', $person->nodes);

        $name = $person->first_name . ' ' . $person->last_name;
        drupal_set_message($is_new
            ? t('Person %name created successfully', array('%name' => $name))
            : t('Person %name updated successfully', array('%name' => $name))
        );
    }

    return $success;
} // }}}

/**
 * Usuwa z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param object &$person
 */
function scholar_delete_person(&$person) // {{{
{
    scholar_delete_nodes($person->id, 'people');
    db_query("DELETE FROM {scholar_authors} WHERE person_id = %d", $person->id);
    db_query("DELETE FROM {scholar_people} WHERE id = %d", $person->id);

    $person->id = null;

    $name = $person->first_name . ' ' . $person->last_name;
    drupal_set_message(t('Person deleted successfully (%name)', array('%name' => $name)));

    variable_set('scholar_last_change', date('Y-m-d H:i:s'));
} // }}}

function scholar_people_form(&$form_state, $id = null) // {{{
{
    $record = $id ? scholar_load_person($id, true) : null;

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
    $record = empty($form['#record']) ? new stdClass : $form['#record'];

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
    scholar_save_person($record);

    drupal_goto('admin/scholar/people');
} // }}}

function scholar_people_form_validate($form, &$form_state) // {{{
{
    return true;
} // }}}

function scholar_people_delete_form(&$form_state, $id) // {{{
{
    $record = scholar_load_person($id, true);

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
        scholar_delete_person($record);
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
