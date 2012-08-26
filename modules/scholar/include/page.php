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

function scholar_page_publications($node) // {{{
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
            // wywal kolumny parenta, nie beda pozniej potrzebne
            _scholar_page_unset_parent_keys($row);
            $articles[] = $row;
            continue;
        }

        // ksiazki pogrupuj w kategorie
        $title = $row['parent_title'];
        if (!isset($book_articles[$category][$title])) {
            $book_articles[$category][$title] = array(
                'id'         => $row['parent_id'],
                'title'      => $title,
                'start_date' => $row['parent_start_date'],
                'details'    => $row['parent_details'],
                'url'        => $row['parent_url'],
                'articles'   => array(),
            );
        }

        // usun dane parenta z artykulu
        _scholar_page_unset_parent_keys($row);
        $book_articles[$category][$title]['articles'][] = $row;
    }

    // przypisz URLe do rekordow
    foreach ($articles as &$article) {
        $url = _scholar_node_url($article['id'], 'generics', $node->language);
        if ($url) {
            $article['url'] = $url;
        }
    }

    foreach ($book_articles as $category => &$books) {
        foreach ($books as &$book) {
            // pobierz URL dla ksiazki...
            $url = _scholar_node_url($book['id'], 'generics', $node->language);
            p($url);
            if ($url) {
                $book['url'] = $url;
            }

            // ...i dla wszystkich znajdujacych sie w niej artykulow
            foreach ($book['articles'] as &$article) {
                $url = _scholar_node_url($article['id'], 'generics', $node->language);
                if ($url) {
                    $article['url'] = $url;
                }
            }
        }
    }

    // TODO wyrenderuj dane
    p($articles);
    p($book_articles);
} // }}}

function scholar_page_conferences($node) // {{{
{
    // pobierz tylko te  prezentacje, ktore naleza do konferencji (INNER JOIN),
    // oraz maja niepusty tytul (LENGTH dostepna jest wszedzie poza MSSQL Server)
    $query = db_query("SELECT g.* FROM {scholar_generics} g JOIN {scholar_generics} g2 ON g.parent_id = g2.id WHERE g2.list <> 0 AND g.subtype = 'presentation' AND g2.subtype = 'conference' AND LENGTH(g.title) > 0 ORDER BY g2.start_date DESC");

    // pobierz prezentacje i identyfikatory konferencji
    $conferences = array();
    $presentations = array();

    foreach (scholar_db_fetch_all($query) as $row) {
        // dodaj URL strony z prezentacja
        $row['url'] = _scholar_node_url($row['id'], 'presentation', $node->language);
        $presentations[] = $row;
        $conferences[$row['parent_id']] = null;
    }

    // pobierz konferencje
    $query = db_query("SELECT * FROM {scholar_generics} WHERE " . scholar_db_where(array('id' => array_keys($conferences))));
    foreach (scholar_db_fetch_all($query) as $row) {
        // poszukaj najpierw URLa wezla konferencji, dopiero gdy nie zostal
        // znaleziony uzyj zewnetrznego
        $url = _scholar_node_url($row['id'], 'generics', $node->language);
        if ($url) {
            $row['url'] = $url;
        }
        $conferences[$row['id']] = $row;
    }

    // pobierz autorow prezentacji
    foreach ($presentations as &$row) {
        $authors = scholar_load_authors($row['id']);

        foreach ($authors as &$author) {
            // dodaj adres URL stron osob
            $author['url'] = _scholar_node_url($author['id'], 'people', $node->language);
        }

        $row['authors'] = $authors;
    }
    unset($row);

    // pamietaj o podziale na lata, jezeli jest wiecej niz jeden rok
    p($conferences);
    p($presentations);
} // }}}

// vim: fdm=marker
