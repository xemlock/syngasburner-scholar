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

/**
 * Wykaz publikacji.
 *
 * @param string $language
 * @return false|array
 */
function scholar_report_publications($language) // {{{
{
    // sortowanie:
    // 1. ksiazki w/g nazwy kategorii rosnaco
    // 2. ksiazki i czasopisma malejaco po czasie: g2.start_date DESC
    // 3. - dla artykulow w obrebie czasopisma
    //      (g2.parent_id IS NOT NULL AND g2.start_date IS NULL) wedlug weight ASC
    //    - dla artykulow w obrebie ksiazki
    //      (g2.parent_id IS NOT NULL AND g2.start_date IS NOT NULL) wedlug weight ASC
    //    - dla artykulow samotnych (g2.parent_id IS NULL) wedlug g.start_date DESC
    //
    // g2.start_date wspolne dla wszystkich, samotne artykuly i te w czasopismach beda
    // grupowane razem (bo dla nich g2.start_date IS NULL). Artykuly w ksiazkach beda
    // grupowane osobno w/g roku wydania, malejaco.
    //
    // Tricky part jest dla wagi i daty wydania artykulu. Zeby sortowac po wadze rosnaco
    // gdy kolumna jest sortowana malejaco, trzeba przemnozyc wage przez -1 
    // (weight ASC === -weight DESC)
    //
    // Podsumowujac:
    //   ORDER BY g2.start_date DESC, CASE WHEN g2.start_date IS NULL THEN g.start_date ELSE -g.weight END DESC

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
        ORDER BY
            CASE WHEN g2.start_date IS NULL THEN NULL ELSE c.name END ASC,
            g2.start_date DESC,
            CASE WHEN g2.start_date IS NULL THEN g.start_date ELSE -g.weight END DESC
    ", $language, $language);

    // Reviewed papers / Publikacje w czasopismach recenzowanych
    $articles = array();

    // artykuly wchodzace w sklad ksiazek lub prac zbiorowych
    $journal_articles = array();

    // kategoria dla wydawnictw, ktore nie sa czasopismami (nie maja podanej
    // daty wydania), ale nie sa skategoryzowane. Ta kategoria bedzie pierwsza
    // (pusty ciag znakow bedzie na poczatku przy sortowaniu po category_names.name)
    $empty_category = t('Uncategorized non-serial publications', array(), $language);

    while ($row = db_fetch_array($query)) {
        $category = trim($row['category_name']);
        // nazwa kategorii nie bedzie juz potrzebna temu rekordowi
        unset($row['category_name']);

        // artykuly bez parenta, lub te, dla ktorych parent ma pusty rok (jest czasopismem)
        if (empty($row['parent_id']) || empty($row['parent_start_date'])) {
            $year  = intval(substr($row['start_date'], 0, 4));
            $row['year']    = $year ? $year : '';
            $row['bib_details'] = _scholar_publication_details($row['bib_details']);

            // dane parenta sa potrzebne do wypisania informacji
            // o czasopismie, wiec ich nie usuwaj
            $articles[] = $row;
            continue;
        }

        if (empty($category)) {
            $category = $empty_category;
        }

        // wydawnictwa pogrupuj w kategorie
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
} // }}}

/**
 * Wykaz wystąpień na konferencjach.
 *
 * @param string $language
 * @return false|array
 */
function scholar_report_conferences($language) // {{{
{
    // pobierz tylko te  prezentacje, ktore naleza do konferencji (INNER JOIN),
    // oraz maja niepusty tytul (LENGTH dostepna jest wszedzie poza MSSQL Server).
    // Wystepienia w obrebie konferencji posortowane sa wedlug wagi.

    // Jezeli konferencja ma pusta date poczatku, uzyj daty prezentacji jako
    // poczatku i konca konferencji (prezentacje maja pusta date konca). Jest to
    // szczegolnie uzyteczne w przypadku konferencji dlugoterminowych (np. seminaria).
    $query = db_query("
        SELECT g.id, g.title, i.suppinfo AS suppinfo, g.url, g.parent_id,
               g2.title AS parent_title, 
               CASE WHEN g2.start_date IS NULL THEN g.start_date ELSE g2.start_date END AS parent_start_date,
               CASE WHEN g2.start_date IS NULL THEN g.start_date ELSE g2.end_date END AS parent_end_date,
               i2.suppinfo AS parent_suppinfo,
               g2.url AS parent_url, g2.country AS parent_country,
               g2.locality AS parent_locality, c.name AS category_name
        FROM {scholar_generics} g
        JOIN {scholar_generics} g2
            ON g.parent_id = g2.id
        LEFT JOIN {scholar_category_names} c
            ON (g.category_id = c.category_id AND c.language = '%s')
        LEFT JOIN {scholar_generic_suppinfo} i
            ON (i.generic_id = g.id AND i.language = '%s')
        LEFT JOIN {scholar_generic_suppinfo} i2
            ON (i2.generic_id = g2.id AND i2.language = '%s')
        WHERE g2.list <> 0
            AND g.subtype = 'presentation'
            AND g2.subtype = 'conference'
            AND LENGTH(g.title) > 0
        ORDER BY parent_start_date DESC, g.weight
    ", $language, $language, $language);

    $year_conferences = array();

    while ($row = db_fetch_array($query)) {
        $parent_id = $row['parent_id'];
        $year = intval(substr($row['parent_start_date'], 0, 4));

        if (!isset($year_conferences[$year][$parent_id])) {
            $year_conferences[$year][$parent_id] = __scholar_prepare_conference_from_parent_fields($row, $language);
        }

        _scholar_page_unset_parent_keys($row);
        $year_conferences[$year][$parent_id]['presentations'][] = $row;
    }

    // dodaj URL do stron z konferencjami i prezentacjami oraz
    // autorow prezentacji
    foreach ($year_conferences as &$conferences) {
        foreach ($conferences as &$conference) {
            _scholar_page_augment_record($conference, $conference['id'], 'generics', $language);
            foreach ($conference['presentations'] as &$presentation) {
                _scholar_page_augment_record($presentation, $presentation['id'], 'generics', $language);
            }
        }
    }

    return array('year_conferences' => $year_conferences);
} // }}}

/**
 * Wykaz prezentacji w obrębie jednej konferencji. Ponieważ konferencja może
 * ciągnąć się przez wiele lat (wiele edycji, np. seminarium), prezentacje
 * pogrupowane są względem roku.
 */
function scholar_report_conference($id, $language) // {{{
{
    // Wszystkie wystapienia w obrebie konferencji, sortowane wg. dnia, i wagi.
    // Tytul wystapienia musi byc niepusty, w przeciwnym razie prezentacja
    // jest zaznaczeniem biernej obecności, co jest bez znaczenia dla
    // przebiegu konferencji.
    $year_date_presentations = array();

    $children = scholar_generic_load_children($id, 'presentation', 'start_date, weight');

    foreach ($children as &$row) {
        // tylko rekordy, ktore maja niepusty tytul sa brane pod uwage
        // jako wystapienia na konferencji
        if (!strlen($row['title'])) {
            continue;
        }

        // pogrupuj wzgledem roku i dnia, rzutowanie na string, w przeciwnym
        // razie wartosci false, ktore moga byc wynikiem substr, zostana
        // skonwertowane do 0
        $row['start_date'] = (string) substr($row['start_date'], 0, 10);
        $year = (string) substr($row['start_date'], 0, 4);

        _scholar_page_augment_record($row, $row['id'], 'generics', $language);
        $year_date_presentations[$year][$row['start_date']][] = $row;
    }
    unset($row, $children);

    return array(
        'year_date_presentations' => $year_date_presentations,
    );
} // }}}

/**
 * Wykaz szkoleń, które mają podane daty trwania.
 *
 * @param string $language
 */
function scholar_report_trainings($language)
{
    $query = db_query("SELECT * FROM {scholar_generics} g LEFT JOIN {scholar_generic_suppinfo} i ON (i.generic_id = g.id AND i.language = '%s') WHERE g.subtype = 'training' AND g.list <> 0 ORDER BY start_date DESC", $language);

    // szkolenia podzielone na lata
    $year_trainings = array();

    $rows = scholar_db_fetch_all($query);

    foreach ($rows as $row) {
        $year = (string) substr($row['start_date'], 0, 4);

        $row['start_date'] = substr($row['start_date'], 0, 10);
        $row['end_date']   = substr($row['end_date'], 0, 10);
        $row['url'] = scholar_node_url($row['id'], 'generics', $language);

        // pobierz liste wszystkich prowadzacych posortowana w/g nazwiska
        $query = db_query("SELECT * FROM {scholar_people} WHERE id IN (SELECT DISTINCT a.person_id FROM {scholar_generics} g JOIN {scholar_authors} a ON (a.table_name = 'generics' AND a.row_id = g.id) WHERE g.subtype='class' AND g.parent_id = %d) ORDER BY last_name, first_name", $row['id']);
        $authors = scholar_db_fetch_all($query);
        _scholar_page_augment_collection($authors);
        foreach ($authors as &$author) {
            $author['url'] = scholar_node_url($author['id'], 'people', $language);
        }
        $row['authors'] = $authors;

        $year_trainings[$year][] = $row;
    }

    return array(
        'year_trainings' => $year_trainings,
    );
}

/**
 * Lista wszystkich zajęć w obrębie szkolenia o podanym identyfikatorze,
 * pogrupowanych według dnia.
 *
 * @param int $id
 * @param string $language
 */
function scholar_report_training($id, $language)
{
    $children = scholar_generic_load_children($id, 'class', 'start_date, weight');

    $date_classes = array();

    // zajecia sa dzielone wg dnia
    foreach ($children as $row) {
        $date = (string) substr($row['start_date'], 0, 10);
        _scholar_page_augment_record($row, $row['id'], 'generics', $language);
        $row['start_time'] = substr($row['start_date'], 11, 5);
        $row['end_time']   = substr($row['end_date'], 11, 5);
        $date_classes[$date][] = $row;
    }

    return array(
        'date_classes' => $date_classes,
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

// jezeli pierwszym znakiem jest nawias otwierajacy <{[( dodaj details za " "
// w przeciwnym razie dodaj ", "

function scholar_records_conferences_in_category()
{
    

}



// vim: fdm=marker
