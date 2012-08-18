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

    db_query("UPDATE {scholar_generics} SET authors = " . scholar_db_quote($bib) . " WHERE id = %d", $generic_id);
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

    if (null !== $subtype) {
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
        if (scholar_db_write_record('scholar_generics', $generic, 'id')) {
            $success = true;
        }
    } else {
        if (scholar_db_write_record('scholar_generics', $generic)) {
            $success = true;
        }
    }

    if ($success) {
        if ($generic->authors) {
            scholar_save_authors($generic->id, $generic->authors);        
        }

        // zapisz dolaczone pliki
        if ($generic->files) {
            scholar_save_files($generic->id, 'generics', $generic->files);
        }

        // zapisz wezly
        if ($generic->nodes) {
            scholar_save_nodes($generic->id, 'generics', $generic->nodes);
        }

        // zapisz zmiany w powiazanych wydarzeniach
        if ($generic->events) {
            scholar_attachments_save_events($generic->id, 'generics', $generic->events);
        }
    }
} // }}}

function scholar_generics_list($subtype) // {{{
{
    $func = 'scholar_' . $subtype . '_list_item';

    if (function_exists($func)) {
        return _scholar_generics_list($subtype, $func);
    }

    drupal_set_message("Unable to retrieve list: Invalid generic subtype '$subtype'", 'error');
} // }}}

function _scholar_generics_list($subtype, $callback)
{
    global $pager_total;

    // funkcja ma zwracac naglowek tabeli, jezeli nie podano wiersza
    $header = call_user_func($callback);

    // sprawdz, czy potrzebna jest kolumna z nazwa kraju, jezeli tak,
    // dodaj ja do zapytania
    $cols = '*';

    foreach ($header as $col) {
        if (isset($col['field']) && 'country_name' == $col['field']) {
            $cols .= ', ' . scholar_db_country_name('country', 'scholar_generics')
                   . ' AS country_name';
            break;
        }
    }

    $rpp = scholar_admin_page_size();
    $sql = "SELECT $cols FROM {scholar_generics} WHERE subtype = " . scholar_db_quote($subtype)
         . tablesort_sql($header);

    $query = pager_query($sql, $rpp, 0, null);
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = call_user_func($callback, $row);
    }

    if (empty($rows)) {
        $colspan = 0;

        foreach ($header as $col) {
            $colspan += isset($col['colspan']) ? max(1, $col['colspan']) : 1;
        }

        $rows[] = array(
            array('data' => t('No records'), 'colspan' => $colspan)
        );
    }

    $html = theme('table', $header, $rows);

    if ($pager_total > 1) {
        $html .= theme('pager', array(), $rpp);
    }

    return $html;
}


/**
 * Funkcja wywołująca formularz dla danego podtypu generycznego.
 * @param array &$form_state
 * @param string $subtype
 */
function scholar_generics_form(&$form_state, $subtype) // {{{
{
    $func = 'scholar_' . $subtype . '_form';

    if ($func != __FUNCTION__ && function_exists($func)) {
        // pobierz argumenty, usun pierwszy, zastap subtype
        // referencja do form_state
        $args = func_get_args();
        array_shift($args);
        $args[0] = &$form_state;

        // pobierz strukture formularza
        $form = call_user_func_array($func, $args);

        // podepnij do niej funkcje obslugujace submit
        if (function_exists($func . '_submit')) {
            $form['#submit'][] = $func . '_submit';
        }

        return $form;
    }

    drupal_set_message("Unable to retrieve form: Invalid generic subtype '$subtype'", 'error');
} // }}}




function scholar_conference_form(&$form_state, $id = null) // {{{
{
    if (null === $id) {
        $record = null;
    } else {
        $record = scholar_load_generic($id, 'conference', 'admin/scholar/conferences');
        $record->start_date = substr($record->start_date, 0, 10);
        $record->end_date   = substr($record->end_date, 0, 10);
    }

    $form = scholar_generic_form(array(
        'title' => t('Conference name'),
        'start_date' => array(
            '#maxlength' => 10,
            '#description' => t('Date format: YYYY-MM-DD.'),
        ), 
        'end_date' => array(
            '#maxlength' => 10,
            '#description' => t('Date format: YYYY-MM-DD. Leave empty if it is the same as the start date.'),
        ),
        'locality', 'country', 'url', 'category',
        'files', 'events', 'nodes'
    
    ), $record);

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => $record ? t('Save changes') : t('Add record'),
    );

    return $form;
} // }}}

