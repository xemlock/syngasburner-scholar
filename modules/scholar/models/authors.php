<?php

/**
 * @return array
 */
function scholar_load_authors($generic_id) // {{{
{
    $query = db_query("SELECT p.id, p.first_name, p.last_name, a.weight FROM {scholar_authors} a JOIN {scholar_people} p ON a.person_id = p.id WHERE a.generic_id = %d ORDER BY a.weight", $generic_id);
    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
} // }}}

/**
 * Funkcja ustawia nowych autorów dla tego rekordu generycznego.
 * Poprzedni autorzy zostają usunięci.
 *
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

// vim: fdm=marker
