<?php

/**
 * @param int $id
 * @param null|string $table_name
 * @param null|string $subtype
 * @return object
 */
function scholar_fetch_category($id, $table_name = false, $subtype = false, $redirect = null) // {{{
{
    $where = array('id' => $id);

    if (false !== $table_name) {
        $where['table_name'] = $table_name;
    }

    // null jest poprawna wartoscia dla podtypu, stad uzycie false
    if (false !== $subtype) {
        $where['subtype'] = $subtype;
    }

    $query = db_query("SELECT * FROM {scholar_categories} WHERE " . scholar_db_where($where));
    $record = db_fetch_object($query);

    if ($record) {
        $names = array();

        // przygotuj miejsce dla nazw kategorii we wszystkich dostepnych jezykach
        foreach (scholar_languages() as $code => $name) {
            $names[$code] = null;
        }

        // pobierz dostepne nazwy kategorii
        $query = db_query("SELECT * FROM {scholar_category_names} WHERE category_id = %d", $record->id);
        while ($row = db_fetch_array($query)) {
            $names[$row['language']] = $row['name'];
        }

        $record->names = $names;

    } elseif (strlen($redirect)) {
        drupal_set_message(t('Invalid category identifier supplied (%id)', array('%id' => $id)), 'error');
        drupal_goto($redirect);
        exit;
    }

    return $record;
} // }}}

/**
 * @param object &$category
 */
function scholar_save_category(&$category) // {{{
{
    if (empty($category->id)) {
        $new = true;
        $sql = "INSERT INTO {scholar_categories} (table_name, subtype) VALUES (" 
             . scholar_db_quote($category->table_name) . ", "
             . scholar_db_quote($category->subtype) . ")";
        db_query($sql);

        $category->id = db_last_insert_id('scholar_categories', 'id');

    } else {
        $new = false;
    }

    // zapisz nazwy
    foreach ($category->names as $language => $name) {
        db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d AND language = '%s'", $category->id, $language);
        db_query("INSERT INTO {scholar_category_names} (category_id, name, language) VALUES (%d, '%s', '%s')", $category->id, $name, $language);
    }

    drupal_set_message($new ? t('Category was added successfully') : t('Category was updated successfully'));
} // }}}

/**
 * Usuwa kategorię. Efektem ubocznym funkcji jest ustawienie komunikatu
 * o pomyślnym usunięciu rekordu.
 *
 * @param object &$category
 */
function scholar_delete_category(&$category) // {{{
{
    global $language;

    db_query("DELETE FROM {scholar_category_names} WHERE category_id = %d", $category->id);
    db_query("DELETE FROM {scholar_categories} WHERE id = %d", $category->id);

    $category->id = null;

    drupal_set_message(t('Category deleted successfully (%name)', array('%name' => $category->names[$language->language])));
} // }}}

/**
 * Zwraca ścieżkę do listy kategorii powiązanych z daną tabelą i opcjonalnie
 * rekordami danego podtypu. Reguła tworzenia ścieżki jest następująca:
 * jeżeli podtyp jest pusty, do nazwy tabeli dołączany jest przyrostek 
 * '/category', jeżeli podana została nazwa podtypu, zostaje ona użyta 
 * w miejscu nazwy tabeli (nazwa tabeli - kontenera jest ignorowana).
 * Nazwy tabel i podtypów muszą być więc unikalne.
 *
 * @param string $table_name OPTIONAL   nazwa tabeli
 * @param string $subtype OPTIONAL      nazwa podtypu
 */
function _scholar_category_path($table_name = null, $subtype = null) // {{{
{
    if (null !== $table_name) {
        $path = (null === $subtype ? $table_name : $subtype) . '/category';
    } else {
        $path = '/';
    }

    return scholar_admin_path($path);
} // }}}

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
            l(t('edit'),  scholar_admin_path('conference/edit/' . $row['id'])),
            intval($row['refcount']) ? '' : l(t('delete'), scholar_admin_path('conference/delete/' . $row['id'])),
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
 * Generator formularzy rekordów generycznych.
 */
function scholar_generic_form($fields = array()) // {{{
{
    $defs = array(
        'title' => array(
            '#type'      => 'textfield',
            '#title'     => t('Title'),
            '#required'  => true,
            '#maxlength' => 255,
        ),
        'details' => array(
            '#type'      => 'textfield',
            '#title'     => t('Details'),
            '#maxlength' => 255,
        ),
        'start_date' => array(
            '#type'      => 'textfield',
            '#title'     => t('Start date'),
            '#maxlength' => 19, // YYYY-MM-DD HH:MM::SS
        ),
        'end_date' => array(
            '#type'      => 'textfield',
            '#title'     => t('End date'),
            '#maxlength' => 19,
        ),
        'locality' => array(
            '#type'      => 'textfield',
            '#title'     => t('Locality'),
            '#maxlength' => 128,
        ),
        'country' => array(
            '#type'      => 'scholar_country',
            '#title'     => t('Country'),
        ),
        'category' => array(
            '#type'      => 'textfield',
            '#title'     => t('Category'),
        ),
        'url' => array(
            '#type'      => 'textfield',
            '#title'     => t('URL'),
            '#maxlength' => 255,
            '#description' => t('Adres URL strony ze szczegółowymi informacjami.'),
        ),
        'parent_id' => array(
            '#type'     => 'textfield',
            '#title'    => t('Parent record'),
        ),
        'image_id' => array(
            '#type'     => 'textfield',
            '#title'    => t('Image'),
        ),
    );

    $form['record'] = array(
        '#type' => 'fieldset',
        '#title' => t('Basic data'),
    );

    foreach ($fields as $key => $value) {
        switch ($value) {
            case 'files':
                $form['files'] = array(
                    '#type' => 'fieldset',
                    '#title' => t('File attachments'),
                );
                $form['files']['files'] = array(
                    '#type' => 'scholar_attachment_manager',
                );
                break;

            case 'nodes':
                $form['nodes'] = array(
                    '#type' => 'fieldset',
                    '#title' => t('Node'),
                );
                $form['nodes']['nodes'] = scholar_nodes_subform();
                break;

            case 'events':
                // TODO konfigurowalne pola eventu
                $form['events'] = array(
                    '#type' => 'fieldset',
                    '#title' => t('Event'),
                );
                $form['events']['events'] = scholar_events_form();
                break;

            default:
                // jezeli podano nazwe formularza jako wartosc, z numerycznym
                // kluczem, uzyj tej nazwy do pobrania definicji pola
                if (is_int($key)) {
                    if (isset($defs[$value])) {
                        $form['record'][$value] = $defs[$value];
                    }
                } elseif (isset($defs[$key])) {
                    // jezeli podano string, zostanie on uzyty jako etykieta,
                    // wartosci typow innych niz string i array zostana zignorowane
                    if (is_string($value)) {
                        $value = array('#title' => $value);
                    }

                    $form['record'][$key] = is_array($value) 
                                          ? array_merge($defs[$key], $value)
                                          : $defs[$key];
                }
                break;
        }
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
        $record = scholar_load_generic($id, 'article', 'admin/scholar/articles');
    }    

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

    return $form;
}