function scholar_conference_form_submit($form, &$form_state) // {{{
{
    $record = empty($form['#record']) ? scholar_new_generic() : $form['#record'];
    $values = $form_state['values'];

    // data poczatku i konca maja obcieta czesc zwiazana z czasem,
    // trzeba ja dodac aby byla poprawna wartoscia DATETIME
    $values['start_date'] .= ' 00:00:00';
    if (strlen($values['end_date'])) {
        $values['end_date'] .= ' 00:00:00';
    }

    // dodaj czas do eventow
    foreach ($values['events'] as $language => &$event) {
        $title = trim($event['title']);
        if (0 == strlen($title)) {
            $title = $values['title'];
        }
        $event['title']      = $title;
        $event['start_date'] = $values['start_date'];
        $event['end_date']   = $values['end_date'];
        $event['language']   = $language;
        $event['image_id']   = $values['image_id'];
    }

    // wypelnij rekord danymi z formularza
    scholar_populate_record($record, $values);

    // dla pewnosci ustaw odpowiedni podtyp
    $record->subtype = 'conference';

    scholar_save_generic($record);

    drupal_set_message('OK!');
    drupal_goto('admin/scholar/conferences');
} // }}}

function scholar_presentation_form(&$form_state, $id = null)
{
    if (null === $id) {
        $record = null;
    } else {
        $record = scholar_load_generic($id, 'presentation', 'admin/scholar/presentations');
    }

    $form = scholar_generic_form(array(
        'title',
        'start_date' => t('Data i czas'),
        'parent_id' => t('Konferencja'),
        'files',
        'nodes',
        'events',
        'authors' => t('Prowadzący'),
    ));
    $form['#record'] = $record;
    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => $record ? t('Save changes') : t('Add record'),
    );

    return $form;
}


function scholar_book_form(&$form_state, $id = null)
{
    if (null === $id) {
        $record = null;
    } else {
        $record = scholar_load_generic($id, 'book', 'admin/scholar/books');
    }

    // rendering: $authors: <a href="$url">$title</a> $details
    // <a href="http://syngasburner.eu/pl/publikacje/monografia-ekoenergetyka">"Eco-energetics - biogas and syngas"</a> (red. A. Cenian, J. Gołaszewski i T. Noch)
    // <a href="http://www.springer.com/physics/classical+continuum+physics/book/978-3-642-03084-0">"Advances in Turbulence XII, Proceedings of the Twelfth European Turbulence Conference, September 7–10, 2009, Marburg, Germany"</a>, Springer Proceedings in Physics, Vol. 132
    // jezeli pierwszym znakiem jest nawias otwierajacy <{[( dodaj details za " "
    // w przeciwnym razie dodaj ", "
    $form = scholar_generic_form(array(
        'title',
        'start_date' => array(
            '#title'     => t('Year'),
            '#maxlength' => 4,
            '#required'  => true,
        ),
        'category',
        'people' => array(
            '#title' => t('Authors'),
            '#description' => 'Wypełnij jeżeli książka. Informacje o redakcji umieść w polu \'szczegóły\'.',
        ),
        'details' => array(
            '#title' => 'Szczegóły wydawnicze',
            '#description' => 'Np. redaktorzy, seria wydawnicza, wydawca',
        ),
        'image_id',
        'url',
        'events', // np. info o wydaniu ksiazki
        'nodes',  // dodatkowa wewnetrzna strona poswiecona ksiazce
        'files',  // pliki
    ));
    array_unshift($form, array(
        '#type' => 'fieldset',
        '#title' => 'Pomoc',
        array(
            '#type' => 'markup',
            '#value' => 'To niekoniecznie musi być książka, może to też być czasopismo (jako seria wydawnicza, a nie pojedynczy numer).',
        ),
    ));
    // rok wydania - pozostaw puste, jeżeli jest to seria wydawnicza
    $form['#record'] = $record;
    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => $record ? t('Save changes') : t('Add record'),
    );

    return $form;

    // lista: rok wydania, tytuł, kategoria
}




