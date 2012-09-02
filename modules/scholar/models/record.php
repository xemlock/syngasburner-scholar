<?php

/**
 * Pobiera rekord z bazy danych. Gdy rekord zostanie znaleziony w tabeli
 * odpowiadającej podanemu modelowi zostanie wywołany hook load Record API
 * właściwy dla podanego modelu.
 *
 * @param string $model
 *     nazwa modelu rekordu
 * @param int|array $id
 *     warunki określające kryterium wyszukiwania rekordu. Jeżeli podano
 *     wartość skalarną, zostanie ona użyta jako wartość kolumny 'id'
 * @param string $redirect
 *     opcjonalny adres do przekierowania jeżeli rekord nie został odnaleziony.
 *     Jeżeli wśród warunków wyszukiwania podana będzie wartość kolumny id,
 *     przekierowaniu towarzyszyć będzie komunikat błędu o niepoprawnym
 *     identyfikatorze rekordu.
 * @param false|object
 */
function scholar_load_record($model, $id, $redirect = false) // {{{
{
    $where = is_array($id) ? $id : array('id' => $id);
    $query = db_query("SELECT * FROM {scholar_{$model}} WHERE " . scholar_db_where($where));

    $record = db_fetch_object($query);

    if ($record) {
        $record->authors = scholar_load_authors($record->id, $model);
        $record->files   = scholar_load_files($record->id, $model);
        $record->nodes   = scholar_load_nodes($record->id, $model);
        $record->events  = scholar_load_events($record->id, $model);

        _scholar_invoke_record('load', $model, $record);

    } else if ($redirect) {
        if (isset($where['id'])) {
            drupal_set_message(t('Invalid record identifier supplied (%id)', array('%id' => $where['id']), 'error'));
        }
        return scholar_goto($redirect);
    }

    return $record;
} // }}}

/**
 * Zapisuje rekord do bazy danych.
 *
 * @param string $model
 * @param object &$record
 * @return bool
 */
function scholar_save_record($model, &$record) // {{{
{
    // przygotuj pola rekordu do zapisu
    _scholar_invoke_record('presave', $model, $record);

    // zapisz rekord
    if ($record->id) {
        $success = scholar_db_write_record('scholar_' . $model, $record, 'id');
    } else {
        $success = scholar_db_write_record('scholar_' . $model, $record);
    }

    // zapisz powiazane rekordy
    if ($success) {
        if (isset($record->authors) && is_array($record->authors)) {
            scholar_save_authors($record->id, $model, $record->authors);
        }

        // zapisz dolaczone pliki
        if (isset($record->files) && is_array($record->files)) {
            scholar_save_files($record->id, $model, $record->files);
        }

        // zapisz wezly
        if (isset($record->nodes) && is_array($record->nodes)) {
            scholar_save_nodes($record->id, $model, $record->nodes);
        }

        // zapisz zmiany w powiazanych wydarzeniach
        if (isset($record->events) && is_array($record->events)) {
            scholar_save_events($record->id, $model, $record->events);
        }

        _scholar_invoke_record('postsave', $model, $record);
        scholar_invalidate_rendering();
    }

    return $success;
} // }}}

/**
 * Usuwa rekord. Po wykonaniu tej funkcji właściwości id rekordu zostaje nadana
 * pusta wartość (null).
 *
 * @param string $model
 * @param pbject &$record
 */
function scholar_delete_record($model, &$record) // {{{
{
    _scholar_invoke_record('predelete', $model, $record);

    // usuniecie autorow
    scholar_delete_authors($record->id, $model);

    // usuniecie powiazan z plikami
    scholar_delete_files($record->id, $model);

    // usuniecie wezlow
    scholar_delete_nodes($record->id, $model);

    // usuniecie wydarzen
    scholar_delete_events($record->id, $model);

    db_query("DELETE FROM {scholar_{$model}} WHERE id = %d", $record->id);

    $record->id = null;

    scholar_invalidate_rendering();

    _scholar_invoke_record('postdelete', $model, $record);
} // }}}

function _scholar_invoke_record($hook, $model, &$record) // {{{
{
    $func = "scholar_{$hook}_{$model}_record";

    if (function_exists($func)) {
        call_user_func_array($func, array(&$record));
    }
} // }}}

// vim: fdm=marker
