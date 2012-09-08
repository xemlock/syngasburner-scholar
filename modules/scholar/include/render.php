<?php

/**
 * Ustawia albo zwraca wartość sterującą renderingiem węzłów (segmentów).
 * Jeżeli nie podano żadnego argumentu zwrócona zostanie aktualna
 * wartość. Jeżeli podano nową, zostanie ona ustawiona, przy czym zwrócona
 * zostanie poprzednia wartość.
 *
 * @param bool $enabled OPTIONAL        true żeby włączyć renderowanie,
 *                                      false aby wyłączyć
 * @return bool
 */
function _scholar_rendering_enabled($enabled = null) // {{{
{
    static $_enabled = true;

    if (null !== $enabled) {
        $previous = $_enabled;
        $_enabled = (bool) $enabled;

        return $previous;
    }

    return $_enabled;
} // }}}

/**
 * Funkcja wywoływana po pomyślnym zapisie lub usunięciu rekordów
 * osób, kategorii i rekordów generycznych oraz przy usuwaniu / zmianie nazwy plików.
 * Zmiana lub usunięcie wydarzeń i węzłów nie wpływa na rendering. 
 */
function scholar_invalidate_rendering() // {{{
{
    variable_set('scholar_last_change', date('Y-m-d H:i:s'));
} // }}}

    // rendering: $authors: <a href="$url">$title</a> $details
    // <a href="http://syngasburner.eu/pl/publikacje/monografia-ekoenergetyka">"Eco-energetics - biogas and syngas"</a> (red. A. Cenian, J. Gołaszewski i T. Noch)
    // <a href="http://www.springer.com/physics/classical+continuum+physics/book/978-3-642-03084-0">"Advances in Turbulence XII, Proceedings of the Twelfth European Turbulence Conference, September 7–10, 2009, Marburg, Germany"</a>, Springer Proceedings in Physics, Vol. 132
    // jezeli pierwszym znakiem jest nawias otwierajacy <{[( dodaj details za " "
// w przeciwnym razie dodaj ", "

function scholar_render_people_node($view, $id, $node) // {{{
{
    $vars = scholar_report_person($id, $node->language);

    if (empty($vars)) {
        return '';
    }

    return $view
        ->assignFromVars($vars)
        ->render('person.tpl');
} // }}}

function scholar_render_generics_node($view, $id, $node) // {{{
{
    $generic = scholar_load_record('generics', $id);
    if ($generic) {
        $func = 'scholar_render_generics_' . $generic->subtype . '_node';
        if (function_exists($func)) {
            return $func($view, $generic, $node);
        }
    }
} // }}}

function scholar_render_generics_conference_node($view, $conference, $node) // {{{
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

        // pogrupuj wzgledem roku i dnia, rzutowanie na string, w przeciwnym
        // razie false bedace wynikiem substr zostanie skonwertowane do 0
        $row['start_date'] = (string) substr($row['start_date'], 0, 10);
        $year = (string) substr($row['start_date'], 0, 4);

        _scholar_page_augment_record($row, $row['id'], 'generics', $node->language);
        $year_date_presentations[$year][$row['start_date']][] = $row;
    }
    unset($row, $children);

    return $view->assign('conference', $conference)
                ->assign('year_date_presentations', $year_date_presentations)
                ->render('conference.tpl');
} // }}}

function scholar_render_pages_node($view, $id, $node) // {{{
{
    $page = scholar_load_record('pages', $id);

    if ($page) {
        $func = 'scholar_render_pages_' . $page->subtype . '_node';

        if (function_exists($func)) {
            return call_user_func($func, $view, $node);
        }
    }
} // }}}

function scholar_render_pages_publications_node($view, $node) // {{{
{
    $vars = scholar_report_publications($node->language);

    if (empty($vars)) {
        return '';
    }

    return $view
        ->assignFromArray($vars)
        ->render('publications.tpl');
} // }}}

function scholar_render_pages_conferences_node($view, $node) // {{{
{
    $language = $node->language;

    // pobierz tylko te  prezentacje, ktore naleza do konferencji (INNER JOIN),
    // oraz maja niepusty tytul (LENGTH dostepna jest wszedzie poza MSSQL Server)
    // country name, locality , kategoria. Wystepienia w obrebie
    // konferencji posortowane sa alfabetycznie po nazwisku pierwszego autora.
    // Jezeli konferencja ma pusta date poczatku, uzyj daty prezentacji jako
    // poczatku i konca konferencji --> przydatne gdy mamy konferencje dlugoterminowe
    // (np. seminaria)
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
        ORDER BY g2.start_date DESC, g.start_date, g.weight
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

// wywołuje odpowiednią funkcję odpowiedzialną za listowanie rekordów
// w danej kategorii.
function scholar_render_categories_node($view, $id, $node) // {{{
{
    $category = scholar_load_record('categories', $id);
    if ($category) {
        $func = 'scholar_render_categories_' . $category->table_name
              . ($category->subtype ? '_' . $category->subtype : '')
              . '_node';
        if (function_exists($func)) {
            return $func($view, $category, $node);
        }
    }
} // }}}

// wszystkie konferencje w danej kategorii, np. lista szkoleń
function scholar_render_categories_generics_conference_node($view, $category, $node)
{

}

// vim: fdm=marker
