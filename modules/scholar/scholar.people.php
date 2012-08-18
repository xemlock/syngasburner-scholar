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
 */
function scholar_people_fetch_row($id, $redirect = false) // {{{
{
    $query = db_query('SELECT * FROM {scholar_people} WHERE id = %d', $id);
    $row   = db_fetch_array($query);

    if (empty($row) && $redirect) {
        drupal_set_message(t('Invalid person id supplied (%id)', array('%id' => $id)), 'error');
        drupal_goto(scholar_admin_path('people'));
        exit;
    }

    return $row;
} // }}}

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
    $row  = $id ? scholar_people_fetch_row($id, true) : null;
    $form = array('#row' => $row);

    // ustawienia osoby
    $form['id'] = array(
        '#type'     => 'hidden',
    );
    $form['first_name'] = array(
        '#type'     => 'textfield',
        '#title'    => t('First name'),
        '#required' => true,
    );
    $form['last_name'] = array(
        '#type'     => 'textfield',
        '#title'    => t('Last name'),
        '#required' => true,
    );

    $form['image_id'] = array(
        '#type'     => 'gallery_image_select',
        '#title'    => t('Photo'),
    );

    // link do wezlow zalezne od jezyka, ustawienia aliasu
    $languages = scholar_languages();
    $default_lang = language_default('language');

    $form['attachments'] = array(
        '#type' => 'fieldset',
        '#title' => t('File attachments'),
//        '#collapsible' => true, // collapsible psuje ukrywanie kolumny z waga
//        '#collapsed' => true,
    );
    $form['attachments']['files'] = array(
        '#type' => 'scholar_attachment_manager',
        '#default_value' => $row
                            ? scholar_fetch_files($row['id'], 'people')
                            : null
                        );

    $form['node'] = scholar_nodes_subform($row, 'people');

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => t('Save changes'),
    );

    // jezeli formularz dotyczy konkretnego rekordu ustaw domyslne wartosci pol
    if ($row) {
        foreach ($row as $column => $value) {
            if (isset($form[$column])) {
                $form[$column]['#default_value'] = $value;
            }
        }
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

    drupal_set_message($is_new
        ? t('Person created successfully')
        : t('Person updated successfully')
    );
    drupal_goto('admin/scholar/people');
} // }}}

function scholar_people_form_validate($form, &$form_state) // {{{
{
    return true;
} // }}}

function scholar_people_delete_form(&$form_state, $id) // {{{
{
    $row = scholar_people_fetch_row($id, true);

    $form = array('#row' => $row);
    $form = confirm_form($form,
        t('Are you sure you want to delete person (%first_name %last_name)?', 
            array(
                '%first_name' => $row['first_name'],
                '%last_name'  => $row['last_name'],
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
        scholar_people_delete($row['id']);
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
        'filterKey'    => 'fn',
        'template'     => '{ fn }',
        'emptyMessage' => t('No people found'),
    );
    $rows = array();

    $query = db_query("SELECT * FROM {scholar_people} ORDER BY last_name");
    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            'id' => $row['id'],
            'fn' => $row['last_name'] . ' ' . $row['first_name'],
        );
    }

    return $rows;
} // }}}

// vim: fdm=marker
