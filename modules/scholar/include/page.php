<?php

/**
 * Funkcja pomocnicza usuwająca z tabeli reprezentującej pobrany
 * wiersz tabeli wszystkie kolumny, których nazwa rozpoczyna się
 * od parent_.
 *
 * @param array &$row
 */
function _scholar_page_unset_parent_keys(&$row) // {{{
{
    foreach ($row as $key => $value) {
        if (!strncmp('parent_', $key, 7)) {
            unset($row[$key]);
        }
    }
} // }}}

function _scholar_page_prepare_collection(&$collection)
{
    if (is_array($collection)) {
        $keys = array_keys($collection);
    } else {
        $keys = array();
        foreach ($collection as $key => $value) {
            $keys[] = $key;
        }
    }

    for ($i = 0, $n = count($keys); $i < $n; ++$i) {
        $key = $keys[$i];
        $collection[$key]['first'] = 0 == $i;
        $collection[$key]['last']  = $n - 1 == $i;
    }
}

function _scholar_page_augment_record(&$record, $row_id, $table_name, $language) // {{{
{
    $language = (string) $language;

    $url = _scholar_node_url($id, $table_name, $language);
    if ($url) {
        $record['url'] = $url;
    }

    $record['authors'] = scholar_load_authors($row_id);
    $record['files']   = scholar_load_files($row_id, $table_name, $language);
} // }}}

function scholar_page_publications($view, $node) // {{{
{
    global $language;

    $query = db_query("
        SELECT g.id, g.title, g.start_date, g.details, g.url, g.parent_id,
               g2.title AS parent_title, g2.start_date AS parent_start_date,
               g2.details AS parent_details, g2.url AS parent_url,
               c.name AS category_name
            FROM {scholar_generics} g
            LEFT JOIN {scholar_generics} g2
                ON g.parent_id = g2.id
            LEFT JOIN {scholar_category_names} c
                ON g2.category_id = c.category_id
            WHERE
                g.subtype = 'article'
                AND (g2.subtype IS NULL OR g2.subtype = 'book')
                AND (c.language IS NULL OR c.language = '%s')
        ORDER BY g.start_date DESC
    ", $language->language);

    // Reviewed papers / Publikacje w czasopismach recenzowanych
    $articles = array();

    // artykuly wchodzace wsklad ksiazek lub prac zbiorowych
    $book_articles = array();

    while ($row = db_fetch_array($query)) {
        $category = trim($row['category_name']);

        // nazwa kategorii nie bedzie juz potrzebna temu rekordowi
        unset($row['category_name']);

        // artykuly bez parenta, lub te, dla ktorych parent ma pusty rok,
        // lub o nieskategoryzowanym parencie. Zakladamy wtedy, ze parent
        // (istniejacy lub nie) to seria wydawnicza lub czasopismo.
        if (empty($row['parent_id']) || empty($row['parent_start_date']) || !strlen($category)) {
            $year  = intval(substr($row['start_date'], 0, 4));
            $row['year'] = $year ? $year : '';

            // wywal kolumny parenta, nie beda pozniej potrzebne
            _scholar_page_unset_parent_keys($row);

            $articles[] = $row;
            continue;
        }

        // ksiazki pogrupuj w kategorie
        $title = $row['parent_title'];
        $year  = intval(substr($row['parent_start_date'], 0, 4));

        if (!isset($book_articles[$category][$title])) {
            $book_articles[$category][$title] = array(
                'id'         => $row['parent_id'],
                'title'      => $title,
                'year'       => $year ? $year : '',
                'details'    => $row['parent_details'],
                'url'        => $row['parent_url'],
                'articles'   => array(),
            );
        }

        // usun dane parenta z artykulu
        _scholar_page_unset_parent_keys($row);
        $book_articles[$category][$title]['articles'][] = $row;
    }

    // przypisz URLe do stron artykulow i ksiazek oraz autorow
    foreach ($articles as &$article) {
        _scholar_page_augment_record($article, $article['id'], 'generics', $node->language);
    }

    foreach ($book_articles as $category => &$books) {
        foreach ($books as &$book) {
            _scholar_page_augment_record($book, $book['id'], 'generics', $node->language);
            foreach ($book['articles'] as &$article) {
                _scholar_page_augment_record($article, $article['id'], 'generics', $node->language);
            }
        }
    }

    return $view
        ->assign('articles', $articles)
        ->assign('book_articles', $book_articles)
        ->render('publications.tpl');
} // }}}

function scholar_page_conferences($view, $node) // {{{
{
    // pobierz tylko te  prezentacje, ktore naleza do konferencji (INNER JOIN),
    // oraz maja niepusty tytul (LENGTH dostepna jest wszedzie poza MSSQL Server)
    $query = db_query("
        SELECT g.id, g.title, g.details, g.url, g.parent_id,
               g2.title AS parent_title, g2.start_date AS parent_start_date,
               g2.end_date AS parent_end_date, g2.details AS parent_details,
               g2.url AS parent_url
        FROM {scholar_generics} g
        JOIN {scholar_generics} g2
            ON g.parent_id = g2.id
        WHERE g2.list <> 0
            AND g.subtype = 'presentation'
            AND g2.subtype = 'conference'
            AND LENGTH(g.title) > 0
        ORDER BY g2.start_date DESC
    ");

    // prezentacje pogrupowane wedlug konferencji
    $conferences = array();

    while ($row = db_fetch_array($query)) {
        $parent_id = $row['parent_id'];

        if (!isset($conferences[$parent_id])) {
            $conferences[$parent_id] = array(
                'id'           => $parent_id,
                'title'        => $row['parent_title'],
                'start_date'   => $row['parent_start_date'],
                'end_date'     => $row['parent_end_date'],
                'details'      => $row['parent_details'],
                'url'          => $row['parent_url'],
                'presentations' => array(),
            );
        }

        _scholar_page_unset_parent_keys($row);
        $conferences[$parent_id]['presentations'][] = $row;
    }

    // dodaj URL do stron z konferencjami i prezentacjami oraz
    // autorow prezentacji
    foreach ($conferences as &$conference) {
        _scholar_page_augment_record($conference, $conference['id'], 'generics', $node->language);
        foreach ($conference['presentations'] as &$presentation) {
            _scholar_page_augment_record($presentation, $presentation['id'], 'generics', $node->language);
        }
    }

    p($conferences);

    // pamietaj o podziale na lata, jezeli jest wiecej niz jeden rok
    return $view
        ->assign('conferences', $conferences)
        ->render('conferences.tpl');
} // }}}

// vim: fdm=marker
