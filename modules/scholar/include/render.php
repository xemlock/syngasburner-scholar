<?php

/**
 * Ustawia albo zwraca wartość sterującą renderingiem węzłów (segmentów).
 * Jeżeli nie podano żadnego argumentu zwrócona zostanie aktualna
 * wartość. Jeżeli podano nową, zostanie ona ustawiona, przy czym zwrócona
 * zostanie poprzednia wartość.
 *
 * @param bool $enabled
 *     opcjonalny, true żeby włączyć renderowanie, false aby wyłączyć
 * @return bool
 *     jeżeli podano nową wartość $enabled funkcja zwraca poprzednią.
 *     Jeżeli funkcję wywołano bez argumentu zwrócona zostanie aktualna
 *     wartość ustawienia.
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
 * Funkcja wywoływana po pomyślnym zapisie lub usunięciu rekordów osób,
 * kategorii i rekordów generycznych oraz przy usuwaniu / zmianie nazwy plików.
 * Zmiana lub usunięcie wydarzeń i węzłów nie wpływa na rendering. 
 */
function scholar_invalidate_rendering() // {{{
{
    variable_set('scholar_last_change', date('Y-m-d H:i:s'));
} // }}}

/**
 * Generuje kod BBCode prezentujący spis publikacji i wystąpień osoby
 * o podanym identyfikatorze.
 *
 * @param scholar_view $view
 * @param int $id
 * @param object $node
 * @return string
 */
function scholar_render_people_node($view, $id, $node) // {{{
{
    $person = scholar_load_record('people', $id);

    if (empty($person)) {
        return;
    }
    
    $vars = scholar_report_person($id, $node->language);

    if (empty($vars)) {
        return;
    }

    return $view
        ->assignFromArray($vars)
        ->assign('person', (array) $person)
        ->render('person.tpl');
} // }}}

/**
 * Funkcja pomocnicza wywołująca funkcję odpowiedzialną za generowanie kodu
 * BBCode dla rekordu generycznego o podanym identyfikatorze. Wywoływana
 * funkcja dla danego podtypu musi mieć nazwę postaci:
 * <code>scholar_render_generics_{subtype}_node</code>
 *
 * @param scholar_view $view
 * @param int $id
 * @param object $node
 */
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

/**
 * Generuje kod BBCode prezentujący spis prezentacji w obrębie podanej
 * konferencji.
 *
 * @param scholar_view $view
 * @param object $conference
 * @param object $node
 * @return string
 */
function scholar_render_generics_conference_node($view, $conference, $node) // {{{
{
    $vars = scholar_report_conference($conference->id, $node->language);

    return $view
        ->assignFromArray($vars)
        ->assign('conference', (array) $conference)
        ->render('conference.tpl');
} // }}}

function scholar_render_generics_training_node($view, $training, $node) // {{{
{
    $vars = scholar_report_training($training->id, $node->language);

    return $view
        ->assignFromArray($vars)
        ->assign('training', (array) $training)
        ->render('training.tpl');
} // }}}


/**
 * Funkcja pomocnicza wywołująca funkcję odpowiedzialną za generowanie kodu
 * BBCode dla strony specjalnej o podanym identyfikatorze. Wywoływana funkcja
 * dla danego podtypu strony musi mieć nazwę postaci:
 * <code>scholar_render_pages_{subtype}_node</code>
 *
 * @param scholar_view $view
 * @param int $id
 * @param object $node
 */
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

/**
 * Generuje kod BBCode prezentujący spis wszystkich publikacji zapisanych
 * w bazie danych.
 *
 * @param scholar_view $view
 * @param object $node
 * @return string
 */
function scholar_render_pages_publications_node($view, $node) // {{{
{
    $vars = scholar_report_publications($node->language);

    if (empty($vars)) {
        return;
    }

    return $view
        ->assignFromArray($vars)
        ->render('publications.tpl');
} // }}}

/**
 * Generuje kod BBCode spisu wszystkich prezentacji na konferencjach zapisanych
 * w bazie danych.
 *
 * @param scholar_view $view
 * @param object $node
 * @return string
 */
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

function scholar_render_pages_trainings_node($view, $node)
{
    $vars = scholar_report_trainings($node->language);

    if (empty($vars)) {
        return '';
    }

    return $view
        ->assignFromArray($vars)
        ->render('trainings.tpl');
}

/**
 * Funkcja pomocnicza wywołująca funkcję odpowiedzialną za listowanie rekordów
 * należących do kategorii o podanym identyfikatorze. Wywoływana funkcja
 * dla danej tabeli i opcjonalnie danego podtypu musi mieć nazwę postaci
 * (w nawiasach kwadratowych ujęta została opcjonalna część nazwy):
 * <code>scholar_render_categories_{table_name}[_{subtype}]_node</code>
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
