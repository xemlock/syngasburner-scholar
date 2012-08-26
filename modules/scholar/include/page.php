<?php

function scholar_page_publications()
{
    // w pierwszej czesci artykuly bez parenta, lub te, dla ktorych parent
    // ma pusty rok (seria wydawnicza lub czasopismo)

    // pozniej dla kazdej kategorii parenta, dla kazdego parenta w tej
    // kategorii wypisz wszystkie powiazane artykuly

}

function scholar_page_conferences() // {{{
{
    // pobierz tylko te prezentacje, ktore naleza do konferencji, oraz maja
    // niepusty tytul. Funkcja LENGTH dostepna jest wszedzie poza MSSQL Server
    $query = db_query("SELECT g.* FROM {scholar_generics} g JOIN {scholar_generics} g2 ON g.parent_id = g2.id WHERE g2.list <> 0 AND g.subtype = 'presentation' AND g2.subtype = 'conference' AND LENGTH(g.title) > 0 ORDER BY g2.start_date DESC");

    // pobierz prezentacje i identyfikatory konferencji
    $conferences = array();
    $presentations = array();
    while ($row = db_fetch_array($query)) {
        $presentations[] = $row;
        $conferences[$row['parent_id']] = null;
    }

    // pobierz konferencje
    $query = db_query("SELECT * FROM {scholar_generics} WHERE " . scholar_db_where(array('id' => array_keys($conferences))));
    while ($row = db_fetch_array($query)) {
        $conferences[$row['id']] = $row;
    }

    // pobierz autorow prezentacji
    foreach ($presentations as &$row) {
        $presentation['authors'] = scholar_load_authors($row['id']);
    }
    unset($row);

    // pamietaj o podziale na lata, jezeli jest wiecej niz jeden rok

} // }}}

// vim: fdm=marker
