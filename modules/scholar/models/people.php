<?php

/**
 * @param object &$person
 */
function scholar_save_people_record(&$person) // {{{
{
    // modyfikacja rekordu osoby wymusza aktualizacje rekordow, ktore
    // odwoluja sie do tego rekordu jako autora
    _scholar_invoke_author_update($person->id);
} // }}}

/**
 * Usuwa z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param object &$person
 */
function scholar_delete_people_record(&$person) // {{{
{
    // usun powiazania tej osoby z rekordami innych tabel
    db_query("DELETE FROM {scholar_authors} WHERE person_id = %d", $person->id);

    _scholar_invoke_author_update($person->id);
} // }}}

/**
 * Wywołuje funkcje obsługujące hook author_update.
 *
 * @param int $person_id
 */
function _scholar_invoke_author_update($person_id) // {{{
{
    // pobierz identyfikatory wszystkich rekordow (z roznych tabel),
    // odwolujacych sie do osoby o podanym identyfikatorze
    $query = db_query("SELECT table_name, row_id FROM {scholar_authors} WHERE person_id = %d", $person_id);

    // od razu pobieramy pelna liste, tak by funkcje obslugujace hook
    // mogly bez problemu operowac na bazie danych

    foreach (scholar_db_fetch_all($query) as $row) {
        // dla kazdej tabeli sprawdz czy istnieje funkcja obslugujaca
        // hook, jezeli tak, wywolaj ja podajac jako pierwszy argument
        // identyfikator rekordu w tej tabeli

        $func = "scholar_{$row['table_name']}_author_update";

        // function_exists sprowadza sie do szukania nazwy funkcji
        // w tablicy haszujacej (Zend/zend_builtin_functions.c)
        if (function_exists($func)) {
            call_user_func_array($func, array($row['row_id'], $person_id));
        }
    }
} // }}}

// vim: fdm=marker
