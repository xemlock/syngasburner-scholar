<?php

/**
 * @param int $id
 * @param null|string $table_name
 * @param null|string $subtype
 * @return object
 */
function scholar_fetch_category($id, $table_name = false, $subtype = false, $redirect = null) // {{{
{
    $where = array('id' => $id);

    if (false !== $table_name) {
        $where['table_name'] = $table_name;
    }

    // null jest poprawna wartoscia dla podtypu, stad uzycie false
    if (false !== $subtype) {
        $where['subtype'] = $subtype;
    }

    $query = db_query("SELECT * FROM {scholar_categories} WHERE " . scholar_db_where($where));
    $record = db_fetch_object($query);

    if ($record) {
        $names = array();

        // przygotuj miejsce dla nazw kategorii we wszystkich dostepnych jezykach
        foreach (scholar_languages() as $code => $name) {
            $names[$code] = null;
        }

        // pobierz dostepne nazwy kategorii
        $query = db_query("SELECT * FROM {scholar_category_names} WHERE category_id = %d", $record->id);
        while ($row = db_fetch_array($query)) {
            $names[$row['language']] = $row['name'];
        }

        $record->names = $names;

    } elseif (strlen($redirect)) {
        drupal_set_message(t('Invalid category identifier supplied (%id)', array('%id' => $id)), 'error');
        drupal_goto($redirect);
        exit;
    }

    return $record;
} // }}}

/**
 * @param object &$category
 */
function scholar_save_category(&$category) // {{{
{
    if (empty($category->id)) {
        $new = true;
        $sql = "INSERT INTO {scholar_categories} (table_name, subtype) VALUES (" 
             . scholar_db_quote($category->table_name) . ", "
             . scholar_db_quote($category->subtype) . ")";
        db_query($sql);

        $category->id = db_last_insert_id('scholar_categories', 'id');

    } else {
        $new = false;
    }

    // zapisz nazwy
    foreach ($category->names as $language => $name) {
        db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d AND language = '%s'", $category->id, $language);
        db_query("INSERT INTO {scholar_category_names} (category_id, name, language) VALUES (%d, '%s', '%s')", $category->id, $name, $language);
    }

    drupal_set_message($new ? t('Category was added successfully') : t('Category was updated successfully'));
} // }}}

/**
 * Usuwa kategorię. Efektem ubocznym funkcji jest ustawienie komunikatu
 * o pomyślnym usunięciu rekordu.
 *
 * @param object &$category
 */
function scholar_delete_category(&$category) // {{{
{
    global $language;

    db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d", $category->id);
    db_query("DELETE FROM {scholar_categories} WHERE id = %d", $category->id);

    $category->id = null;

    drupal_set_message(t('Category deleted successfully (%name)', array('%name' => $category->names[$language->language])));
} // }}}

/**
 * Zwraca ścieżkę do listy kategorii powiązanych z daną tabelą i opcjonalnie
 * rekordami danego podtypu. Reguła tworzenia ścieżki jest następująca:
 * jeżeli podtyp jest pusty, do nazwy tabeli dołączany jest przyrostek 
 * '/category', jeżeli podana została nazwa podtypu, zostaje ona użyta 
 * w miejscu nazwy tabeli (nazwa tabeli - kontenera jest ignorowana).
 * Nazwy tabel i podtypów muszą być więc unikalne.
 *
 * @param string $table_name OPTIONAL   nazwa tabeli
 * @param string $subtype OPTIONAL      nazwa podtypu
 */
function _scholar_category_path($table_name = null, $subtype = null) // {{{
{
    if (null !== $table_name) {
        $path = (null === $subtype ? $table_name : $subtype) . '/category';
    } else {
        $path = '/';
    }

    return scholar_admin_path($path);
} // }}}

/**
 * Strona wyświetlająca listę kategorii.
 *
 * @param string $table_name
 * @param string $subtype OPTIONAL
 */
