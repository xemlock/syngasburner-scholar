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

function scholar_render_people_node($view, $id, $node) // {{{
{
    $vars = scholar_report_person($id, $node->language);

    if (empty($vars)) {
        return '';
    }

    return $view
        ->assignFromArray($vars)
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
    $vars = scholar_report_conference($conference->id, $node->language);

    return $view
        ->assign('conference', (array) $conference)
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
    $vars = scholar_report_conferences($node->language);

    if (empty($vars)) {
        return '';
    }

    return $view
        ->assignFromArray($vars)
        ->render('conferences.tpl');
} // }}}

/**
 * Wywołuje funkcję odpowiedzialną za listowanie rekordów należących do
 * kategorii o podanym identyfikatorze.
 *
 * @param scholar_view $view
 * @param int $id
 * @param object $node
 */
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

// vim: fdm=marker
