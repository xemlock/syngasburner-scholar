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

function _scholar_node_url($id, $table_name, $language, $path = null)
{
    $b = _scholar_fetch_node_binding($id, $table_name, $language);
    if ($b && $b['status']) {
        if (db_table_exists('url_alias')) {
            $qq = db_query("SELECT dst FROM url_alias WHERE pid = %d", $b['pid']);
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

function render_people_node($id, $node)
{
    $person = scholar_load_person($id);
    
    if (empty($person)) {
        return '';
    }

    // pobierz wszystkie artykuly (razem z ewentualnym tytulem wydawnictw,
    // w ktorych sie znajduja), wsrod ktorych autorow znajduje sie ta osoba
    $articles = array();

    $query = db_query("SELECT g.*, g2.title AS parent_title, g2.url AS parent_url FROM {scholar_authors} a JOIN {scholar_generics} g ON a.generic_id = g.id LEFT JOIN {scholar_generics} g2 ON g.parent_id = g2.id WHERE g.subtype = 'article' AND a.person_id = %d ORDER BY start_date DESC", $person->id);
    while ($row = db_fetch_array($query)) {
        $articles[] = $row;
    }

    // pobierz pelne listy autorow, posortowanych w odpowiedniej kolejnosci
    foreach ($articles as &$article) {
        $article['authors'] = scholar_load_authors($article['id']);
    }
    unset($article);

    ob_start();

    // kazdy z autorow, artykulow i ich rodzicow moze miec podpiety wezel
    // trzeba go pobrac (aliasy zrobia sie same?)
    if ($articles) {
        echo '[section="' . t('Articles', array(), $node->language) . '"]';

        foreach ($articles as $article) {
            echo '[block="' . intval($article['start_date']) . '"]', "\n";

            // autorzy
            $keys = array_keys($article['authors']);
            for ($i = 0, $n = count($keys); $i < $n; ++$i) {
                $author = $article['authors'][$keys[$i]];

                if ($i == $n - 1) {
                    echo t(' and ');
                } else if ($i > 0) {
                    echo ', ';
                }

                $fn = _scholar_render_escape($author['first_name'] . ' ' . $author['last_name']);

                // sprobuj znalezc link
                $url = _scholar_node_url($author['id'], 'people', $node->language);
                if ($url) {
                    echo '[url="' . $url . '"]' . $fn . '[/url]';
                } else {
                    echo $fn;
                }

            }
            echo ', [i]', _scholar_render_escape($article['title']) . '[/i]';
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
            $details = trim($article['details']);
            if ($details) {
                if (false !== strpos("<{([", $details{0})) {
                    echo ' ';
                } else {
                    echo ', ';
                }
                echo _scholar_render_escape($details);
            }

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


    return ob_get_clean();
}

function render_categories_node($id)
{
    $category = scholar_load_category($id);

    if (empty($category)) {
        return '';
    }

    return __FUNCTION__;
}

function render_generics_node($id)
{
    $generic = scholar_load_generic($id);

    if (empty($generic)) {
        return '';
    }

    return __FUNCTION__;
}
