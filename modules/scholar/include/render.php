<?php

    // rendering: $authors: <a href="$url">$title</a> $details
    // <a href="http://syngasburner.eu/pl/publikacje/monografia-ekoenergetyka">"Eco-energetics - biogas and syngas"</a> (red. A. Cenian, J. Gołaszewski i T. Noch)
    // <a href="http://www.springer.com/physics/classical+continuum+physics/book/978-3-642-03084-0">"Advances in Turbulence XII, Proceedings of the Twelfth European Turbulence Conference, September 7–10, 2009, Marburg, Germany"</a>, Springer Proceedings in Physics, Vol. 132
    // jezeli pierwszym znakiem jest nawias otwierajacy <{[( dodaj details za " "
// w przeciwnym razie dodaj ", "
function _scholar_render_escape($str)
{
    return str_replace(array('[', ']'), array('\[', '\]'), $str);
}

function _scholar_render_attr($str)
{
    return str_replace("\"", "''", $str);
}

function _scholar_node_url($id, $table_name, $language)
{
    // FIXME to nie potrzebuje bindingu tylko 2 kolumny, status i node_id
    // language musi byc zrzutowany do stringa, bo gdyby podano
    // null to dostalibysmy wszystkie bindingi
    $b = _scholar_fetch_node_binding($id, $table_name, (string) $language);

    if ($b && $b['status']) {
        if (db_table_exists('url_alias')) {
            $qq = db_query("SELECT dst FROM {url_alias} WHERE pid = %d", $b['pid']);
            $rr = db_fetch_array($qq);
            $alias = $rr ? $rr['dst'] : null;
        }
        $path = $alias ? $alias : 'node/' . $b['node_id'];
    }

    if ($path) {
        return url($path, array('absolute' => true));
    }

    return null;
}

function scholar_render_people_node($view, $id, $node)
{
    $person = scholar_load_record('people', $id);
    
    if (empty($person)) {
        return '';
    }

    $language = $node->language;

    // pobierz wszystkie artykuly (razem z tytulami wydawnictw), 
    // wsrod ktorych autorow znajduje sie ta osoba
    $query = db_query("
        SELECT g.*, g2.title AS parent_title, g2.url AS parent_url
        FROM {scholar_authors} a 
        JOIN {scholar_generics} g 
            ON a.row_id = g.id
        LEFT JOIN {scholar_generics} g2
            ON g.parent_id = g2.id
        WHERE g.subtype = 'article' 
            AND a.person_id = %d
            AND a.table_name = 'generics'
        ORDER BY g.start_date DESC
    ", $person->id);

    $articles = scholar_db_fetch_all($query);
    foreach ($articles as &$article) {
        _scholar_page_augment_record($article, $article['id'], 'generics', $language);

        $year  = intval(substr($article['start_date'], 0, 4));
        $article['year']        = $year ? $year : '';
        $article['bib_details'] = _scholar_publication_details($article['bib_details']);

        if ($article['parent_id']) {
            $url = _scholar_node_url($article['parent_id'], 'generics', $language);
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
        SELECT g.id, g.title, i.suppinfo AS suppinfo, g.url, g.parent_id,
               g2.title AS parent_title, g2.start_date AS parent_start_date,
               g2.end_date AS parent_end_date, i2.suppinfo AS parent_suppinfo,
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
            $conferences[$parent_id] = __scholar_prepare_conference_from_parent_fields($presentation);
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

    return $view
        ->assign('publications_title', t('Publications', array(), $language))
        ->assign('conferences_title', t('Conferences, seminars, workshops', array(), $language))
        ->assign('articles', $articles)
        ->assign('conferences', $conferences)
        ->render('person.tpl');

    // listowane jako: artykuly
    // <a href="node/x">Imie nazwisko</a>, Imie2 Nazwisko2 and <a href="">Imie3 Nazwisko3</a>, 2010. <a href="">Mixing in the vicinity of the vortex</a>. <a href="">Current Advances in Applied Nonlinear Analysis and Mathematical Modelling Issues, Mathematical Sciences and Applications</a>, Vol.32, 419-427 
    //
}

function scholar_render_generics_node($view, $id, $node)
{
    $generic = scholar_load_record('generics', $id);

    if (empty($generic)) {
        return '';
    }

    $func = 'scholar_render_generics_' . $generic->subtype . '_node';
    p($func);
    if (function_exists($func)) {
        return $func($view, $generic, $node);
    }
}

function scholar_render_generics_conference_node($view, $conference, $node)
{
    // wszystkie wystapienia w obrebie konferencji, sortowane wg. dnia, i wagi.
    // Tytul wystapienia musi byc niepusty
    $year_date_presentations = array();

    $children = scholar_generic_load_children($conference->id, 'presentation', 'start_date, weight');

    foreach ($children as &$row) {
        // tylko rekordy, ktore maja niepusty tytul sa brane pod uwage
        // jako wystapienia na konferencji
        if (!strlen($row['title'])) {
            continue;
        }

        // pogrupuj wzgledem roku
        $row['start_date'] = substr($row['start_date'], 0, 10);
        $year = substr($row['start_date'], 0, 4);

        _scholar_page_augment_record($row, $row['id'], 'generics', $node->language);
        $year_date_presentations[$year][$row['start_date']][] = $row;
    }
    unset($row, $children);

    return $view->assign('conference', $conference)
                ->assign('year_date_presentations', $year_date_presentations)
                ->render('conference.tpl');
}

function scholar_render_pages_node($view, $id, $node)
{
    $page = scholar_load_record('pages', $id);

    if ($page && function_exists($page->callback)) {
        return call_user_func($page->callback, $view, $node);
    }
}

// vim: fdm=marker