function scholar_article_form(&$form_state, $id = null)
{
    if (null === $id) {
        $record = null;
    } else {
        $record = scholar_load_generic($id, 'article', scholar_admin_path('article'));
    }

    $form = scholar_generic_form(array(
        'title',
        'start_date' => array(
            '#title'     => t('Year'),
            '#maxlength' => 4,
            '#required'  => true,
            '#default_value' => date('Y'),
        ),
        'category',
        'authors' => array(
            '#description' => 'Pamiętaj o ustawieniu odpowiedniej kolejności autorów.',
        ),
        'details' => array(
            '#title' => 'Szczegóły bibliograficzne',
            '#description' => 'Np. nr tomu, strony',
        ),
        'parent_id', // czasopismo lub książka
        'image_id',
        'url',
        'events', // np. info o wydaniu ksiazki
        'nodes',  // dodatkowa wewnetrzna strona poswiecona ksiazce
        'files',  // pliki
    ));

    if ($record) {
        // intval konczy na pierwszym niepoprawnym znaku, wiec dostaniemy
        // poprawna wartosc roku
        $record->start_date = intval($record->start_date);

        scholar_populate_form($form, $record);
    }

    $form['#record'] = $record;

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $record ? t('Save changes') : t('Add article'),
    );

    return $form;
}

function scholar_article_form_submit($form, &$form_state)
{
    $record = empty($form['#record']) ? scholar_new_generic() : $form['#record'];
    $values = $form_state['values'];

    // poniewaz jako date artykulu zapisuje sie tylko rok, trzeba
    // dodac do niego brakujace znaki, aby byl poprawna wartoscia DATETIME
    $values['start_date'] = sprintf("%04d", $values['start_date']) . '-01-01 00:00:00';
    $values['end_date']   = null;

    // wypelnij rekord danymi z formularza
    scholar_populate_record($record, $values);

    // dla pewnosci ustaw odpowiedni podtyp
    $record->subtype = 'article';

    scholar_save_generic($record);

    drupal_set_message('OK!');
    drupal_goto(scholar_admin_path('article'));
}


/**
 * @return array
 */
function scholar_article_list_item($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('x Year'), 'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Authors'), 'field' => 'authors'),
            array('data' => t('Title'), 'field' => 'title'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        intval($row['start_date']),
        str_replace(' et al.', ' <em>et al.</em>', check_plain($row['authors'])),
        check_plain($row['title']),
        l(t('edit'),  scholar_admin_path('article/edit/' . $row['id'])),
        l(t('delete'), scholar_admin_path('article/delete/' . $row['id'])),
    );
} // }}}

function scholar_book_list_item($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('x Year'), 'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Authors'), 'field' => 'authors'),
            array('data' => t('Title'), 'field' => 'title'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        intval($row['start_date']),
        str_replace(' et al.', ' <em>et al.</em>', check_plain($row['authors'])),
        check_plain($row['title']),
        l(t('edit'),  scholar_admin_path('book/edit/' . $row['id'])),
        l(t('delete'), scholar_admin_path('book/delete/' . $row['id'])),
    );
} // }}}

function scholar_conference_list_item($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('x Date'), 'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'), 'field' => 'title'),
            array('data' => t('Country'), 'field' => 'country_name'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        substr($row['start_date'], 0, 10),
        check_plain($row['title']),
        check_plain($row['country_name']),
        l(t('edit'),  scholar_admin_path('conference/edit/' . $row['id'])),
        intval($row['refcount']) ? '' : l(t('delete'), scholar_admin_path('conference/delete/' . $row['id'])),
    );
} // }}}

function scholar_presentation_list_item($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('x Date'), 'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'), 'field' => 'title'),
            array('data' => t('Country'), 'field' => 'country_name'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        substr($row['start_date'], 0, 10),
        check_plain($row['title']),
        check_plain($row['country_name']),
        l(t('edit'),  scholar_admin_path('presentation/edit/' . $row['id'])),
        intval($row['refcount']) ? '' : l(t('delete'), scholar_admin_path('presentation/delete/' . $row['id'])),
    );
} // }}}

// vim: fdm=marker
