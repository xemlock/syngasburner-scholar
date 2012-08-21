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
 * @return array
 */
function scholar_load_authors($generic_id) // {{{
{
    $query = db_query("SELECT p.id, p.first_name, p.last_name FROM {scholar_authors} a JOIN {scholar_people} p ON a.person_id = p.id WHERE a.generic_id = %d ORDER BY a.weight", $generic_id);
    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
} // }}}

/**
 * @param int $generic_id
 * @param array $authors
 */
function scholar_save_authors($generic_id, $authors) // {{{
{
    // dla wszystkich identyfikatorow osob (rekordow w tabeli people)
    // w podanej tablicy sprawdz czy sa one poprawne

    $ids = array();
    foreach ($authors as $person) {
        $ids[$person['id']] = false;
    }

    $where = array('id' => array_keys($ids));
    $query = db_query("SELECT id, last_name FROM {scholar_people} WHERE " . scholar_db_where($where));

    while ($row = db_fetch_array($query)) {
        $ids[$row['id']] = $row;
    }

    // dodaj tylko te rekordy, ktore sa poprawne
    db_query("DELETE FROM {scholar_authors} WHERE generic_id = %d", $generic_id);

    $names = array();

    foreach ($authors as $person) {
        $person_id = $person['id'];

        if (false === $ids[$person_id]) {
            continue;
        }

        db_query("INSERT INTO {scholar_authors} (generic_id, person_id, weight) VALUES (%d, %d, %d)", $generic_id, $person_id, $person['weight']);

        if (count($names) < 4) {
            $names[] = $ids[$person_id]['last_name'];
        }
    }

    $bib = scholar_bib_authors($names);

    db_query("UPDATE {scholar_generics} SET bib_authors = " . scholar_db_quote($bib) . " WHERE id = %d", $generic_id);
} // }}}

function scholar_delete_authors($generic_id) // {{{
{
    db_query("DELETE FROM {scholar_authors} WHERE generic_id = %d", $generic_id);
} // }}}

/**
    // pobieramy co najwyzej czterech autorow, jezeli jest dwoch
    // uzyj ampersandu, jezeli trzech uzyj przecinka i ampersandu,
    // jezeli czterech i wiecej uzyj et al.
 * @return string
 */
function scholar_bib_authors($names) // {{{
{
    if (count($names) > 4) {
        $names = array_slice($names, 0, 4);
    }

    switch (count($names)) {
        case 4:
            $bib = $names[0] . ' et al.';
            break;

        case 3:
            $bib = $names[0] . ', ' . $names[1] . ' & ' . $names[2];
            break;

        case 2:
            $bib = $names[0] . ' & ' . $names[1];
            break;

        case 1:
            $bib = $names[0];
            break;

        default:
            $bib = null;
            break;
    }

    return $bib;
} // }}}

/**
 * Zwraca wypełniony obiekt reprezentujący rekord tabeli generyków.
 *
 * @param int $id
 *     identyfikator rekordu
 * @param string $subtype
 *     OPTIONAL wymagany podtyp rekordu
 * @param string $redirect
 *     OPTIONAL jeśli podany nastąpi przekierowanie do podanej strony
 *     w komunikatem o nieprawidłowym identyfikatorze rekordu
 * @return false|object
 */
function scholar_load_generic($id, $subtype = null, $redirect = null) // {{{
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
        drupal_goto($redirect);
        exit;
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
        $query = db_query("SELECT parent_id, category_id FROM {scholar_generics} WHERE id = %d", $generic->id);
        if ($row = db_fetch_array($query)) {
            $parent_id = $row['parent_id'];
            $category_id = $row['category_id'];
        } else {
            $parent_id = $category_id = null;
        }

        $is_new = false;
        if (scholar_db_write_record('scholar_generics', $generic, 'id')) {
            // zaktualizuj liczniki odwolan kategorii, bez znaczenia czy stary
            // i nowy identyfikator sa rozne czy takie same. Najwyzej zostanie
            // wykonana dekremantacja i inkrementacja na tej samej wartosci.
            scholar_category_dec_refcount($category_id);
            scholar_category_inc_refcount($generic->category_id);

            $success = true;
        }

    } else {
        $is_new = true;
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

        drupal_set_message($is_new
            ? t('%title created successfully.', array('%title' => $generic->title))
            : t('%title updated successfully.', array('%title' => $generic->title))
        );
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

    drupal_set_message(t('%title deleted successfully.', array('%title' => $generic->title)));
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
