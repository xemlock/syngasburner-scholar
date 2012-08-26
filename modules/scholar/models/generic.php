<?php

function scholar_new_generic() // {{{
{
    $record = new stdClass;
    $schema = drupal_get_schema('scholar_generics');

    if ($schema) {
        foreach ($schema['fields'] as $field => $info) {
            $record->$field = null;
        }
    }

    return $record;
} // }}}

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
function scholar_load_generic($id, $subtype = false, $redirect = null) // {{{
{
    $where = array();
    $where['id'] = $id;

    if ($subtype) {
        $where['subtype'] = $subtype;
    }

    $query = db_query("SELECT * FROM {scholar_generics} WHERE " . scholar_db_where($where));
    $record = db_fetch_object($query);

    if ($record) {
        $record->authors = scholar_load_authors($record->id);
        $record->files   = scholar_load_files($record->id, 'generics');
        $record->nodes   = scholar_load_nodes($record->id, 'generics');
        $record->events  = scholar_load_events($record->id, 'generics');

    } else if ($redirect) {
        drupal_set_message(t('Invalid record identifier supplied (%id)', array('%id' => $id)), 'error');
        return scholar_goto($redirect);
    }

    return $record;
} // }}}

/**
 * Zapisuje nowy lub istniejący rekord generyczny do tabeli w bazie danych.
 *
 * @param object &$generic
 * @return bool
 */
function scholar_save_generic(&$generic) // {{{
{
    $success = false;

    if ($generic->id) {
        // zapamietaj oryginalne wartosci parent_id i category_id,
        // zeby pozniej wymusic przeliczenie liczby odwolan do nich
        $query = db_query("SELECT category_id FROM {scholar_generics} WHERE id = %d", $generic->id);
        if ($row = db_fetch_array($query)) {
            $category_id = $row['category_id'];
        } else {
            $category_id = 0;
        }

        if (scholar_db_write_record('scholar_generics', $generic, 'id')) {
            // zaktualizuj liczniki odwolan kategorii, bez znaczenia czy stary
            // i nowy identyfikator sa rozne czy takie same. Najwyzej zostanie
            // wykonana dekremantacja i inkrementacja na tej samej wartosci.
            scholar_category_dec_refcount($category_id);
            scholar_category_inc_refcount($generic->category_id);

            $success = true;
        }

    } else {
        if (scholar_db_write_record('scholar_generics', $generic)) {
            $success = true;
        }
    }

    if ($success) {
        if (isset($generic->authors)) {
            scholar_save_authors($generic->id, $generic->authors);
        }

        // zapisz dolaczone pliki
        if (isset($generic->files)) {
            scholar_save_files($generic->id, 'generics', $generic->files);
        }

        // zapisz wezly
        if (isset($generic->nodes)) {
            scholar_save_nodes($generic->id, 'generics', $generic->nodes);
        }

        // zapisz zmiany w powiazanych wydarzeniach
        if (isset($generic->events)) {
            scholar_save_events($generic->id, 'generics', $generic->events);
        }

        // wymus wygenerowanie na nowo tresci wezlow (segmentow)
        scholar_invalidate_rendering();
    }

    return $success;
} // }}}

/**
 * Usuwa rekord generyczny z tabeli. Wraz z nim usunięte zostają
 * wszystkie posiadane przez niego powiązania z osobami, powiązania
 * z plikami, węzły (segmenty) i wydarzenia.
 *
 * @param object &$generic
 */
function scholar_delete_generic(&$generic) // {{{
{
    scholar_category_dec_refcount($generic->category_id);

    // usuniecie autorow
    scholar_delete_authors($generic->id);

    // usuniecie powiazan z plikami
    scholar_delete_files($generid->id, 'generics');

    // usuniecie wezlow
    scholar_delete_nodes($generic->id, 'generics');

    // usuniecie wydarzen
    scholar_delete_events($generic->id, 'generics');

    // usuniecie rekordu generycznego
    db_query("DELETE FROM {scholar_generics} WHERE id = %d", $generic->id);

    $generic->id = null;

    scholar_invalidate_rendering();
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

// vim: fdm=marker
