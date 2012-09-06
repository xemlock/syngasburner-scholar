<?php

/**
 * Zwraca wypełniony obiekt reprezentujący rekord tabeli generyków.
 *
 * @param int $id
 *     identyfikator rekordu
 * @param string $subtype
 *     podtyp rekordu, jeżeli nie został podany lub gdy podano dowolną pustą
 *     wartość, podtyp nie będzie uwzględniony podczas wyszukiwania
 * @param string $redirect
 *     OPTIONAL jeśli podany nastąpi przekierowanie do podanej strony
 *     z komunikatem o nieprawidłowym identyfikatorze rekordu
 * @return false|object
 */
function scholar_load_generics_record(&$record) // {{{
{
    // przytnij daty poczatku i konca do zadanej liczby znakow
    $record->start_date = substr($record->start_date, 0, $record->start_date_len);
    $record->end_date = substr($record->end_date, 0, $record->end_date_len);

    $suppinfo = array();

    $query = db_query("SELECT * FROM {scholar_generic_suppinfo} WHERE generic_id = %d", $record->id);

    while ($row = db_fetch_array($query)) {
        $suppinfo[$row['language']] = $row['suppinfo'];
    }

    $record->suppinfo = $suppinfo;
} // }}}

/**
 * Zapisuje nowy lub istniejący rekord generyczny do tabeli w bazie danych.
 *
 * @param object &$generic
 * @return bool
 */
function scholar_presave_generics_record(&$generic) // {{{
{
    $generic->prev_parent_id   = null;
    $generic->prev_category_id = null;

    if ($generic->id) {
        // zapamietaj oryginalne wartosci parent_id i category_id,
        // zeby pozniej wymusic przeliczenie liczby odwolan do nich
        $query = db_query("SELECT parent_id, category_id FROM {scholar_generics} WHERE id = %d", $generic->id);
        if ($row = db_fetch_array($query)) {
            $generic->prev_parent_id   = $row['parent_id'];
            $generic->prev_category_id = $row['category_id'];
        }
    }

    // wyznacz liczbe znaczacych znakow w datach, dopelnij niepuste daty
    // do pelnych wartosci typu DATETIME (YYYY-MM-DD HH:MM:SS),
    // 1000-01-01 00:00:00 jest minimalna wartoscia dla tego typu
    // w MySQL.
    $dates = array(
        'start_date' => $generic->start_date,
        'end_date'   => $generic->end_date,
    );

    foreach ($dates as $key => $date) {
        $date = substr(trim($date), 0, 19);
        $len  = strlen($date);

        if ($len) {
            $date .= substr('1000-01-01 00:00:00', $len);
        }

        $generic->$key = $date;
        $generic->{$key . '_len'} = $len;
    }
} // }}}

function scholar_postsave_generics_record(&$generic) // {{{
{
    scholar_category_dec_refcount($generic->prev_category_id);
    scholar_category_inc_refcount($generic->category_id);

    scholar_generic_update_child_count($generic->prev_parent_id);
    if ($generic->parent_id != $generic->prev_parent_id) {
        scholar_generic_update_child_count($generic->parent_id);
    }

    // zaktualizuj informacje dodatkowe
    db_query("DELETE FROM {scholar_generic_suppinfo} WHERE generic_id = %d", $generic->id);

    if (isset($generic->suppinfo)) {
        foreach ((array) $generic->suppinfo as $language => $suppinfo) {
            $suppinfo = trim($suppinfo);
            if (strlen($suppinfo)) {
                db_query("INSERT INTO {scholar_generic_suppinfo} (generic_id, language, suppinfo) VALUES (%d, '%s', '%s')", $generic->id, $language, $suppinfo);
            }
        }
    }

    scholar_generic_update_bib_authors($generic->id);
} // }}}

/**
 * Usuwa rekord generyczny z tabeli. Wraz z nim usunięte zostają
 * wszystkie posiadane przez niego powiązania z osobami, powiązania
 * z plikami, węzły (segmenty) i wydarzenia.
 *
 * @param object &$generic
 */
function scholar_predelete_generics_record(&$generic) // {{{
{
    scholar_category_dec_refcount($generic->category_id);

    // usuniecie dodatkowych informacji
    db_query("DELETE FROM {scholar_generic_suppinfo} WHERE generic_id = %d", $generid->id);
} // }}}

/**
 * Hook wywolywany po usunieciu rekordu.
 */
function scholar_postdelete_generics_record(&$generic) // {{{
{
    scholar_generic_update_child_count($generic->parent_id);
} // }}}

/**
 * Hook author_update wywoływany podczas modifikacji rekordu osoby
 * powiązanej z rekordem innej tabeli poprzez relację bycia autorem.
 * W przypadku tabeli rekordów generycznych funkcja jest aliasem
 * {@see scholar_generic_update_bib_authors}.
 *
 * @param int $row_id
 */
