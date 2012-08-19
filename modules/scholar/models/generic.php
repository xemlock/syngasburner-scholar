<?php

/**
 * Pobiera z bazy danych wydarzenia powiązane z rekordem podanej tabeli.
 * @param int $row_id
 * @param string $table_name
 * @return array
 */
function scholar_attachments_load_events($row_id, $table_name) // {{{
{
    $rows = array();

    if (module_exists('events')) {
        $query = db_query("SELECT * FROM {scholar_events} WHERE generic_id = %d", $row_id);

        // tutaj dostajemy po jednym evencie na jezyk, eventy sa unikalne
        while ($row = db_fetch_array($query)) {
            $event = events_load_event($row['event_id']);
            if ($event) {
                $event->body = $row['body']; // nieprzetworzona tresc
                $rows[$event->language] = $event;
            }
        }
    }

    return $rows;
} // }}}

/**
 * @param array $events tablica nowych wartości eventów
 * @return int          liczba zapisanych (utworzonych / zaktualizowanych) rekordów
 */
function scholar_attachments_save_events($row_id, $table_name, $events) // {{{
{
    $count = 0;

    if (module_exists('events')) {
        // zapisz dowiazane eventy, operuj tylko na wezlach w poprawnych jezykach
        foreach ($events as $language => $event_data) {
            // sprawdz czy istnieje relacja miedzy generykiem a eventem
            $event = false;
            $query = db_query("SELECT * FROM {scholar_events} WHERE generic_id = %d AND language = '%s'", $row_id, $language);

            if ($binding = db_fetch_array($query)) {
                $event = events_load_event($binding['event_id']);
            }

            $status = intval($event_data['status']) ? 1 : 0;

            // jezeli nie bylo relacji lub jest niepoprawna utworz nowy event
            if (empty($event)) {
                if (!$status) {
                    // zerowy status i brak rekordu wydarzenia - nie dodawaj
                    continue;
                }

                $event = events_new_event();
            }

            // skopiuj dane do eventu...
            foreach ($event_data as $key => $value) {
                // ... pilnujac, zeby nie zmienic klucza glownego!
                if ($key == 'id') {
                    continue;
                }
                $event->$key = $value;
            }

            // opis wydarzenia bedzie generowany automatycznie, ustaw zaslepke
            $body = $event->body;
            $event->body     = '';
            $event->status   = $status;
            $event->language = $language;

            // zapisz event
            if (events_save_event($event)) {
                // usun wczesniejsze powiazania
                db_query("DELETE FROM {scholar_events} WHERE (generic_id = %d AND language = '%s') OR (event_id = %d)",
                    $row_id, $language, $event->id
                );
                // dodaj nowe
                db_query("INSERT INTO {scholar_events} (generic_id, event_id, language, body) VALUES (%d, %d, '%s', '%s')",
                    $row_id, $event->id, $language, $body
                );
                ++$count;
            }
        }
    }

    return $count;
} // }}}

function scholar_new_generic() // {{{
{
    $record = new stdClass;
    $schema = drupal_get_schema('scholar_generics');

    if ($schema) {
        foreach ($schema['fields'] as $field => $info) {
            $record->$field = null;
        }
    }

    return $record;
} // }}}

/**
 * @return array
 */
function scholar_fetch_authors($generic_id) // {{{
{
    $query = db_query("SELECT p.id, p.first_name, p.last_name FROM {scholar_authors} a JOIN {scholar_people} p ON a.person_id = p.id WHERE a.generic_id = %d ORDER BY a.weight", $generic_id);
    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
} // }}}

/**
 * @param int $generic_id
 * @param array $authors
 */
function scholar_save_authors($generic_id, $authors) // {{{
{
    // dla wszystkich identyfikatorow osob (rekordow w tabeli people)
    // w podanej tablicy sprawdz czy sa one poprawne

    $ids = array();
    foreach ($authors as $person) {
        $ids[$person['id']] = false;
    }

    $where = array('id' => array_keys($ids));
    $query = db_query("SELECT id, last_name FROM {scholar_people} WHERE " . scholar_db_where($where));

    while ($row = db_fetch_array($query)) {
        $ids[$row['id']] = $row;
    }

    // dodaj tylko te rekordy, ktore sa poprawne
    db_query("DELETE FROM {scholar_authors} WHERE generic_id = %d", $generic_id);

    $names = array();

    foreach ($authors as $person) {
        $person_id = $person['id'];

        if (false === $ids[$person_id]) {
            continue;
        }

        db_query("INSERT INTO {scholar_authors} (generic_id, person_id, weight) VALUES (%d, %d, %d)", $generic_id, $person_id, $person['weight']);

        if (count($names) < 4) {
            $names[] = $ids[$person_id]['last_name'];
        }
    }

    $bib = scholar_bib_authors($names);

    db_query("UPDATE {scholar_generics} SET bib_authors = " . scholar_db_quote($bib) . " WHERE id = %d", $generic_id);
} // }}}

