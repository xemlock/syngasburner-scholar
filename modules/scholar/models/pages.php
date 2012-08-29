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

// vim: fdm=marker
