<?php

/**
 * Do formularzy o identyfikatorze rozpoczynającym się od scholar_
 * ustawia odpowiednie callbacki oraz atrybut class.
 *
 * @param array &$form
 */
function scholar_prepare_form(&$form) // {{{
{
    // callback #submit o nazwie takiej jak nazwa formularza
        // z przyrostkiem _submit jest automaycznie dodawany przez
        // drupal_prepare_form() wywolywana przez drupal_get_form().

        $form['#validate'] = isset($form['#validate'])
            ? array_map('strtolower', (array) $form['#validate'])
            : array();

        $validate_callback = $form_id . '_validate';
        if (function_exists($validate_callback) && !in_array($validate_callback, $form['#validate'])) {
            $form['#validate'][] = $validate_callback;
        }

        $form['#attributes'] = isset($form['#attributes']) ? (array) $form['#attributes'] : array();
        if (!isset($form['#attributes']['class'])) {
            $form['#attributes']['class'] = 'scholar';
        } else {
            $form['#attributes']['class'] .= ' scholar';
        }
} // }}}

/**
 * Deklaracja dodatkowych pól formularza.
 *
 * @return array
 */
function scholar_elements() // {{{
{
    $elements = array();

    $elements['scholar_textarea'] = array(
        '#input'            => true,
    );

    $elements['scholar_country'] = array(
        '#input'            => true,
        '#options'          => scholar_countries(),
        '#default_value'    => 'PL',
        '#theme'            => 'select',
    );
    $elements['scholar_date'] = array(
        '#input'            => true,
        '#maxlength'        => 10,
        '#yearonly'         => false,
    );

    $elements['scholar_element_events'] = array(
        '#input'            => true,
        '#fields'           => null,
        '#process'          => array('form_type_scholar_element_events_process'),
        '#element_validate' => array('form_type_scholar_element_events_validate'),
    );

    $elements['scholar_element_files'] = array(
        '#input'            => true,
        '#element_validate' => array('form_type_scholar_element_files_validate'),
    );

    $elements['scholar_element_langtext'] = array(
        '#input'            => true,
        '#process'          => array('form_type_scholar_element_langtext_process'),
        '#element_validate' => array('form_type_scholar_element_langtext_validate'),
    );

    $elements['scholar_element_people'] = array(
        '#input'            => true,
    );

    $elements['scholar_element_time'] = array(
        '#input'            => true,
        '#process'          => array('form_type_scholar_element_time_process'),
        '#element_validate' => array('form_type_scholar_element_time_validate'),
    );

    $elements['scholar_element_timespan'] = array(
        '#input'            => true,
        '#process'          => array('form_type_scholar_element_timespan_process'),
        '#element_validate' => array('form_type_scholar_element_timespan_validate'),
    );

    $elements['scholar_checkboxed_container'] = array(
        '#input'            => true,
        '#checkbox_name'    => 'status',
    );

    $elements['scholar_element_vtable'] = array(
        // #input ustawione na true powoduje, ze atrybuty #name i #id ustawiane
        // sa automatycznie. Efektem ubocznym jest dodatkowa wartosc vtable
        // w tablicy form_state[values]
        '#input'            => false,
    );

    $elements['scholar_element_vtable_row'] = array(
        '#input'            => false,
        '#title'            => null,
        '#description'      => null, // jezeli bedzie pusty string, #description
                                     // stanie sie tablica
    );

    return $elements;
} // }}}

/**
 * Funkcja wymagana do renderowania dodatkowych elementów formularza.
 *
 * @return array
 */
function scholar_elements_theme() // {{{
{
    $theme_arguments = array('arguments' => array('element' => null));

    $theme = array();

    $theme['scholar_country']              = $theme_arguments;
    $theme['scholar_date']                 = $theme_arguments;
    $theme['scholar_textarea']             = $theme_arguments;
    $theme['scholar_checkboxed_container'] = $theme_arguments;
    $theme['scholar_element_events']       = $theme_arguments;
    $theme['scholar_element_files']        = $theme_arguments;
    $theme['scholar_element_langtext']     = $theme_arguments;
    $theme['scholar_element_people']       = $theme_arguments;
    $theme['scholar_element_time']         = $theme_arguments;
    $theme['scholar_element_timespan']     = $theme_arguments;
    $theme['scholar_element_vtable']       = $theme_arguments;
    $theme['scholar_element_vtable_row']   = $theme_arguments;

    return $theme;
} // }}}

/**
 * Wypełnia pola formularza odpowiadające rekordowi. Pola bezpośrednio
 * należące do rekordu muszą znajdować się w kontenerze 'record'.
 * @param array &$form
 * @param object &$record
 */