/**
    // pobieramy co najwyzej czterech autorow, jezeli jest dwoch
    // uzyj ampersandu, jezeli trzech uzyj przecinka i ampersandu,
    // jezeli czterech i wiecej uzyj et al.
 * @return string
 */
function scholar_bib_authors($names) // {{{
{
    if (count($names) > 4) {
        $names = array_slice($names, 0, 4);
    }

    switch (count($names)) {
        case 4:
            $bib = $names[0] . ' et al.';
            break;

        case 3:
            $bib = $names[0] . ', ' . $names[1] . ' & ' . $names[2];
            break;

        case 2:
            $bib = $names[0] . ' & ' . $names[1];
            break;

        case 1:
            $bib = $names[0];
            break;

        default:
            $bib = null;
            break;
    }

    return $bib;
} // }}}

/**
 * Zwraca wypełniony obiekt reprezentujący rekord tabeli generyków.
 * @param int $id               identyfikator rekordu
 * @param string $subtype       OPTIONAL wymagany podtyp rekordu
 * @param string $redirect      OPTIONAL jeśli podany nastąpi przekierowanie do
 *                              podanej strony w komunikatem o nieprawidłowym
 *                              identyfikatorze rekordu
 * @return false|object
 */
function scholar_load_generic($id, $subtype = null, $redirect = null) // {{{
{
    $where = array();
    $where['id'] = $id;

    if ($subtype) {
        $where['subtype'] = $subtype;    
    }

    $query = db_query("SELECT * FROM {scholar_generics} WHERE " . scholar_db_where($where));
    $record = db_fetch_object($query);

    if ($record) {
        $record->authors = scholar_fetch_authors($record->id);
        $record->files   = scholar_fetch_files($record->id, 'generics');
        $record->nodes   = scholar_fetch_nodes($record->id, 'generics');
        $record->events  = scholar_attachments_load_events($record->id, 'generics');

    } else if ($redirect) {
        drupal_set_message(t('Invalid record identifier supplied (%id)', array('%id' => $id)), 'error');
        drupal_goto($redirect);
        exit;
    }

    return $record;
} // }}}

/**
 * @param object &$generic
 */
function scholar_save_generic(&$generic) // {{{
{
    $success = false;

    foreach (get_object_vars($generic) as $key => $value) {
        if (is_string($value)) {
            $value = trim($value);
            $generic->$key = strlen($value) ? $value : null;
        }
    }

    if ($generic->id) {
        $is_new = false;
        if (scholar_db_write_record('scholar_generics', $generic, 'id')) {
            $success = true;
        }
    } else {
        $is_new = true;
        if (scholar_db_write_record('scholar_generics', $generic)) {
            $success = true;
        }
    }

    if ($success) {
        if (isset($generic->authors)) {
            scholar_save_authors($generic->id, $generic->authors);
        }

        // zapisz dolaczone pliki
        if (isset($generic->files)) {
            scholar_save_files($generic->id, 'generics', $generic->files);
        }

        // zapisz wezly
        if (isset($generic->nodes)) {
            scholar_save_nodes($generic->id, 'generics', $generic->nodes);
        }

        // zapisz zmiany w powiazanych wydarzeniach
        if (isset($generic->events)) {
            scholar_attachments_save_events($generic->id, 'generics', $generic->events);
        }

        drupal_set_message($is_new
            ? t('Record created successfully (%title)', array('%title' => $generic->title))
            : t('Record updated successfully (%title)', array('%title' => $generic->title))
        );
    }
} // }}}

/**
 * Lista dostępnych rekordów rodzica, w podziale na kategorie.
 */
function scholar_generic_parent_options($subtype = null) // {{{
{
    global $language;

    $where = array(
        '?n.language' => $language->language,
    );

    if ($subtype) {
        $where['g.subtype'] = $subtype;
    }

    $query = db_query("SELECT g.id, g.title, n.name AS category_name FROM {scholar_generics} g LEFT JOIN {scholar_category_names} n ON g.category_id = n.category_id WHERE " . scholar_db_where($where) . " ORDER BY n.name, g.title");

    $options = array(
        0 => '', // pusty rodzic
    );

    while ($row = db_fetch_array($query)) {
        $category_name = $row['category_name'];

        if (empty($category_name)) {
            $category_name = t('uncategorized');
        }

        $options[$category_name][$row['id']] = $row['title'];
    }

    return $options;
} // }}}

// vim: fdm=marker
