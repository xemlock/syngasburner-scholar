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

function _scholar_page_augment_collection(&$collection)
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
        $element = &$collection[$keys[$i]];

        if (is_array($element)) {
            $element['first'] = 0 == $i;
            $element['last']  = $n - 1 == $i;
        }
    }
}

function _scholar_page_augment_record(&$record, $row_id, $table_name, $language) // {{{
{
    $language = (string) $language;

    $url = _scholar_node_url($row_id, $table_name, $language);
    if ($url) {
        $record['url'] = $url;
    }

    $authors = scholar_load_authors($row_id, $table_name);
    _scholar_page_augment_collection($authors);

    foreach ($authors as &$author) {
        $author['url'] = _scholar_node_url($author['id'], 'people', $language);
    }
    $record['authors'] = $authors;

    $files = scholar_load_files($row_id, $table_name, $language);
    _scholar_page_augment_collection($files);

    // dodaj urle do plikow
    foreach ($files as &$file) {
        $file['url'] = scholar_file_url($file['filename']);
    }
    unset($file);

    $record['files']   = $files;
} // }}}

function _scholar_publication_details($details) // {{{
{
    // jezeli sa informacje szczegolowe, dodaj do nich przecinek,
    // ale tylko jezeli nie rozpoczynaja sie od nawiasu
    $details = trim($details);

    if (strlen($details)) {
        if (false === strpos('<{([', substr($details, 0, 1))) {
            $separator = ', ';
        } else {
            $separator = ' ';
        }

        $details = $separator . $details;
    }

    // poniewaz poza byciem dodatkowym tekstem szczegoly nic nie wnosza,
    // nie widze przeciwskazan, aby je w ten sposob uzupelnic
    return $details;
} // }}}

