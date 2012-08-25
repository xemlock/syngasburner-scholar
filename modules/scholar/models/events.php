<?php

/**
 * Pobiera z bazy danych wydarzenia powiązane z rekordem wybranej tabeli.
 *
 * @param int $row_id
 *     identyfikator rekordu
 * @param string $table_name
 *     ciąg znaków identyfikujący tebelę, w której znajduje się rekord
 *     o podanym identyfikatorze
 * @return array
 *     tablica z powiązanymi wydarzeniami, której kluczami są kody języka
 *     każdego z rekordów. Niepowtarzalność kodów języka jest gwarantowana
 *     przez definicję tabeli przechowującej powiązania z rekordami wydarzeń.
 */
function scholar_load_events($row_id, $table_name) // {{{
{
    $rows = array();

    if (module_exists('events')) {
        $rendering = _scholar_rendering_enabled(false);
        $query = db_query("SELECT * FROM {scholar_events} WHERE row_id = %d AND table_name = '%s'", $row_id, $table_name);

        // tutaj dostajemy po jednym evencie na jezyk, eventy sa unikalne
        while ($row = db_fetch_array($query)) {
            $event = events_load_event($row['event_id']);

            if ($event) {
                $event = (array) $event;
                $event['body'] = $row['body']; // nieprzetworzona tresc

                $rows[$event['language']] = $event;
            }
        }

         _scholar_rendering_enabled($rendering);
    }

    return $rows;
} // }}}

/**
 * Zapisuje rekordy wydarzeń i wiąże je z rekordem wybranej tabeli.
 * Funkcja ta nie usuwa powiązań, tylko dopisuje lub nadpisuje rekordy
 * wiążące eventy z rekordami tego modułu.
 *
 * @param int $row_id
 *     identyfikator rekordu
 * @param string $table_name
 *     ciąg znaków identyfikujący tebelę, w której znajduje się rekord
 *     o podanym identyfikatorze
 * @param array $events
 *     tablica z danymi rekordów wydarzeń, zawierająca na pierwszym poziomie
 *     jako klucze kody języków, a jako wartości tablice z danymi pojedynczych
 *     rekordów. Może być to wartość pochodząca z {@see scholar_events_form}.
 * @return int
 *     liczba zapisanych (utworzonych lub zaktualizowanych) rekordów
 */
function scholar_save_events($row_id, $table_name, $events) // {{{
{
    $count = 0;

    if (module_exists('events')) {
        foreach ($events as $language => $event_data) {
            // sprawdz czy istnieje relacja miedzy generykiem a eventem
            $event = false;
            $query = db_query("SELECT * FROM {scholar_events} WHERE table_name = '%s' AND row_id = %d AND language = '%s'", $table_name, $row_id, $language);

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

            // jezeli nie podano wymaganej daty poczatku zdarzenia uzyj czasu 
            // epoki Uniksa
            if (empty($event_data['start_date'])) {
                $event_data['start_date'] = date('Y-m-d H:i:s', 0);
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
                // usun wczesniejsze powiazania...
                db_query("DELETE FROM {scholar_events} WHERE (table_name = '%s' AND row_id = %d AND language = '%s') OR (event_id = %d)",
                    $table_name, $row_id, $language, $event->id
                );

                // ... i dodaj nowe
                db_query("INSERT INTO {scholar_events} (table_name, row_id, event_id, language, body) VALUES ('%s', %d, %d, '%s', '%s')",
                    $table_name, $row_id, $event->id, $language, $body
                );

                ++$count;
            }
        }
    }

    return $count;
} // }}}

/**
 * Usuwa rekordy wydarzeń powiązane z rekordem wybranej tabeli.
 *
 * @param int $row_id
 *     identyfikator rekordu
 * @param string $table_name
 *     ciąg znaków identyfikujący tebelę, w której znajduje się rekord
 *     o podanym identyfikatorze
 */
function scholar_delete_events($row_id, $table_name) // {{{
{
    if (module_exists('events')) {
        $query = db_query("SELECT * FROM {scholar_events} WHERE row_id = %d AND table_name = '%s'", $row_id, $table_name);

        while ($row = db_fetch_array($query)) {
            $event = events_load_event($row['event_id']);
            events_delete_event(&$event);
        }

        db_query("DELETE FROM {scholar_events} WHERE row_id = %d AND table_name = '%s'", $row_id, $table_name);
    }
} // }}}

// vim: fdm=marker
