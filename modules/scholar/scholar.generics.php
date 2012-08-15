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
        $record->files = scholar_fetch_files($record->id, 'generics');
        $record->nodes = scholar_fetch_nodes($record->id, 'generics');
        $record->events = scholar_attachments_load_events($record->id, 'generics');

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
    $func = 'scholar_' . $subtype . '_list';

    if ($func != __FUNCTION__ && function_exists($func)) {
        return call_user_func($func);
    }

    drupal_set_message("Unable to retrieve list: Invalid generic subtype '$subtype'", 'error');
} // }}}

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

function scholar_conference_list() // {{{
{
    global $pager_total;

    $header = array(
        array('data' => t('Date'), 'field' => 'start_date', 'sort' => 'desc'),
        array('data' => t('Title'), 'field' => 'title'),
        array('data' => t('Country'), 'field' => 'country_name'),
        array('data' => t('Operations'), 'colspan' => '2'),
    );

    $rpp = 25;
    $country_name = scholar_db_country_name('country', 'scholar_generics');
    $sql = "SELECT *, " . $country_name . " AS country_name FROM {scholar_generics} WHERE subtype = 'conference'" . tablesort_sql($header);

    $query = pager_query($sql, $rpp, 0, null);
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        // kraj musi byc stringiem, bo jezeli jest nullem scholar_countries
        // zwroci tablice wszystkich krajow
        $rows[] = array(
            substr($row['start_date'], 0, 10),
            check_plain($row['title']),
            check_plain($row['country_name']),
            l(t('edit'),   "admin/scholar/conferences/edit/{$row['id']}"), 
            intval($row['refcount']) ? '' : l(t('delete'), "admin/scholar/conferences/delete/{$row['id']}"),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records'), 'colspan' => 5)
        );
    }

    $html = theme('table', $header, $rows);

    if ($pager_total > 1) {
        $html .= theme('pager', array(), $rpp);
    }

    return $html;
} // }}}

/**
 * Wypełnia pola formularza odpowiadające rekordowi. Pola bezpośrednio
 * należące do rekordu muszą znajdować się w kontenerze 'record'.
 * @param array &$form
 * @param object &$record
 */
function _scholar_populate_form(&$form, &$record) // {{{
{
    if (isset($form['record'])) {
        $subform = &$form['record'];

        foreach ($record as $key => $value) {
            if (isset($subform[$key]) && is_scalar($value)) {
                $subform[$key]['#default_value'] = $value;
            }
        }

        unset($subform);
    }

    // elementy files, node i events musza znajdowac sie w kontenerach
    // o tej samej nazwie
    if (isset($form['files']['files']) && isset($record->files)) {
        // to jest o tyle proste, ze element files jest attachment_managerem
        $form['files']['files']['#default_value'] = $record->files;
    }

    // wypelnij elementy zwiazane z powiazanymi segmentami
    if (isset($form['nodes']['nodes']) && isset($record->nodes)) {
        $subform = &$form['nodes']['nodes'];

        foreach ($record->nodes as $language => $node) {
            // wartosc checkboksa sterujacego kontenerem
            $subform[$language]['#default_value'] = $node->status;

            $subform[$language]['title']['#default_value'] = $node->title;
            $subform[$language]['body']['#default_value']  = $node->body;

            if ($node->menu) {
                foreach ($node->menu as $key => $value) {
                    $subform[$language]['menu'][$key]['#default_value'] = $value;
                }
            }

            $subform[$language]['menu']['parent']['#default_value'] = $node->menu['menu_name'] . ':' . $node->menu['plid'];
        }

        unset($subform);
    }

    if (isset($form['events']['events']) && isset($record->events)) {
        $subform = &$form['events']['events'];

        foreach ($record->events as $language => $event) {
            $subform[$language]['#default_value'] = $event->status;

            foreach ($event as $key => $value) {
                if (isset($subform[$language][$key])) {
                    $subform[$language][$key]['#default_value'] = $value;
                }
            }
        }

        unset($subform);
    }
} // }}}

/**
 * Wypełnienie rekordu wartościami z odpowiednich pól formularza.
 * @param object &$record
 * @param array $values zwykle wartości ze stanu formularza (form_state[values])
 * @return int  liczba ustawionych wartości
 */
function _scholar_populate_record(&$record, $values) // {{{
{
    // pomijaj nazwy wartosci zwiazane z automatycznie wygenerowanymi
    // dodatkowymi polami formularza
    $omit = array('op', 'submit', 'form_build_id', 'form_token', 'form_id');
    $count = 0;

    foreach ($values as $key => $value) {
        if (in_array($key, $omit)) {
            continue;
        }
        $record->$key = $value;
        ++$count;
    }

    return $count;
} // }}}

/**
 * Funkcja pomocnicza
 * @param array $field
 * @param mixed $options
 * @return array
 */
