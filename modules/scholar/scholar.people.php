<?php

/*
 * Narzędzia do manipulowania rekordami osób
 *
 * @author xemlock
 * @version 2012-08-02
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
        drupal_set_message(t('Invalid person id supplied (%id)', array('%id' => $id)), 'error');
        drupal_goto(scholar_admin_path('people'));
        exit;        
    }

    return $record;
} // }}}

function scholar_save_person(&$person)
{
    if ($person->id) {
        
    
    }

    drupal_set_message($is_new
        ? t('Person created successfully')
        : t('Person updated successfully')
    );
}

/**
 * Usuwa z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param int $id
 */
function scholar_people_delete($id) // {{{
{
    scholar_delete_nodes($id, 'people');
    db_query("DELETE FROM {scholar_authors} WHERE person_id = %d", $id);
    db_query("DELETE FROM {scholar_people} WHERE id = %d", $id);
    variable_set('scholar_last_change', date('Y-m-d H:i:s'));
} // }}}

function scholar_people_form(&$form_state, $id = null) // {{{
{
    $row  = $id ? scholar_load_person($id, true) : null;
    p($row);

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
    ));

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => t('Save changes'),
    );

    // jezeli formularz dotyczy konkretnego rekordu ustaw domyslne wartosci pol
    if ($row) {
        scholar_populate_form($form, $row);
    }

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
    $row    = isset($form['#row']) ? $form['#row'] : null;
    $is_new = empty($row);
    $values = $form_state['values'];
    $nodes  = array();
    $langs  = scholar_languages();

    if ($row) {
        db_query(
            "UPDATE {scholar_people} SET first_name = '%s', last_name = '%s', image_id = '%s' WHERE id = %d",
            $values['first_name'],
            $values['last_name'],
            $values['image_id'],
            $row['id']
        );

    } else {
        db_query(
            "INSERT INTO {scholar_people} (first_name, last_name, image_id) VALUES ('%s', '%s', %d)",
            $values['first_name'],
            $values['last_name'],
            $values['image_id']
        );
        $row = $values;
        $row['id'] = db_last_insert_id('scholar_people', 'id');
    }

    // zapisz zalaczniki
    scholar_save_files($row['id'], 'people', $values['files']);

    // zapisz wezly, jezeli pusty tytul wstaw pelne imie i nazwisko
    foreach ($values['node'] as $code => $node) {
        $title = trim($node['title']);
        if (empty($title)) {
            $title = $values['first_name'] . ' ' . $values['last_name'];
        }
        $values['node'][$code]['title'] = $title;
        $values['node'][$code]['body'] = trim($values['node'][$code]['body']);
    }

    scholar_save_nodes($row['id'], 'people', $values['node']);

    // zapisz czas ostatniej zmiany danych
    scholar_last_change(time());

    drupal_goto('admin/scholar/people');
} // }}}

function scholar_people_form_validate($form, &$form_state) // {{{
{
    return true;
} // }}}

function scholar_people_delete_form(&$form_state, $id) // {{{
{
    $row = scholar_load_person($id, true);

    $form = array('#row' => $row);
    $form = confirm_form($form,
        t('Are you sure you want to delete person (%first_name %last_name)?', 
            array(
                '%first_name' => $row->first_name,
                '%last_name'  => $row->last_name,
            )
        ),
        'admin/scholar/people',
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    scholar_add_css();

    return $form;
} // }}}

function scholar_people_delete_form_submit($form, &$form_state) // {{{
{
    if ($row = $form['#row']) {
        scholar_delete_person($row);
        drupal_set_message(t(
            'Person deleted successfully (%first_name %last_name)',
            array(
                '%first_name' => $row['first_name'],
                '%last_name'  => $row['last_name'],
            )
        ));
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