function scholar_generics_author_update($generic_id) // {{{
{
    scholar_generic_update_bib_authors($generic_id);
} // }}}

/**
 * @return resource
 */
function scholar_generics_recordset($conds = null, $header = null, $before = null, $pager = null) // {{{
{
    global $language;

    $header = (array) $header;

    // sprawdz, czy potrzebna jest kolumna z nazwa kraju, jezeli tak,
    // dodaj ja do zapytania
    $cols = 'g.*, n.name AS category_name';

    foreach ($header as $col) {
        if (isset($col['field']) && 'country_name' == $col['field']) {
            $cols .= ', '
                   . scholar_db_country_name('g.country', 'scholar_generics')
                   . ' AS country_name';
            break;
        }
    }

    $sql = "SELECT $cols FROM {scholar_generics} g LEFT JOIN {scholar_category_names} n ON (g.category_id = n.category_id AND n.language = " . scholar_db_quote($language->language) . ") WHERE " . scholar_db_where($conds);

    return scholar_recordset_query($sql, $header, $before, $pager);
} // }}}


/**
 * Lista dostępnych rekordów rodzica podzielonych na kategorie, do użycia jako
 * opcje elementu SELECT formularza. Jeżeli nie istnieje żaden potencjalny
 * rodzic, zwrócona zostanie pusta lista. W przeciwnym razie na pierwszym
 * miejscu w zwróconej liście znajdować się będzie zerowa wartość bez etykiety,
 * odpowiadajaca pustemu (niewybranemu) rekordowi rodzica.
 *
 * @param string $subtype OPTIONAL
 * @return array
 */
function scholar_generic_parent_options($subtype = null) // {{{
{
    global $language;

    if ($subtype) {
        $where = 'WHERE g.subtype = ' . scholar_db_quote($subtype);
    } else {
        $where = '';
    }

    $query = db_query("SELECT g.id, g.title, n.name AS category_name FROM {scholar_generics} g LEFT JOIN {scholar_category_names} n ON (g.category_id = n.category_id AND n.language = '%s') " . $where . " ORDER BY n.name, g.title", $language->language);

    $options = array(
        0 => '', // pusty rodzic
    );

    while ($row = db_fetch_array($query)) {
        $category_name = $row['category_name'];

        if (empty($category_name)) {
            $category_name = t('uncategorized');
        }

        $options[$category_name][$row['id']] = $row['title'];
    }

    return count($options) > 1 ? $options : array();
} // }}}

function scholar_generic_update_bib_authors($generic_id) // {{{
{
    $authors = scholar_load_authors($generic_id, 'generics');
    $names = array();

    foreach ($authors as $author) {
        if (count($names) < 4) {
            $names[] = $author['last_name'];
        }
    }

    $bib = scholar_bib_authors($names);

    db_query("UPDATE {scholar_generics} SET bib_authors = " . scholar_db_quote($bib) . " WHERE id = %d", $generic_id);
} // }}}

function scholar_generic_update_child_count($generic_id) // {{{
{
    // niestety nie mozemy wywolac SELECT na tej samej tabeli, na ktorej
    // wywolujemy UPDATE, stad koniecznosc wykonania dwoch zapytan
    $row = db_fetch_array(db_query("SELECT COUNT(*) AS child_count FROM {scholar_generics} WHERE parent_id = %d AND id <> parent_id", $generic_id));
    db_query("UPDATE {scholar_generics} SET child_count = %d WHERE id = %d", $row['child_count'], $generic_id);
} // }}}

/**
 * @return int
 *     liczba zaktualizowanych rekordów
 */
function scholar_generic_update_children_weights($generic_id, $weights) // {{{
{
    $updated = 0;

    foreach ((array) $weights as $id => $weight) {
        db_query("UPDATE {scholar_generics} SET weight = %d WHERE id = %d AND parent_id = %d AND id <> parent_id", $weight, $id, $generic_id);
        $updated += db_affected_rows();
    }

    return $updated;
} // }}}

function scholar_generic_load_children($generic_id, $subtype = null, $order = null) // {{{
{
    global $language;

    if (is_array($subtype)) {
        $where = $subtype;

    } else if ($subtype) {
        $where = array(
            'subtype' => $subtype,
        );
    } else {
        $where = array();
    }

    $where['parent_id'] = $generic_id;

    $sql = "SELECT g.*, i.suppinfo AS suppinfo, c.name AS category_name FROM {scholar_generics} g LEFT JOIN {scholar_generic_suppinfo} i ON (i.generic_id = g.id AND i.language = '%s') LEFT JOIN {scholar_category_names} c ON (g.category_id = c.category_id AND c.language = '%s') WHERE parent_id <> id AND " . scholar_db_where($where);
    if ($order) {
        $sql .= " ORDER BY " . $order;
    }

    $query = db_query($sql, $language->language, $language->language);
    $rows  = scholar_db_fetch_all($query);

    return $rows;
} // }}}

// vim: fdm=marker