function _scholar_generic_form_field($field, $options) // {{{
{
    // jezeli podano string, zostanie on uzyty jako etykieta
    if (is_string($options)) {
        $options = array('#title' => $options);
    }

    if (is_array($options)) {
        $field = array_merge($field, $options);
    }

    return $field;
} // }}}

/**
 * Generator formularzy rekordów generycznych.
 */
function scholar_generic_form($fields = array()) // {{{
{
    // mozna tez podac same nazwy pol bez kluczy, wiec przejdz raz
    // tablice i wyznacz, ktore pola nalezy dodac
    foreach ($fields as $key => $value) {
        if (is_int($key) && !isset($fields[$value])) {
            $fields[$value] = true;
        }
    }

    $form['record'] = array(
        '#type' => 'fieldset',
        '#title' => t('Basic data'),
    );

    // pole title jest zawsze obowiazkowe
    $form['record']['title'] = _scholar_generic_form_field(
        array(
            '#type'      => 'textfield',
            '#title'     => t('Title'),
            '#required'  => true,
        ),
        isset($fields['title']) ? $fields['title'] : true
    );

    if (isset($fields['details'])) {
        $form['record']['details'] = _scholar_generic_form_field(
            array(
                '#type'      => 'textfield',
                '#title'     => t('Details'),
                '#maxlength' => 255,
            ),
            $fields['details']
        );
    }

    if (isset($fields['start_date'])) {
        $form['record']['start_date'] = _scholar_generic_form_field(
            array(
                '#type'      => 'textfield',
                '#required'  => true,
                '#title'     => t('Start date'),
            ), 
            $fields['start_date']
        );
    }

    if (isset($fields['end_date'])) {
        $form['record']['end_date'] = _scholar_generic_form_field(
            array(
                '#type'      => 'textfield',
                '#title'     => t('End date'),
            ),
            $fields['end_date']
        );
    }

    if (isset($fields['locality'])) {
        $form['record']['locality'] = _scholar_generic_form_field(
            array(
                '#type'      => 'textfield',
                '#required'  => true,
                '#title'     => t('Locality'),
                '#description' => t('Nazwa miejscowości, gdzie konferencja będzie mieć miejsce.'),
            ),
            $fields['locality']
        );
    }

    if (isset($fields['country'])) {
        $form['record']['country'] = _scholar_generic_form_field(
            array(
                '#type'      => 'scholar_country',
                '#required'  => true,
                '#title'     => t('Country'),
            ),
            $fields['country']
        );
    }

    // TODO kategoria opcjonalna
    if (isset($fields['category'])) {
        $form['record']['category'] = _scholar_generic_form_field(
            array(
                '#type'      => 'textfield',
                '#required'  => true,
                '#title'     => t('Category'),
            ),
            $fields['category']
        );
    }

    if (isset($fields['url'])) {
        $form['record']['url'] = _scholar_generic_form_field(
            array(
                '#type'      => 'textfield',
                '#title'     => t('URL'),
                '#description' => t('Adres URL strony ze szczegółowymi informacjami.'),
            ),
            $fields['url']
        );
    }

    if (isset($fields['files'])) {
        $form['files'] = array(
            '#type' => 'fieldset',
            '#title' => t('File attachments'),
        );
        $form['files']['files'] = array(
            '#type' => 'scholar_attachment_manager',
        );
    }

    if (isset($fields['events'])) {
        $form['events'] = array(
            '#type' => 'fieldset',
            '#title' => t('Event'),
        );
        $form['events']['events'] = scholar_events_form(false);
    }

    if (isset($fields['nodes'])) {
        $form['nodes'] = array(
            '#type' => 'fieldset',
            '#title' => t('Node'),
        );
        $form['nodes']['nodes'] = scholar_nodes_subform();
    }

    return $form;
} // }}}

function scholar_conference_form(&$form_state, $id = null) // {{{
{
    if (null === $id) {
        $record = null;
    } else {
        $record = scholar_load_generic($id, 'conference', 'admin/scholar/conferences');
    }

    $form = scholar_generic_form(array(
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
    
    ));
    $form['#record'] = $record;
    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => $record ? t('Save changes') : t('Add record'),
    );

    if ($record) {
        $record->start_date = substr($record->start_date, 0, 10);
        $record->end_date   = substr($record->end_date, 0, 10);

        _scholar_populate_form($form, $record);
    }

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
    _scholar_populate_record($record, $values);

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
        'start_date' => t('Czas'),
        'parent_id' => t('Konferencja'),
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
        'start_date' => array(
            '#title'     => 'Rok wydania',
            '#maxlength' => 4,
            '#required'  => true,
        ),
        'category',
        'people' => array(
            '#title' => 'Autorzy',
        ),
        'details' => array(
            '#title' => 'Szczegóły wydawnicze',
            '#description' => 'Np. redaktorzy, seria wydawnicza, wydawca',
        ),
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
        $record = scholar_load_generic($id, 'article', 'admin/scholar/articles');
    }    


}

// vim: fdm=marker
