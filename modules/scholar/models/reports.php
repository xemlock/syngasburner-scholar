<?php

/**
 * Funkcje pobierające dane z bazy do raportów.
 */

/**
 * Wykaz publikacji i uczestnictwa w konferencjach osoby o podanym
 * identyfikatorze.
 *
 * @param int $id
 * @param string $language
 * @return false|array
 */
function scholar_report_person($id, $language) // {{{
{
    if (!($person = scholar_load_record('people', $id))) {
        return false;
    }

    // pobierz wszystkie artykuly (razem z tytulami wydawnictw), wsrod ktorych
    // autorow znajduje sie ta osoba
    $query = db_query("
        SELECT g.*, i.suppinfo, g2.title AS parent_title, g2.url AS parent_url
        FROM {scholar_authors} a 
        JOIN {scholar_generics} g 
            ON (a.row_id = g.id AND g.subtype = 'article')
        LEFT JOIN {scholar_generics} g2
            ON (g.parent_id = g2.id AND g2.subtype = 'journal')
        LEFT JOIN {scholar_generic_suppinfo} i
            ON (g.id = i.generic_id AND i.language = '%s')
        WHERE a.person_id = %d
            AND a.table_name = 'generics'
        ORDER BY g.start_date DESC, g.weight
    ", $language, $person->id);

    $articles = scholar_db_fetch_all($query);
    foreach ($articles as &$article) {
        _scholar_page_augment_record($article, $article['id'], 'generics', $language);

        $year  = intval(substr($article['start_date'], 0, 4));
        $article['year']        = $year ? $year : '';
        $article['bib_details'] = _scholar_publication_details($article['bib_details']);

        if ($article['parent_id']) {
            $url = scholar_node_url($article['parent_id'], 'generics', $language);
            if ($url) {
                $article['parent_url'] = $url;
            }
        }

        // teraz musimy usunac wszystkie urle prowadzace do strony tej osoby
        // (czyli do strony, ktora w tej chwili generujemy)
        foreach ($article['authors'] as &$author) {
            if ($author['id'] == $person->id) {
                $author['url'] = null;
            }
        }
        unset($author);
    }
    unset($article);

    // co z ksiazkami? na razie nic, jak sie pojawi zapotrzebowanie.

    // Wszystkie prezentacje na konferencjach (JOIN), wktorych uczestniczyla
    // ta osoba (takze te z pustymi tytulami)
    $query = db_query("
        SELECT g.id, g.title, i.suppinfo, g.url, g.parent_id,
               g2.title AS parent_title,
               CASE WHEN g2.start_date IS NULL THEN g.start_date ELSE g2.start_date END AS parent_start_date,
               CASE WHEN g2.start_date IS NULL THEN g.start_date ELSE g2.end_date END AS parent_end_date,
               i2.suppinfo AS parent_suppinfo,
               g2.url AS parent_url, g2.country AS parent_country,
               g2.locality AS parent_locality, c.name AS category_name
        FROM {scholar_authors} a
        JOIN {scholar_generics} g
            ON a.row_id = g.id
        JOIN {scholar_generics} g2
            ON (g.parent_id = g2.id AND g2.subtype = 'conference')
        LEFT JOIN {scholar_category_names} c
            ON (g.category_id = c.category_id AND c.language = '%s')
        LEFT JOIN {scholar_generic_suppinfo} i
            ON (g.id = i.generic_id AND i.language = '%s')
        LEFT JOIN {scholar_generic_suppinfo} i2
            ON (g2.id = i2.generic_id AND i.language = '%s')
        WHERE g2.list <> 0
            AND g.subtype = 'presentation'
            AND a.person_id = %d
            AND a.table_name = 'generics'
        ORDER BY g2.start_date DESC, g.start_date, g.weight
    ", $language, $language, $language, $person->id);

    $presentations = scholar_db_fetch_all($query);

    $conferences = array();

    foreach ($presentations as &$presentation) {
        $parent_id = $presentation['parent_id'];
        if (!isset($conferences[$parent_id])) {
            $conferences[$parent_id] = __scholar_prepare_conference_from_parent_fields($presentation, $language);
        }

        // dodajemy konferencje, ale nie dodajemy wystapienia z pustym tytulem
        if (!strlen($presentation['title'])) {
            continue;
        }

        _scholar_page_unset_parent_keys($presentation);
        _scholar_page_augment_record($presentation, $presentation['id'], 'generics', $language);

        // tutaj nie dodajemy autorow (TODO moze jakas flaga?)
        $presentation['authors'] = array();

        $conferences[$parent_id]['presentations'][] = &$presentation;
    }
    unset($presentation, $presentations);

    return array(
        'publications_title' => t('Publications', array(), $language),
        'conferences_title'  => t('Conferences, seminars, workshops', array(), $language),
        'articles'    => $articles,
        'conferences' => $conferences,
    );
} // }}}

function scholar_report_publications($language)
{
    $query = db_query("
        SELECT g.id, g.title, g.start_date, g.bib_details AS bib_details, g.url,
               i.suppinfo, g.parent_id, g2.title AS parent_title,
               g2.start_date AS parent_start_date,
               g2.bib_details AS parent_bib_details, g2.url AS parent_url,
               c.name AS category_name
            FROM {scholar_generics} g
            LEFT JOIN {scholar_generics} g2
                ON (g.parent_id = g2.id AND g2.subtype = 'journal')
            LEFT JOIN {scholar_category_names} c
                ON (g2.category_id = c.category_id AND c.language = '%s')
            LEFT JOIN {scholar_generic_suppinfo} i
                ON (g.id = i.generic_id AND i.language = '%s')
            WHERE
                g.subtype = 'article'
        ORDER BY g.start_date DESC, g.weight
    ", $language, $language);

    // Reviewed papers / Publikacje w czasopismach recenzowanych
    $articles = array();

    // artykuly wchodzace w sklad ksiazek lub prac zbiorowych
    $journal_articles = array();

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

        if (!isset($journal_articles[$category][$title])) {
            $journal_articles[$category][$title] = array(
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

        $journal_articles[$category][$title]['articles'][] = $row;
    }

    // przypisz URLe do stron artykulow i ksiazek oraz autorow
    foreach ($articles as &$article) {
        _scholar_page_augment_record($article, $article['id'], 'generics', $language);

        // w przypadku artykulow w czasopismach trzeba ustawic
        // odpowiedni URL parenta
        if ($article['parent_id']) {
            $url = scholar_node_url($article['parent_id'], 'generics', $language);
            if ($url) {
                $article['parent_url'] = $url;
            }
        }
    }

    foreach ($journal_articles as $category => &$journals) {
        foreach ($journals as &$journal) {
            _scholar_page_augment_record($journal, $journal['id'], 'generics', $language);
            foreach ($journal['articles'] as &$article) {
                _scholar_page_augment_record($article, $article['id'], 'generics', $language);
            }
        }
    }

    return array(
        'section_title'    => t('Reviewed papers', array(), $language),
        'articles'         => $articles,
        'journal_articles' => $journal_articles,
    );
}


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

function _scholar_page_augment_collection(&$collection) // {{{
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
} // }}}

function _scholar_page_augment_record(&$record, $row_id, $table_name, $language) // {{{
{
    $language = (string) $language;

    $url = scholar_node_url($row_id, $table_name, $language);
    if ($url) {
        $record['url'] = $url;
    }

    $authors = scholar_load_authors($row_id, $table_name);
    _scholar_page_augment_collection($authors);

    foreach ($authors as &$author) {
        $author['url'] = scholar_node_url($author['id'], 'people', $language);
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

function __scholar_prepare_conference_from_parent_fields($row, $language) // {{{
{
            $countries    = scholar_countries(null, $language);
            $locality     = trim($row['parent_locality']);

            $country      = $row['parent_country'];
            $country_name = isset($countries[$country]) ? $countries[$country] : '';

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

            return array(
                'id'         => $row['parent_id'],
                'title'      => $row['parent_title'],
                'start_date' => $start_date,
                'end_date'   => $end_date,
                'date_span'  => $date_span,
                'suppinfo'   => $row['parent_suppinfo'],
                'url'        => $row['parent_url'],
                'locality'   => t($locality, array(), $language),
                'country'    => $country,
                'country_name'  => $country_name,
                'presentations' => array(),
            );    
} // }}}



function scholar_records_conferences_in_category()
{
    

}



// vim: fdm=marker
