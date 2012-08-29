<?php

/**
 * @return array
 */
function scholar_load_authors($row_id, $table_name) // {{{
{
    $query = db_query("SELECT p.id, p.first_name, p.last_name, a.weight FROM {scholar_authors} a JOIN {scholar_people} p ON a.person_id = p.id WHERE a.table_name = '%s' AND a.row_id = %d ORDER BY a.weight", $table_name, $row_id);
    return scholar_db_fetch_all($query);
} // }}}

/**
 * Funkcja ustawia nowych autorów dla tego rekordu generycznego.
 * Poprzedni autorzy zostają usunięci.
 *
 * @param int $row_id
 * @param array $authors
 */
function scholar_save_authors($row_id, $table_name, $authors) // {{{
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
    db_query("DELETE FROM {scholar_authors} WHERE row_id = %d", $row_id);

    foreach ($authors as $person) {
        $person_id = $person['id'];

        if (false === $ids[$person_id]) {
            continue;
        }

        db_query("INSERT INTO {scholar_authors} (table_name, row_id, person_id, weight) VALUES ('%s', %d, %d, %d)", $table_name, $row_id, $person_id, $person['weight']);
    }
} // }}}

function scholar_delete_authors($row_id, $table_name) // {{{
{
    db_query("DELETE FROM {scholar_authors} WHERE table_name = '%s' AND row_id = %d", $table_name, $row_id);
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