function scholar_populate_form(&$form, &$record) // {{{
{
    $form_ptr = &$form['vtable'];

    if (isset($form_ptr['record'])) {
        $subform = &$form_ptr['record'];

        foreach ($record as $key => $value) {
            if (isset($subform[$key])) {
                $subform[$key]['#default_value'] = $value;
            }
        }

        unset($subform);
    }

    // elementy files, node i events musza znajdowac sie w kontenerach
    // o tej samej nazwie
    if (isset($form_ptr['files']) && isset($record->files)) {
        $form_ptr['files']['files']['#default_value'] = $record->files;
    }

    // wypelnij elementy zwiazane z powiazanymi segmentami
    if (isset($form_ptr['nodes']) && isset($record->nodes)) {
        $subform = &$form_ptr['nodes']['nodes'];

        foreach ($record->nodes as $language => $node) {
            // wartosc checkboksa sterujacego kontenerem
            $subform[$language]['#default_value'] = $node->status;

            $subform[$language]['title']['#default_value'] = $node->title;
            $subform[$language]['body']['#default_value']  = $node->body;

            // wartosci powiazanego elementu menu
            if (isset($node->menu)) {
                foreach ($node->menu as $key => $value) {
                    $subform[$language]['menu'][$key]['#default_value'] = $value;
                }

                $subform[$language]['menu']['parent']['#default_value'] = $node->menu['menu_name'] . ':' . $node->menu['plid'];
            }

            // wartosci dla aliasu sciezki
            if (isset($node->path)) {
                $subform[$language]['path']['path']['#default_value'] = $node->path;
            }

            // wartosci dla galerii
            if (isset($node->gallery)) {
                $subform[$language]['gallery']['id']['#default_value'] = $node->gallery['id'];
                $subform[$language]['gallery']['layout']['#default_value'] = $node->gallery['layout'];
            }
        }

        unset($subform);
    }

    if (isset($form_ptr['events']) && isset($record->events)) {
        $form_ptr['events']['events']['#default_value'] = $record->events;
    }
} // }}}

/**
 * Wypełnia podany obiekt wartościami z podanej tablicy. Wartości odpowiadające
 * polom tworzonym automatycznie dla każdego formularza (op, submit,
 * form_build_id, form_token, form_id) zostaną zignorowane.
 *
 * @param object &$record
 * @param array $values
 *     zwykle wartości ze stanu formularza (form_state[values])
 * @return int
 *     liczba ustawionych wartości
 */
