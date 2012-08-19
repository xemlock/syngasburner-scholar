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

        $record->files = scholar_fetch_files($record->id, 'categories');
        $record->nodes = scholar_fetch_nodes($record->id, 'categories');

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

    // zapisz nazwy kategorii
    foreach ($category->names as $language => $name) {
        db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d AND language = '%s'", $category->id, $language);
        db_query("INSERT INTO {scholar_category_names} (category_id, name, language) VALUES (%d, '%s', '%s')", $category->id, $name, $language);
    }
    unset($name);

    scholar_save_files($category->id, 'categories', $category->files);
    scholar_save_nodes($category->id, 'categories', $category->nodes);

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
 * Lista kategorii do użycia w elemencie SELECT formularza.
 *
 * @param string $table_name OPTIONAL
 * @param string $subtype OPTIONAL
 * @return array
 */
function scholar_category_options($table_name = null, $subtype = null) // {{{
{
    global $language;

    $where = array('?language' => $language->language);

    if ($table_name) {
        $where['table_name'] = $table_name;
    }

    if ($subtype) {
        $where['subtype'] = $subtype;
    }

    $query = db_query("SELECT c.id, n.name FROM {scholar_categories} c JOIN {scholar_category_names} n ON c.id = n.category_id WHERE " . scholar_db_where($where) . " ORDER BY n.name");

    $options = array(
        0 => '', // pusta kategoria
    );

    while ($row = db_fetch_array($query)) {
        $options[$row['id']] = $row['name'];
    }

    return $options;
} // }}}

function scholar_category_acquire($id) // {{{
{
    db_query("UPDATE {scholar_categories} SET refcount = refcount + 1 WHERE id = %d", $id);
} // }}}

function scholar_category_release($id) // {{{
{
    db_query("UPDATE {scholar_categories} SET refcount = refcount - 1 WHERE id = %d AND refcount > 0", $id);
} // }}}





// vim: fdm=marker