function scholar_page_publications($view, $node) // {{{
{
    $language = $node->language;

    $query = db_query("
        SELECT g.id, g.title, g.start_date, g.bib_details AS bib_details, g.url,
               g.parent_id, g2.title AS parent_title,
               g2.start_date AS parent_start_date,
               g2.bib_details AS parent_bib_details, g2.url AS parent_url,
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
    ", $language);

    // Reviewed papers / Publikacje w czasopismach recenzowanych
    $articles = array();

    // artykuly wchodzace w sklad ksiazek lub prac zbiorowych
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
            $row['year']    = $year ? $year : '';
            $row['bib_details'] = _scholar_publication_details($row['bib_details']);

            // dane parenta sa potrzebne do wypisania informacji
            // o czasopismie, wiec ich nie usuwaj
            $articles[] = $row;
            continue;
        }

        // ksiazki pogrupuj w kategorie
        $title = $row['parent_title'];
        $year  = intval(substr($row['parent_start_date'], 0, 4));

        if (!isset($book_articles[$category][$title])) {
            $book_articles[$category][$title] = array(
                'id'          => $row['parent_id'],
                'title'       => $title,
                'year'        => $year ? $year : '',
                'bib_details' => _scholar_publication_details($row['parent_bib_details']),
                'url'         => $row['parent_url'],
                'articles'    => array(),
            );
        }

        // usun dane parenta z artykulu, nie beda juz potrzebne
        _scholar_page_unset_parent_keys($row);
        $row['bib_details'] = _scholar_publication_details($row['bib_details']);

        $book_articles[$category][$title]['articles'][] = $row;
    }

    // przypisz URLe do stron artykulow i ksiazek oraz autorow
    foreach ($articles as &$article) {
        _scholar_page_augment_record($article, $article['id'], 'generics', $language);

        // w przypadku artykulow w czasopismach trzeba ustawic
        // odpowiedni URL parenta
        $url = _scholar_node_url($article['parent_id'], 'generics', $language);
        if ($url) {
            $article['parent_url'] = $url;
        }
    }

    foreach ($book_articles as $category => &$books) {
        foreach ($books as &$book) {
            _scholar_page_augment_record($book, $book['id'], 'generics', $language);
            foreach ($book['articles'] as &$article) {
                _scholar_page_augment_record($article, $article['id'], 'generics', $language);
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
    $language = $node->language;

    // pobierz tylko te  prezentacje, ktore naleza do konferencji (INNER JOIN),
    // oraz maja niepusty tytul (LENGTH dostepna jest wszedzie poza MSSQL Server)
    // country name, locality (Internet), kategoria. Wystepienia w obrebie
    // konferencji posortowane sa alfabetycznie po nazwisku pierwszego autora.
    $query = db_query("
        SELECT g.id, g.title, i.suppinfo AS suppinfo, g.url, g.parent_id,
               g2.title AS parent_title, g2.start_date AS parent_start_date,
               g2.end_date AS parent_end_date, i2.suppinfo AS parent_suppinfo,
               g2.url AS parent_url, g2.country AS parent_country,
               g2.locality AS parent_locality, c.name AS category_name
        FROM {scholar_generics} g
        JOIN {scholar_generics} g2
            ON g.parent_id = g2.id
        LEFT JOIN {scholar_category_names} c
            ON g.category_id = c.category_id
        LEFT JOIN {scholar_generic_suppinfo} i
            ON i.generic_id = g.id
        LEFT JOIN {scholar_generic_suppinfo} i2
            ON i2.generic_id = g2.id
        WHERE g2.list <> 0
            AND g.subtype = 'presentation'
            AND g2.subtype = 'conference'
            AND LENGTH(g.title) > 0
            AND (c.language IS NULL OR c.language = '%s')
            AND (i.language IS NULL OR i.language = '%s')
            AND (i2.language IS NULL OR i2.language = '%s')
        ORDER BY g2.start_date DESC, g.start_date, g.weight
    ", $language, $language, $language);

    // TODO co z kolejnoscia prezentacji w konferencji???
    // prezentacje pogrupowane wedlug konferencji, a te z kolei
    // malejaco wedlug roku
    $year_conferences = array();

    $countries = scholar_countries();

    while ($row = db_fetch_array($query)) {
        $parent_id = $row['parent_id'];
        $year = intval(substr($row['parent_start_date'], 0, 4));

        if (!isset($year_conferences[$year][$parent_id])) {
            $locality = trim($row['parent_locality']);

            if (!strcasecmp('internet', $locality)) {
                // jezeli miejscowosc to internet usun dane lokalizacji
                $country      = '';
                $country_name = '';
                $locality     = '';
            } else {
                $country      = $row['parent_country'];
                $country_name = isset($countries[$country]) ? $countries[$country] : '';
            }

            $start_date = substr($row['parent_start_date'], 0, 10);
            $end_date   = substr($row['parent_end_date'], 0, 10);

            $date_span = $start_date;
            if ($end_date) {
                if ($end_date != $start_date) {
                    $date_span .= ' – ' . $end_date;
                }
            } else {
                $date_span .= ' – …';
            }

            $year_conferences[$year][$parent_id] = array(
                'id'         => $parent_id,
                'title'      => $row['parent_title'],
                'start_date' => $start_date,
                'end_date'   => $end_date,
                'date_span'  => $date_span,
                'suppinfo'   => $row['parent_suppinfo'],
                'url'        => $row['parent_url'],
                'locality'   => $locality,
                'country'    => $country,
                'country_name'  => $country_name,
                'presentations' => array(),
            );
        }

        _scholar_page_unset_parent_keys($row);
        $year_conferences[$year][$parent_id]['presentations'][] = $row;
    }

    // dodaj URL do stron z konferencjami i prezentacjami oraz
    // autorow prezentacji
    foreach ($year_conferences as &$conferences) {
        foreach ($conferences as &$conference) {
            _scholar_page_augment_record($conference, $conference['id'], 'generics', $node->language);
            foreach ($conference['presentations'] as &$presentation) {
                _scholar_page_augment_record($presentation, $presentation['id'], 'generics', $node->language);
            }
        }
    }

    return $view
        ->assign('year_conferences', $year_conferences)
        ->render('conferences.tpl');
} // }}}

// vim: fdm=marker
