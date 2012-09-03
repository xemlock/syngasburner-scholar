<?php

/**
 * Hook presave_record. Sam rekord jest strony
 * niemutowalny, zapisywane sa jedynie rekordy z nim powiązane, takie jak
 * pliki i węzły (segmenty).
 *
 * @param object &$page
 *     obiekt reprezentujący rekord strony
 */
function scholar_presave_pages_record(&$page) // {{{
{
    // nie zezwalamy na modyfikacje bezposrednich danych rekordu,
    // a jedynie danych powiazanych
    $query = db_query("SELECT * FROM {scholar_pages} WHERE id = %d", $page->id);
    $row   = db_fetch_array($query);

    if ($row) {
        // ta petla gwarantuje nam niezmiennosc danych, poniewaz hook
        // presave jest wykonywany bezposrednio przed zapisem do tabeli
        foreach ($row as $key => $value) {
            $page->$key = $value;
        }

        // niestety nie mozemy po prostu usunac wszystkich pol obiektu,
        // bo scholar_db_write_record musi dostac przynajmniej jedna
        // kolumne z wartoscia do zapisu, co wiecej, nie moze to byc
        // kolumna sekwencyjna (serial)
    }
} // }}}

/**
 * Dodatkowa kolumna published z liczbą opublikowanych węzłów podpiętych
 * do danej strony.
 *
 * @return resource
 */
function scholar_pages_recordset() // {{{
{
    // ciagniemy z bazy liste stron, przygotowujac tytuly stron do sortowania
    // wzgledem ich tlumaczen w biezacym jezyku.

    // pobierz kody aktywnych jezykow i zbuduj zbior do uzycia w zapytaniu SQL
    $languages = array_keys(scholar_languages());
    $languages_sql = count($languages)
        ? '(' . implode(',', array_map('scholar_db_quote', $languages)) . ')'
        : '(NULL)';

    // pobierz liste stron wraz z informacja o liczbie opublikowanych wersji
    // jezykowych w aktywnych jezykach
    $sql = "SELECT p.id, p.title, SUM(CASE WHEN n.status <> 0 AND n.language IN $languages_sql THEN 1 ELSE 0 END) AS published FROM {scholar_pages} p LEFT JOIN {scholar_nodes} n ON n.table_name = 'pages' AND p.id = n.row_id GROUP BY p.id, p.title ORDER BY p.title";

    return db_query($sql);
} // }}}

// vim: fdm=marker
