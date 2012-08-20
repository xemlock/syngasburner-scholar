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
 * @param array $events
 *     tablica nowych wartości eventów
 * @return int
 *     liczba zapisanych (utworzonych / zaktualizowanych) rekordów
 */
function scholar_attachments_save_events($row_id, $table_name, $events) // {{{
{
    $count = 0;

    if (module_exists('events')) {
        // zapisz dowiazane eventy, operuj tylko na wezlach w aktualnie
        // dostepnych jezykach
        p($events);
        foreach (scholar_languages() as $language => $event_data) {


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

function scholar_delete_events($row_id, $table_name)
{
}

// vim: fdm=marker