function scholar_category_list($table_name, $subtype = null)
{
    global $language;

    drupal_add_tab(t('Add category'), $_GET['q'] . '/add');

    $header = array(
        array('data' => t('Name'), 'field' => 'n.name', 'sort' => 'asc'),
        array('data' => t('Size'), 'title' => t('Number of category members')),
        array('data' => t('Operations'), 'colspan' => 2),
    );

    // poniewaz subtype moze miec wartosc NULL uzycie placeholderow
    // w db_query byloby niewygodne
    $where = array(
        'c.table_name' => $table_name,
        'c.subtype'    => $subtype,
        'n.language'   => $language->language,
    );

    $query = db_query("SELECT * FROM {scholar_categories} c LEFT JOIN {scholar_category_names} n ON c.id = n.category_id WHERE " . scholar_db_where($where) . tablesort_sql($header));

    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['name']),
            intval($row['refcount']),
            l(t('edit'),   _scholar_category_path($table_name, $subtype) . '/edit/' . $row['id']),
            l(t('delete'), _scholar_category_path($table_name, $subtype) . '/delete/' . $row['id']),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4),
        );
    }

    return theme('table',  $header, $rows);
}

/**
 * Strona z formularzem edycji kategorii.
 *
 * @param array &$form_state
 * @param string $table_name
 * @param string $subtype
 * @param int $id OPTIONAL
 */
function scholar_category_form(&$form_state, $table_name, $subtype = null, $id = null) // {{{
{
    if (null === $id) {
        $is_new = true;

        // pusty rekord, musi miec ustawione pola table_name i subtype,
        // bo beda one niezbedne podczas zapisu do bazy danych
        $record = new stdClass;
        $record->table_name = $table_name;
        $record->subtype = $subtype;

        drupal_add_tab(t('Add category'), $_GET['q']);

    } else {
        $is_new = false;
        $record = scholar_fetch_category($id, $table_name, $subtype, _scholar_category_path($table_name, $subtype));
    }

    $form = array(
        '#record' => $record,
    );

    foreach (scholar_languages() as $code => $name) {
        $form[$code] = array(
            '#type' => 'fieldset',
            '#tree' => true,
            '#title' => scholar_language_label($code, $name),
        );
        $form[$code]['name'] = array(
            '#type' => 'textfield',
            '#title' => t('Name (@language)', array('@language' => $name)),
            '#description' => t('Category name in language: @language', array('@language' => $name)),
            '#required' => true,
            '#default_value' => $record ? $record->names[$code] : null,
        );
    }

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $is_new ? t('Add category') : t('Save changes'),
    );

    return $form;
} // }}}

/**
 * Tworzy lub aktualizuje rekord kategorii na podstawie danych
 * przesłanych w formularzu.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_category_form_submit($form, &$form_state) // {{{
{
    $record = $form['#record'];

    if ($record) {
        $values = $form_state['values'];

        // ustaw nazwy kategorii w dostepnych jezykach
        foreach (scholar_languages() as $code => $name) {
            if (isset($values[$code])) {
                $record->names[$code] = $values[$code]['name'];
            }
        }

        scholar_save_category($record);
        drupal_goto(_scholar_category_path($record->table_name, $record->subtype));
    }
} // }}}

/**
 * Strona z formularzem potwierdzającym usunięcie rekordu kategorii.
 *
 * @param array &$form_state
 * @param int $id
 */
function scholar_category_delete_form(&$form_state, $id) // {{{
{
    global $language;

    $record = scholar_fetch_category($id, false, false, _scholar_category_path());

    $form = array(
        '#record' => $record,
    );

    $form = confirm_form($form,
        t('Are you sure you want to delete category (%name)?', array('%name' => $record->names[$language->language])),
        _scholar_category_path($record->table_name, $record->subtype),
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    return $form;
} // }}}

/**
 * Usuwa rekord kategorii.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_category_delete_form_submit($form, &$form_state) // {{{
{
    $record = $form['#record'];

    if ($record) {
        scholar_delete_category($record);
        drupal_goto(_scholar_category_path($record->table_name, $record->subtype));
    }
} // }}}

// vim: fdm=marker
