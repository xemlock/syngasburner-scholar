<?php

/**
 * @param object &$recor
 */
function scholar_load_categories_record(&$record) // {{{
{
    $names = array();

    // przygotuj miejsce dla nazw kategorii we wszystkich dostepnych jezykach
    foreach (scholar_languages() as $language => $name) {
        $names[$language] = null;
    }

    // pobierz dostepne nazwy kategorii
    $query = db_query("SELECT * FROM {scholar_category_names} WHERE category_id = %d", $record->id);
    while ($row = db_fetch_array($query)) {
        $names[$row['language']] = $row['name'];
    }

    $record->names = $names;
} // }}}

/**
 * @param object &$category
 */
function scholar_presave_categories_record(&$record) // {{{
{
    // nie zezwalaj na modyfikacje liczby odwolan
    if (property_exists($record, 'refcount')) {
        unset($record->refcount);
    }
} // }}}

function scholar_postsave_categories_record(&$record) // {{{
{
    // zapisz nazwy kategorii
    db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d", $record->id);

    foreach ($record->names as $language => $name) {
        db_query("INSERT INTO {scholar_category_names} (category_id, language, name) VALUES (%d, '%s', '%s')", $record->id, $language, $name);
    }
} // }}}

/**
 * Usuwa kategorię. Efektem ubocznym funkcji jest pozostawienie
 * wiszących referencji do usuwanej kategorii w innych tabelach.
 *
 * @param object &$record
 */
function scholar_predelete_categories_record(&$record) // {{{
{
    db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d", $record->id);
} // }}}


/**
 * Ponieważ liczenie rekordów odwołujących się do danej kategorii nie jest
 * proste (odwołania mogą pochodzić z różnych tabel), operacje na liczniku
 * ograniczone są do inkrementacji i dekrementacji.
 */
function scholar_category_inc_refcount($category_id) // {{{
{
    db_query("UPDATE {scholar_categories} SET refcount = refcount + 1 WHERE id = %d", $category_id);
} // }}}

function scholar_category_dec_refcount($category_id) // {{{
{
    db_query("UPDATE {scholar_categories} SET refcount = refcount - 1 WHERE id = %d AND refcount > 0", $category_id);
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

    $conds = array();

    if ($table_name) {
        $conds['table_name'] = $table_name;
    }

    if ($subtype) {
        $conds['subtype'] = $subtype;
    }

    if (count($conds)) {
        $where = 'WHERE ' . scholar_db_where($conds);
    } else {
        $where = '';
    }

    $query = db_query("SELECT c.id, n.name FROM {scholar_categories} c JOIN {scholar_category_names} n ON (c.id = n.category_id AND n.language = '%s') $where ORDER BY n.name", $language->language);

    $options = array(
        0 => '', // pusta kategoria
    );

    while ($row = db_fetch_array($query)) {
        $options[$row['id']] = $row['name'];
    }

    return count($options) > 1 ? $options : array();
} // }}}

function scholar_categories_recordset($conds = null, $header = null, $before = null) // {{{
{
    global $language;

    $sql = "SELECT * FROM {scholar_categories} c LEFT JOIN {scholar_category_names} n ON (c.id = n.category_id AND n.language = " . scholar_db_quote($language->language) . ") WHERE " . scholar_db_where($conds);
   
    return scholar_recordset_query($sql, $header, $before);
} // }}}

// vim: fdm=marker
