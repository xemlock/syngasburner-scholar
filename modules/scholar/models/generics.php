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
    $generic->prev_category_id = null;

    if ($generic->id) {
        // zapamietaj oryginalne wartosci parent_id i category_id,
        // zeby pozniej wymusic przeliczenie liczby odwolan do nich
        $query = db_query("SELECT category_id FROM {scholar_generics} WHERE id = %d", $generic->id);
        if ($row = db_fetch_array($query)) {
            $generic->prev_category_id = $row['category_id'];
        }
    }
} // }}}

function scholar_save_generics_record(&$generic) // {{{
{
    scholar_category_dec_refcount($generic->prev_category_id);
    scholar_category_inc_refcount($generic->category_id);

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
function scholar_delete_generics_record(&$generic) // {{{
{
    scholar_category_dec_refcount($generic->category_id);

    // usuniecie dodatkowych informacji
    db_query("DELETE FROM {scholar_generic_suppinfo} WHERE generic_id = %d", $generid->id);
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

    $where = array(
        '?n.language' => $language->language,
    );

    if ($subtype) {
        $where['g.subtype'] = $subtype;
    }

    $query = db_query("SELECT g.id, g.title, n.name AS category_name FROM {scholar_generics} g LEFT JOIN {scholar_category_names} n ON g.category_id = n.category_id WHERE " . scholar_db_where($where) . " ORDER BY n.name, g.title");

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

// vim: fdm=marker
