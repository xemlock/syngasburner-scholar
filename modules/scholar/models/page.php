<?php

/**
 * Zwraca wypełniony obiekt reprezentujący rekord tabeli stron.
 *
 * @param int $id
 *     identyfikator rekordu
 * @param string $redirect
 *     OPTIONAL jeśli podany nastąpi przekierowanie do podanej strony
 *     z komunikatem o nieprawidłowym identyfikatorze rekordu
 * @return false|object
 */
function scholar_load_page($id, $redirect = '') // {{{
{
    $query = db_query("SELECT * FROM {scholar_pages} WHERE id = %d", $id);
    $page  = db_fetch_object($query);

    if ($page) {
        $page->files = scholar_load_files($page->id, 'pages');
        $page->nodes = scholar_load_nodes($page->id, 'pages');

    } else if ($redirect) {
        drupal_set_message(t('Invalid page identifier supplied (%id)', array('%id' => $id)), 'error');
        return scholar_goto($redirect);
    }

    return $page;
} // }}}

/**
 * Zapisuje zmiany w istniejącym rekordzie strony. Sam rekord jest 
 * niemutowalny, zapisywane sa jedynie rekordy z nim powiązane, takie jak
 * pliki i węzły (segmenty).
 *
 * @param object &$page
 *     obiekt reprezentujący rekord strony
 */
function scholar_save_page(&$page) // {{{
{

    if (isset($page->files)) {
        scholar_save_files($page->id, 'pages', $page->files);
    }

    if (isset($page->nodes)) {
        scholar_save_nodes($page->id, 'pages', $page->nodes);
    }

    scholar_invalidate_rendering();
} // }}}

// vim: fdm=marker
