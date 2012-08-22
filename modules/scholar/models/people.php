<?php

/*
 * Narzędzia do manipulowania rekordami osób
 *
 * @author xemlock
 * @version 2012-08-19
 */

/**
 * Pobiera z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param int $id               identyfikator osoby
 * @param bool $redirect        czy zgłosić błąd i przekierować do listy
 *                              osób, jeżeli osoba nie została znaleziona
 * @return object
 */
function scholar_load_person($id, $redirect = null) // {{{
{
    $query = db_query('SELECT * FROM {scholar_people} WHERE id = %d', $id);
    $record = db_fetch_object($query);

    if ($record) {
        // pobierz powiazane wezly i pliki
        $record->files = scholar_load_files($record->id, 'people');
        $record->nodes = scholar_load_nodes($record->id, 'people');
    
    } else if ($redirect) {
        drupal_set_message(t('Invalid person identifier supplied (%id)', array('%id' => $id)), 'error');
        return scholar_goto($redirect);
    }

    return $record;
} // }}}

/**
 * @param object &$person
 * @return bool
 */
function scholar_save_person(&$person) // {{{
{
    if (empty($person->id)) {
        $is_new = true;
        $success = scholar_db_write_record('scholar_people', $person);
    } else {
        $is_new = false;
        $success = scholar_db_write_record('scholar_people', $person, 'id');
    }

    if ($success) {
        scholar_save_files($person->id, 'people', $person->files);
        scholar_save_nodes($person->id, 'people', $person->nodes);
    }

    scholar_invalidate_rendering();

    return $success;
} // }}}

/**
 * Usuwa z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param object &$person
 */
function scholar_delete_person(&$person) // {{{
{
    scholar_delete_nodes($person->id, 'people');

    db_query("DELETE FROM {scholar_authors} WHERE person_id = %d", $person->id);
    db_query("DELETE FROM {scholar_people} WHERE id = %d", $person->id);

    $person->id = null;
    scholar_invalidate_rendering();
} // }}}

// vim: fdm=marker