function scholar_category_list($table_name, $subtype = null) // {{{
{
    global $language;

    drupal_add_tab(t('Add category'), $_GET['q'] . '/add');

    $header = array(
        array('data' => t('Name'), 'field' => 'n.name', 'sort' => 'asc'),
        array('data' => t('Size'), 'title' => t('Number of category members')),
        array('data' => t('Operations'), 'colspan' => 2),
    );

    // poniewaz subtype moze miec wartosc NULL uzycie placeholderow
    // w db_query byloby niewygodne
    $where = array(
        'c.table_name' => $table_name,
        'c.subtype'    => $subtype,
        'n.language'   => $language->language,
    );

    $query = db_query("SELECT * FROM {scholar_categories} c LEFT JOIN {scholar_category_names} n ON c.id = n.category_id WHERE " . scholar_db_where($where) . tablesort_sql($header));

    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['name']),
            intval($row['refcount']),
            l(t('edit'),   _scholar_category_path($table_name, $subtype) . '/edit/' . $row['id']),
            l(t('delete'), _scholar_category_path($table_name, $subtype) . '/delete/' . $row['id']),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4),
        );
    }

    return theme('table',  $header, $rows);
} // }}}

/**
 * Strona z formularzem edycji kategorii.
 *
 * @param array &$form_state
 * @param string $table_name
 * @param string $subtype
 * @param int $id OPTIONAL
 */
function scholar_category_form(&$form_state, $table_name, $subtype = null, $id = null) // {{{
{
    if (null === $id) {
        $is_new = true;

        // pusty rekord, musi miec ustawione pola table_name i subtype,
        // bo beda one niezbedne podczas zapisu do bazy danych
        $record = new stdClass;
        $record->table_name = $table_name;
        $record->subtype = $subtype;

        drupal_add_tab(t('Add category'), $_GET['q']);

    } else {
        $is_new = false;
        $record = scholar_fetch_category($id, $table_name, $subtype, _scholar_category_path($table_name, $subtype));
    }

    $form = array(
        '#record' => $record,
    );

    $form['guid'] = array(
        '#type'      => 'textfield',
        '#maxlength' => 128,
        '#required'  => true,
        '#title'     => t('Unique identifier'),
        '#description' => t('Each category must be given a unique identifier.'),
    );

    foreach (scholar_languages() as $code => $name) {
        $form[$code] = array(
            '#type' => 'fieldset',
            '#tree' => true,
            '#title' => scholar_language_label($code, $name),
        );
        $form[$code]['name'] = array(
            '#type' => 'textfield',
            '#title' => t('Name (@language)', array('@language' => $name)),
            '#description' => t('Category name in language: @language', array('@language' => $name)),
            '#required' => true,
            '#default_value' => $record ? $record->names[$code] : null,
        );
    }

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $is_new ? t('Add category') : t('Save changes'),
    );

    return $form;
} // }}}

/**
 * Tworzy lub aktualizuje rekord kategorii na podstawie danych
 * przesłanych w formularzu.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_category_form_submit($form, &$form_state) // {{{
{
    $record = $form['#record'];

    if ($record) {
        $values = $form_state['values'];

        // ustaw nazwy kategorii w dostepnych jezykach
        foreach (scholar_languages() as $code => $name) {
            if (isset($values[$code])) {
                $record->names[$code] = $values[$code]['name'];
            }
        }

        scholar_save_category($record);
        drupal_goto(_scholar_category_path($record->table_name, $record->subtype));
    }
} // }}}

/**
 * Strona z formularzem potwierdzającym usunięcie rekordu kategorii.
 *
 * @param array &$form_state
 * @param int $id
 */
function scholar_category_delete_form(&$form_state, $id) // {{{
{
    global $language;

    $record = scholar_fetch_category($id, false, false, _scholar_category_path());

    $form = array(
        '#record' => $record,
    );

    $form = confirm_form($form,
        t('Are you sure you want to delete category (%name)?', array('%name' => $record->names[$language->language])),
        _scholar_category_path($record->table_name, $record->subtype),
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    return $form;
} // }}}

/**
 * Usuwa rekord kategorii.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_category_delete_form_submit($form, &$form_state) // {{{
{
    $record = $form['#record'];

    if ($record) {
        scholar_delete_category($record);
        drupal_goto(_scholar_category_path($record->table_name, $record->subtype));
    }
} // }}}

// vim: fdm=marker
