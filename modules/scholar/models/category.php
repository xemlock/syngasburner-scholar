<?php

/**
 * @param int $id
 * @param string $table_name
 * @param string $subtype
 * @return object
 */
function scholar_load_category($id, $table_name = false, $subtype = false, $redirect = null) // {{{
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

        $record->files = scholar_load_files($record->id, 'categories');
        $record->nodes = scholar_load_nodes($record->id, 'categories');

    } else if ($redirect) {
        drupal_set_message(t('Invalid category identifier supplied (%id)', array('%id' => $id)), 'error');
        return scholar_goto($redirect);
    }

    return $record;
} // }}}

/**
 * @param object &$category
 */
function scholar_save_category(&$category) // {{{
{
    // nie zezwalaj na modyfikacje liczby odwolan
    if (property_exists($category, 'refcount')) {
        unset($category->refcount);
    }

    if (empty($category->id)) {
        scholar_db_write_record('scholar_categories', $category);
    } else {
        scholar_db_write_record('scholar_categories', $category, 'id');
    }

    // zapisz nazwy kategorii
    db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d", $category->id);

    foreach ($category->names as $language => $name) {
        db_query("INSERT INTO {scholar_category_names} (category_id, language, name) VALUES (%d, '%s', '%s')", $category->id, $language, $name);
    }

    if (isset($category->files)) {
        scholar_save_files($category->id, 'categories', $category->files);
    }

    if (isset($category->nodes)) {
        scholar_save_nodes($category->id, 'categories', $category->nodes);
    }

    scholar_invalidate_rendering();
} // }}}

/**
 * Usuwa kategorię. Efektem ubocznym funkcji jest ustawienie komunikatu
 * o pomyślnym usunięciu rekordu.
 *
 * @param object &$category
 */
function scholar_delete_category(&$category) // {{{
{
    db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d", $category->id);
    db_query("DELETE FROM {scholar_categories} WHERE id = %d", $category->id);

    $category->id = null;

    scholar_invalidate_rendering();
} // }}}


/**
 * Ponieważ liczenie rekordów odwołujących się do danej kategorii nie jest
 * proste (odwołania mogą pochodzić z różnych tabel), operacje na liczniku
 * ograniczone są do inkrementacji i dekrementacji.
 */
function scholar_category_inc_refcount($id) // {{{
{
    db_query("UPDATE {scholar_categories} SET refcount = refcount + 1 WHERE id = %d", $id);
} // }}}

function scholar_category_dec_refcount($id) // {{{
{
    db_query("UPDATE {scholar_categories} SET refcount = refcount - 1 WHERE id = %d AND refcount > 0", $id);
} // }}}

/**
 * Lista kategorii do użycia w elemencie SELECT formularza. Jeżeli nie ma
 * żadnej kategorii zdefiniowanej, zwrócona zostanie pusta lista. W przeciwnym
 * razie na pierwszym miejscu w zwróconej liście znajdować się będzie zerowa
 * wartość bez etykiety, odpowiadajaca pustej (niewybranej) kategorii.
 *
 * @param string $table_name OPTIONAL
 * @param string $subtype OPTIONAL
 * @return array
 */
function scholar_category_options($table_name = null, $subtype = null) // {{{
{
    global $language;

    $where = array();

    if ($table_name) {
        $where['table_name'] = $table_name;
    }

    if ($subtype) {
        $where['subtype'] = $subtype;
    }

    if (count($where)) {
        $where = 'WHERE ' . scholar_db_where($where);
    }

    $query = db_query("SELECT c.id, n.name FROM {scholar_categories} c JOIN {scholar_category_names} n ON (c.id = n.category_id AND n.language = '%s') " . $where . " ORDER BY n.name", $language->language);

    $options = array(
        0 => '', // pusta kategoria
    );

    while ($row = db_fetch_array($query)) {
        $options[$row['id']] = $row['name'];
    }

    return count($options) > 1 ? $options : array();
} // }}}

// vim: fdm=marker