function scholar_populate_record(&$record, $values) // {{{
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
function scholar_generic_form($fields = array(), $record = null) // {{{
{
    // predefiniowane pola sekcji record formularza
    $record_fields = array(
        'first_name' => array(
            '#type'     => 'textfield',
            '#title'    => t('First name'),
        ),
        'last_name' => array(
            '#type'     => 'textfield',
            '#title'    => t('Last name'),
        ),
        'title' => array(
            '#type'      => 'textfield',
            '#title'     => t('Title'),
            '#maxlength' => 255,
        ),
        'bib_details' => array(
            '#type'      => 'textfield',
            '#title'     => t('Bibliographic details'), // Szczegóły bibliograficzne
            '#maxlength' => 255,
        ),
        'suppinfo' => array(
            '#type'      => 'scholar_element_langtext',
            '#title'     => t('Supplementary information'),
            '#maxlength' => 255,
        ),
        'start_date' => array(
            '#type'      => 'textfield',
            '#title'     => t('Start date'),
            '#maxlength' => 10, // YYYY-MM-DD
            '#size'      => 24,
            '#description' => t('Date format: YYYY-MM-DD.'),
            '#attributes' => array('class' => 'form-date'),
        ),
        'end_date' => array(
            '#type'      => 'textfield',
            '#title'     => t('End date'),
            '#maxlength' => 10,
            '#size'      => 24,
            '#description' => t('Date format: YYYY-MM-DD.'),
            '#attributes' => array('class' => 'form-date'),
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
        'category_id' => array(
            '#type'      => 'select',
            '#title'     => t('Category'),
            '#options'   => array(),
        ),
        'url' => array(
            '#type'      => 'textfield',
            '#title'     => t('URL'),
            '#maxlength' => 255,
            '#description' => t('Adres URL zewnętrznej strony ze szczegółowymi informacjami (musi zaczynać się od http:// lub https://).'),
            '#element_validate' => array('scholar_form_validate_url'),
        ),
        'parent_id' => array(
            '#type'     => 'select',
            '#title'    => t('Parent record'),
            '#options'  => array(),
        ),
        'image_id' => array(
            // jezeli modul gallery jest niedostepny, ten element nie zostanie
            // wyswietlony, ponadto jego wartosc nie wystapi wsrod wartosci
            // przeslanych w formularzu (dzieki temu np. stara wartosc image_id
            // w rekordzie nie zostanie nadpisana).
            '#type'     => 'gallery_image_select',
            '#title'    => t('Image'),
        ),
        'authors' => array(
            '#type'     => 'scholar_element_people',
            '#title'    => t('Authors'),
        ),
    );

    $vtable = array(
        '#type' => 'scholar_element_vtable',
        '#tree' => false,
    );

    $vtable['record'] = array(
        '#id'    => 'scholar-form-vtable-record',
        '#type'  => 'scholar_element_vtable_row',
        '#title' => t('Basic data'),
        '#description' => t('Enter the basic information'),
    );

    // tablica przechowujaca definicje formularza. Tutaj umieszczane sa
    // wartosci, ktorych klucze rozpoczynaja sie od #
    $form = array();

    // przechowuje informacje o polu submit, jezeli zostalo podane
    $submit = null;

    // jezeli 2 wtedy zostanie automatycznie dodany walidator dat
    // poczatku i konca
    $date_range = 0;

    foreach ($fields as $key => $value) {
        // jezeli podano nazwe formularza jako wartosc, z numerycznym
        // kluczem, uzyj tej nazwy do pobrania definicji pola
        if (is_int($key) && is_string($value)) {
            $key   = $value;
            $value = true;
        }

        // zrzutuj typ klucza do stringa, w przeciwnym razie podczas porownan
        // wartosci tekstowe w case bylyby konwertowane do liczb (do zera),
        // co mogloby skutkowac wejsciem do nie tej galezi co trzeba.
        $key = (string) $key;

        // wyodrebnij ustawienia formularza, nie sprawdzaj ich poprawnosci
        if (!strncmp('#', $key, 1)) {
            $form[$key] = $value;
            continue;
        }

        switch ($key) {
            case 'files':
                $vtable['files'] = array(
                    '#id'    => 'scholar-form-vtable-files',
                    '#type'  => 'scholar_element_vtable_row',
                    '#title' => t('File attachments'),
                    '#description' => t('Edit attached files'),
                );
                $vtable['files']['files'] = array(
                    '#type' => 'scholar_element_files',
                );
                break;

            case 'nodes':
                $vtable['nodes'] = array(
                    '#id'    => 'scholar-form-vtable-nodes',
                    '#type'  => 'scholar_element_vtable_row',
                    '#title' => t('Node'),
                    '#description' => t('Edit related pages'),
                );
                $vtable['nodes']['nodes'] = scholar_nodes_subform($record);
                break;

            case 'events':
                // dodaj formularz edycji wydarzen jedynie wtedy, gdy dostepny
                // jest modul events, aby nie modyfikowac istniejacych wartosci
                // eventow
                if (module_exists('events')) {
                    $vtable['events'] = array(
                        '#id'    => 'scholar-form-vtable-events',
                        '#type'  => 'scholar_element_vtable_row',
                        '#title' => t('Event'),
                        '#description' => t('Edit related events'),
                    );
                    $vtable['events']['events'] = array(
                        '#type'   => 'scholar_element_events',
                        '#fields' => $value,
                    );
                }
                break;

            case 'submit':
                if (is_array($value)) {
                    $submit = scholar_element_submit($value);
                } else {
                    $submit = scholar_element_submit();
                }
                break;

            default:
                if (isset($record_fields[$key])) {
                    // jezeli podano false zamiast specyfikacji elementu,
                    // nie dodawaj tego pola do formularza
                    if (false === $value) {
                        continue;
                    }

                    // jezeli podano string, zostanie on uzyty jako etykieta,
                    // wartosci typow innych niz string i array zostana 
                    // zignorowane podczas tworzenia pola
                    if (is_string($value)) {
                        $value = array('#title' => $value);
                    }

                    // ustawienia podane jawnie nadpisuja domyslne (czyli np.
                    // walidatory i atrybuty)
                    $vtable['record'][$key] = is_array($value) 
                        ? array_merge($record_fields[$key], $value)
                        : $record_fields[$key];

                    if ('start_date' == $key) {
                        $date_range = 1;
                    } elseif ('end_date' == $key) {
                        $date_range = 2;
                    }

                } elseif (is_array($value)) {
                    // niestandardowe pole formularza, dodaj je do sekcji record
                    $vtable['record'][$key] = $value;
                }
        }
    }

    // dodaj do formularza powiazany rekord, oraz pola w vtable
    $form['#record'] = $record;
    $form['vtable']  = $vtable;
    $form['submit']  = $submit;

    // jezeli w formularzu sa predefiniowane pola poczatku i konca daty
    // dodaj walidacje zakresu dat
    if (2 == $date_range) {
        $form['#validate'][] = 'scholar_generic_form_validate_date_range';
    }

    if ($record) {
        scholar_populate_form($form, $record);
    }

    return $form;
} // }}}

function scholar_generic_form_validate_date_range($form, &$form_state) // {{{
{
    $range = scholar_form_validate_date_range(
        $form['vtable']['record']['start_date'],
        $form['vtable']['record']['end_date']
    );

    // validate_date_range, jezeli podano poprawne daty, zwraca czasy poczatku
    // i konca przedzialu dat w formacie YYYY-MM-DD.
    if ($range) {
        $form_state['values']['start_date'] = $range[0];
        $form_state['values']['end_date']   = $range[1];
    }
} // }}}

function scholar_element_separator() // {{{
{
    return array('#type' => 'markup', '#value' => '<div class="scholar-separator"><hr/></div>');
} // }}}

/**
 * Otwiera tabelę i rozpoczyna pierwszą komórkę.
 *
 * @return array
 */
function scholar_form_tablerow_open($options = array()) // {{{
{
    $options += array('#prefix' => '');

    return array(
        '#type' => 'markup',
        '#value' => '<div class="form-item">' . $options['#prefix'] . '<table border="0" cellpadding="0" cellspacing="0" style="width:auto;border:none;margin:0"><tbody style="border:none"><tr style="border:none"><td style="border:none;padding:0">',
    );
} // }}}

/**
 * Zamyka otwartą komórkę tabeli i rozpoczyna nową.
 *
 * @return array
 */
function scholar_form_tablerow_next() // {{{
{
    return array(
        '#type' => 'markup',
        '#value' => '</td><td style="border:none;padding:0 0 0 0.75em">',
    );
} // }}}

/**
 * Zamyka otwartą komórkę tabeli i zamyka tabelę.
 *
 * @return array
 */
function scholar_form_tablerow_close($options = array()) // {{{
{
    $options += array('#suffix' => '');

    return array(
        '#type' => 'markup',
        '#value' => '</td></tr></tbody></table>' . $options['#suffix'] . '</div>',
    );
} // }}}


function scholar_form_required_error($element) // {{{
{
    form_error($element, t('!name field is required.', array('!name' => $element['#title'])));
} // }}}

function scholar_element_attributes($element) // {{{
{
    $attrs    = isset($element['#attributes']) ? (array) $element['#attributes'] : array();
    $multiple = isset($element['#multiple']) && $element['#multiple'];
    $parents  = isset($element['#parents']) ? (array) $element['#parents'] : array();

    // jezeli element nie ma podanego id lub name sprobuj wyznaczyc je
    // na podstawie zawartosci tablicy #parents (o ile zostala podana).
    if (isset($element['#id'])) {
        $id = $element['#id'];
    } else {
        $id = $parents ? implode('-', $element['#parents']) : '';
    }

    if (isset($element['#name'])) {
        $name = $element['#name'];
    } else if ($parents) {
        $name = array_shift($parents);
        if ($parents) {
            $name .= '[' . implode('][', $parents) . ']';
        }
    } else {
        $name = '';
    }

    $attrs['id'] = form_clean_id($id);
    $attrs['name'] = $name . ($multiple ? '[]' : '');

    if ($multiple) {
        $attrs['multiple'] = 'multiple';
    }

    $size = isset($element['#size']) ? max(0, $element['#size']) : 0;
    if ($size) {
        $attrs['size'] = $size;
    }

    $maxlength = isset($element['#maxlength']) ? max(0, $element['#maxlength']) : 0;
    if ($maxlength) {
        $attrs['maxlength'] = $maxlength;
    }

    return $attrs;
} // }}}

// drupalowy _form_set_class nie dość że jest funkcją wewnętrzną, to
// jeszcze nieodsluguje zagniezdzenia elementow. Tzn. jak ustawie error
// dla jakiegos poziomu, to wszystkie potomne tez powinny miec errora.
// Funkcja przeznaczona dla elementów formularza złożonych z innych pól.
function scholar_element_set_class(&$element, $class = array()) // {{{
{
    $class = (array) $class;

    if ($element['#required']) {
        $class[] = 'required';
    }

    // sprawdz, czy pole jest zaznaczone jako error
    if (scholar_element_get_error($element)) {
        $class[] = 'error';
    }

    if (isset($element['#attributes']['class'])) {
        $class[] = $element['#attributes']['class'];
    }

    $element['#attributes']['class'] = implode(' ', $class);
} // }}}

function scholar_element_get_error($element) // {{{
{
    if (isset($element['#parents'])) {
        $errors = form_set_error();
        $parents = array();

        foreach ((array) $element['#parents'] as $parent) {
            $parents[] = $parent;
            $key = implode('][', $parents);

            if (isset($errors[$key])) {
                return $errors[$key];
            }
        }
    }
} // }}}


// vim: fdm=marker
