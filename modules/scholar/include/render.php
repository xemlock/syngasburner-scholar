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

    // pobierz wszystkie artykuly (razem z ewentualnym tytulem wydawnictw,
    // w ktorych sie znajduja), wsrod ktorych autorow znajduje sie ta osoba
    $articles = array();

    $query = db_query("SELECT g.*, g2.title AS parent_title, g2.url AS parent_url FROM {scholar_authors} a JOIN {scholar_generics} g ON a.generic_id = g.id LEFT JOIN {scholar_generics} g2 ON g.parent_id = g2.id WHERE g.subtype = 'article' AND a.person_id = %d ORDER BY g.start_date DESC", $person->id);
    while ($row = db_fetch_array($query)) {
        $articles[] = $row;
    }

    ob_start();

    // co z ksiazkami? na razie nic :)

    // kazdy z autorow, artykulow i ich rodzicow moze miec podpiety wezel
    // trzeba go pobrac (aliasy zrobia sie same?)
    if ($articles) {
        echo '[section="' . t('Publications', array(), $node->language) . '"]', "\n";

        foreach ($articles as $article) {
            // pobierz pelne listy autorow, posortowanych w odpowiedniej kolejnosci
            $authors = scholar_load_authors($article['id'], 'generics');
            echo '[block="' . intval($article['start_date']) . '"]', "\n";

            // autorzy
            $keys = array_keys($authors);
            for ($i = 0, $n = count($keys); $i < $n; ++$i) {
                $author = $authors[$keys[$i]];

                if ($i == $n - 1) {
                    echo t(' and ');
                } else if ($i > 0) {
                    echo ', ';
                }

                $fn = _scholar_render_escape($author['first_name'] . ' ' . $author['last_name']);

                // sprobuj znalezc link, ale tylko jesli autor nie jest aktualna osoba
                if ($author['id'] != $person->id) {
                    $url = _scholar_node_url($author['id'], 'people', $node->language);
                } else {
                    $url = null;
                }
                if ($url) {
                    echo '[url="' . $url . '"]' . $fn . '[/url]';
                } else {
                    echo $fn;
                }
            }
            // nazwa artykulu (kursywa)
            echo ', [i]', _scholar_render_escape($article['title']) . '[/i]';

            // nazwa czasopisma (hiperlacze)
            $parent = trim($article['parent_title']);
            if ($parent) {
                // parent or external
                $purl = _scholar_node_url($article['parent_id'], 'generics', $node->language);
                if ($purl) {
                    echo ', [url="' . $purl . '" target="_self"]' . _scholar_render_escape($parent) . '[/url]';
                } elseif ($article['parent_url']) {
                    // zewnetrzny url, otworz w nowym oknie
                    echo ', [url="' . $article['parent_url'] . '"]' . _scholar_render_escape($parent) . '[/url]';
                } else {
                    echo ', ', _scholar_render_escape($parent);
                }
            }

            // szczegoly bibliograficzne
            $details = trim($article['details']);
            if ($details) {
                // jezeli szczegoly rozpoczynaja sie od nawiasu otwierajacego nie umieszczaj
                // przed nimi przecinka
                if (false !== strpos("<{([", $details{0})) {
                    echo ' ';
                } else {
                    echo ', ';
                }
                echo _scholar_render_escape($details);
            }

            // TODO zalaczone pliki
            echo '[/block]', "\n";
        }

        echo '[/section]';
    }

    // listowane jako: artykuly
    // <a href="node/x">Imie nazwisko</a>, Imie2 Nazwisko2 and <a href="">Imie3 Nazwisko3</a>, 2010. <a href="">Mixing in the vicinity of the vortex</a>. <a href="">Current Advances in Applied Nonlinear Analysis and Mathematical Modelling Issues, Mathematical Sciences and Applications</a>, Vol.32, 419-427 
    //

    // TODO pobierz wszystkie prezentacje
    // wpisy postaci
    // Data konferencji, <a href="">Nazwa konferencji</a>
    // Kategoria: Tytuł, detale

    $presentations = array();

    // Pobierz wszystkie prezentacje powiazane z ta osoba, dla kazdej prezentacji
    // pobierz nazwe jej kategorii oraz dane konferencji. Poniewaz blok nosi
    // tytul konferencje / seminaria / warsztaty mamy JOIN na generykach a nie LEFT JOIN
    $query = db_query("
        SELECT g.*, c.name AS category_name, i.suppinfo AS details,
               g2.title AS parent_title, g2.url AS parent_url, 
               g2.start_date AS parent_start_date,
               g2.end_date AS parent_end_date,
               g2.locality AS parent_locality,
               g2.country AS parent_country, i2.suppinfo AS parent_details
        FROM {scholar_authors} a
        JOIN {scholar_generics} g
            ON a.generic_id = g.id
        JOIN {scholar_generics} g2
            ON g.parent_id = g2.id
        LEFT JOIN {scholar_category_names} c
            ON g.category_id = c.category_id
        LEFT JOIN {scholar_generic_suppinfo} i
            ON g.id = i.generic_id
        LEFT JOIN {scholar_generic_suppinfo} i2
            ON g2.id = i2.generic_id
        WHERE g.subtype = 'presentation'
            AND a.person_id = %d
            AND (c.language = '%s' OR c.language IS NULL)
            AND (i.language = '%s' OR i.language IS NULL)
            AND (i2.language = '%s' OR i2.language IS NULL)
        ORDER BY g2.start_date DESC
    ", $person->id, $node->language);

    while ($row = db_fetch_array($query)) {
        $presentations[] = $row;
    }

    $roman = array('', 
        'I', 'II', 'III', 'IV',
        'V', 'VI', 'VII', 'VIII',
        'IX', 'X', 'XI', 'XII',
    );

    if ($presentations) {
        $countries = scholar_countries();

        echo '[section="', t('Conferences, seminars, workshops'), '"]', "\n";
        // w przeciwienstwie do artykulow w przypadku prezentacji nie podajemy autorow
        foreach ($presentations as $presentation) {
            $start_date = $presentation['parent_start_date'];
            $year = substr($start_date, 0, 4);
            $month = intval(substr($start_date, 5, 2));
            $month = $roman[$month];

            echo '[block="' . $year . ' ' . $month . '"]', "\n";

            $confname = $presentation['parent_title'];

            echo '[box]', "\n";
            $url = _scholar_node_url($presentation['parent_id'], 'generics', $node->language);
            if ($url) {
                echo '[url="' . $url . '" target="_self"]' . $confname . '[/url]';
            } else if ($presentation['parent_url']) {
                echo '[url="' . $presentation['parent_url'] . '"]' . $confname . '[/url]';
            } else {
                echo $confname;
            }
            $pd = trim($presentation['parent_details']);
            if ($pd) {
                echo ', ' . $pd;
            }

            // specjalna wartosc 'internet' wylacza pokazywanie miejscowosci i kraju
            $locality = trim($presentation['parent_locality']);
            if ($locality && strcasecmp('internet', $locality)) {
                if ($locality) {
                    echo ', ', $locality;
                }
                $country = isset($countries[$presentation['parent_country']])
                    ? $countries[$presentation['parent_country']]
                    : '';
                if ($country) {
                    echo ', ', $country;
                }
            }
            echo '[/box]', "\n";

            echo '[box]', "\n";
            $category = trim($presentation['category_name']);
            if ($category) {
                echo $category, ': ';
            }

            $title = trim($presentation['title']);
            if ($title) {
                echo '[i]', $title, '[/i]';
            }

            $details = trim($presentation['details']);
            if ($details) {
                if ($title) {
                    echo '. ';
                } else {
                    echo ' ';
                }
                echo $details;
            }
            echo '[/box]';

            // TODO zalaczone pliki
            echo '[/block]', "\n";
        }
        
    
        echo '[/section]';
    }

    return ob_get_clean();
}

function scholar_render_generics_node($view, $id, $node)
{
    $generic = scholar_load_record('generics', $id);

    if (empty($generic)) {
        return '';
    }

    $func = 'scholar_render_' . $generic->subtype . '_node';
    if (function_exists($func)) {
        $func($view, $generic, $node);
    }

    return __FUNCTION__;
}

function _render_generics_conference_node($view, $generic, $node)
{
    // wszystkie wystapienia w obrebie konferencji, sortowane wg. wagi a pozniej po nazwisku pierwszego autora
}

function scholar_render_pages_node($view, $id, $node)
{
    $page = scholar_load_record('pages', $id);
    if (!$page) {
        return;
    }

    if (function_exists($page->callback)) {
        return call_user_func($page->callback, $view, $node);
    }
}

// vim: fdm=marker
