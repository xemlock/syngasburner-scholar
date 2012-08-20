<?php

/**
 * Deklaracja dodatkowych pól formularza.
 *
 * @return array
 */
function scholar_elements() // {{{
{
    $elements['scholar_textarea'] = array(
        '#input'            => true,
        '#description'      => t('Use BBCode markup, supported tags are listed <a href="#!">here</a>'),
    );
    $elements['scholar_country'] = array(
        '#input'            => true,
        '#options'          => scholar_countries(),
        '#default_value'    => 'PL',
    );
    $elements['scholar_date'] = array(
        '#input'            => true,
        '#maxlength'        => 10,
        '#yearonly'         => false,
    );

    $elements['scholar_element_cancel'] = array(
        '#input'            => false,
        '#title'            => t('Cancel'),
        '#value'            => '',
    );
    $elements['scholar_element_people'] = array(
        '#input'            => true,
        '#element_validate' => array('form_typ_scholar_element_people_validate'),
    );

    $elements['scholar_checkboxed_container'] = array(
        '#input'            => true,
        '#checkbox_name'    => 'status',
    );
    $elements['scholar_attachment_manager'] = array(
        '#input'            => true,
        '#element_validate' => array('form_type_scholar_attachment_manager_validate'),
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
    $theme['scholar_country'] = array(
        'arguments' => array('element' => null),
    );

    $theme['scholar_date'] = array(
        'arguments' => array('element' => null),
    );

    $theme['scholar_textarea'] = array(
        'arguments' => array('element' => null),
    );

    $theme['scholar_checkboxed_container'] = array(
        'arguments' => array('element' => null),
    );

    $theme['scholar_attachment_manager'] = array(
        'arguments' => array('element' => null),
    );

    $theme['scholar_element_cancel'] = array(
        'arguments' => array('element' => null),
    );

    $theme['scholar_element_people'] = array(
        'arguments' => array('element' => null),
    );

    return $theme;
} // }}}

/**
 * @return array
 */
function form_type_scholar_element_people_value($element, $post = false) // {{{
{
    $value = array();

    if (false === $post) {
        if ($element['#default_value']) {
            $post = $element['#default_value'];
        }
    }

    if ($post) {
        foreach ((array) $post as $data) {
            if (empty($data['id'])) {
                continue;
            }

            $value[] = array(
                'id'         => intval($data['id']),
                'first_name' => isset($data['first_name']) ? strval($data['first_name']) : '',
                'last_name'  => isset($data['last_name']) ? strval($data['last_name']) : '',
                'weight'     => isset($data['weight']) ? intval($data['weight']) : 0,
            );
        }
    }

    return $value;
} // }}}

function form_typ_scholar_element_people_validate()
{
    // TODO required ?
}



/**
 * @return string|null
 */
function form_type_scholar_textarea_value($element, $post = false) // {{{
{
    if (false === $post) {
        $value = $element['#default_value'];
    } else {
        $value = $post;
    }

    $value = trim(strval($value));

    return strlen($value) ? $value : null;
} // }}}


/**
 * @param array $element
 * @param array &$form_state
 */
function form_type_scholar_attachment_manager_validate($element, &$form_state) // {{{
{
    // jezeli podane sa pliki, to kazdy z nich musi miec niepusta etykiete
    // unikalną dla tego języka
    // jezeli dodano element musi byc podana
    foreach ($element['#value'] as $language => $files) {
        $labels = array();

        foreach ($files as $file) {
            $label = strtolower($file['label']);
            if (0 == strlen($file['label']) || isset($labels[$label])) {
                // Każdy załączony plik musi mieć nadaną etykietę
                form_error($element, t('Each attached file must be given a unique (case insensitive) label.'));
                break;
            }

            $labels[$label] = true;
        }
    }
} // }}}

/**
 * @param array $element
 * @param mixed $post
 */
function form_type_scholar_attachment_manager_value($element, $post = false) // {{{
{
    $value = array();

    if (false === $post) {
        // formularz nie zostal przeslany, uzyj domyslnej wartosci
        if ($element['#default_value']) {
            $post = $element['#default_value'];
        }
    }

    if ($post) {
        foreach (scholar_languages() as $language => $name) {
            if (!isset($post[$language])) {
                continue;
            }

            foreach ((array) $post[$language] as $data) {
                $id = intval($data['id']);

                // id jako klucz eliminuje ewentualne duplikaty plikow
                $value[$language][$id] = array(
                    'id'       => $id,
                    'label'    => isset($data['label']) ? trim(strval($data['label'])) : '',
                    'weight'   => isset($data['weight']) ? intval($data['weight']) : 0,
                    // nazwa i rozmiar pliku sa uzywane podczas renderowania
                    // tego elementu
                    'size'     => isset($data['size']) ? intval($data['size']) : 0,
                    'filename' => isset($data['filename']) ? strval($data['filename']) : '',
                );
            }
        }
    }

    return $value;
} // }}}

/**
 * Przekierowuje do theme_select.
 */
function theme_scholar_country($element) { // {{{
    return theme_select($element);
} // }}}

function theme_scholar_textarea($element) // {{{
{
    if (is_array($element['#description'])) {
        $element['#description'] = implode('', $element['#description']);
    }

    $textarea = theme_textarea($element);
    return $textarea;
} // }}}

/**
 * @return string
 */
function theme_scholar_element_people($element) // {{{
{
    $params = array(
        '#' . $element['#id'],
        $element['#name'],
        scholar_admin_path('people/itempicker'),
        $element['#value'],
    );
    $params = implode(',', array_map('drupal_to_js', $params));

    drupal_add_js('misc/tabledrag.js', 'core');
    drupal_add_js('misc/tableheader.js', 'core');
    drupal_add_js("\$(function(){Scholar.formElements.people($params)})", 'inline');

    return theme_form_element($element, '<div id="' . $element['#id'] .'"><noscript><div class="error">' . t('JavaScript is required.') . '</div></noscript></div>');
} // }}}

function theme_scholar_element_cancel($element) // {{{
{
    return l($element['#title'], $element['#value'], array('attributes' => array('class' => 'scholar-cancel')));
} // }}}

/**
 * @param array $element
 * @return string
 */
function theme_scholar_attachment_manager($element) // {{{
{
    drupal_add_js('misc/tabledrag.js', 'core');
    drupal_add_js('misc/tableheader.js', 'core');

    $langicons = module_exists('languageicons');

    $settings = array(
        'prefix'        => $element['#name'],
        'urlFileSelect' => url(scholar_admin_path('file/itempicker')),
        'urlFileUpload' => url(scholar_admin_path('file/upload'), array('query' => 'dialog=1')),
    );

    $html = '<p class="help">Each file must be given label in at least one language.
        If label is given, file will be listed on page in that language.</p>';

    $language = new stdClass;
    foreach (scholar_languages() as $code => $name) {
        $values = isset($element['#value'][$code]) ? array_values($element['#value'][$code]) : 0;

        // spraw zeby languageicons_icon myslal, ze dostaje obiekt jezyka
        $language->language = $code;
        $language->name     = $name;

        $legend = ($langicons ? theme('languageicons_icon', $language, $name) . ' ' : '') . $name;
        $id = $element['#id'] . '-' . $code;

        $settings['language'] = $language;
        $html .= '<fieldset class="scholar"><legend>' . $legend . '</legend>' .
        '<div id="' . htmlspecialchars($id) . '" class="form-item"></div>' .
        '<script type="text/javascript">new Scholar.attachmentManager(' . drupal_to_js('#' . $id) . ',' . drupal_to_js("{$element['#name']}[{$code}]") . ' ,' . drupal_to_js($settings) . ',' . drupal_to_js($values) . ')</script>'.
        '</fieldset>';
    }

    return $html;
} // }}}

/**
 * Funkcja renderująca kontener.
 *
 * @param array $element
 * @return string
 */
function theme_scholar_checkboxed_container($element) // {{{
{
    $checkbox_name = $element['#checkbox_name'];
    $checked = isset($element['#value'][$checkbox_name]) && $element['#value'][$checkbox_name];

    $parents = $element['#parents'];
    if ($parents) {
        $name = array_shift($parents) 
              . ($parents ? '[' . implode('][', $parents) . ']' : '')
              . '[' .$checkbox_name . ']';
    } else {
        $name = $checkbox_name;
    }

    $output = '<div style="border:1px solid black" id="' . $element['#id'] . '-wrapper">';
    $output .= '<label><input type="checkbox" name="' . $name .'" id="'.$element['#id'].'" value="1" onchange="$(\'#'.$element['#id'].'-wrapper .contents\')[this.checked ? \'show\' : \'hide\']()"' . ($checked ? ' checked="checked"' : ''). '/><input type="hidden" name="pi" value="3.14159" />' . $element['#title'] . '</label>';
    $output .= '<div class="contents">';
    $output .= $element['#children'];
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<script type="text/javascript">/*<![CDATA[*/$(function(){
        if (!$("#'.$element['#id'].'").is(":checked")) {
            $("#'.$element['#id'].'-wrapper .contents").hide();
        }
})/*]]>*/</script>';

    return $output;
} // }}}

/**
 * @param array $element
 * @param mixed $post
 *     podtablica z wartościami dla tego elementu
 * @return array
 *     wartość checkboksa kontrolujacego ten kontener
 */
function form_type_scholar_checkboxed_container_value($element, $post = false) // {{{
{
    $checkbox_name = $element['#checkbox_name'];

    if ($post) {
        $value = isset($post[$checkbox_name]) && $post[$checkbox_name];
    } else {
        $value = (bool) $element['#default_value'];
    }

    // musi zwrocic tablice, zeby dzieci kontenera mogly wpisac swoje wartosci
    return array(
        $checkbox_name => intval($value)
    );
} // }}}

function scholar_language_label($language, $label = null) // {{{
{
    static $have_languageicons = null;

    if (null === $have_languageicons) {
        $have_languageicons = module_exists('languageicons');
    }

    $name = scholar_languages($language);

    if ($have_languageicons) {
        $dummy = new stdClass;
        $dummy->language = $language;

        return theme('languageicons_icon', $dummy, $name) . ' ' . $label;
    }

    return '[' . $name . '] ' . $label;
} // }}}

/**
 * Tworzy tablicę definiującą formularz edycji wydarzenia.
 *
 * @param array $options
 *     opcjonalna tablica z dodatkową specyfikacją pól formularza, w której
 *     kluczami są nazwy predefiniowanych pól. 
 *     Podając jako wartość false pole o nazwie równej kluczowi nie zostanie
 *     dodane do wynikowego formularza. Jeżeli podano tablicę, zostanie ona
 *     scalona z predefiniowaną tablicą definiującą pole. Jeżeli podano wartość
 *     typu string zostanie ona ustawiona jako tytuł pola. Wartości innych
 *     typów nie będą miały wpływu na kształt formularza.
 * @return array
 *     tablica definiująca formularz edycji wydarzenia
 */
function scholar_events_form($options = array()) // {{{
{
    // predefiniowane pola formularza edycji eventow, podajac w tablicy
    // $fields wartosc false pole nie zostanie dodane do formularza
    $fields = array(
        'start_date' => array(
            '#type'        => 'textfield',
            '#title'       => t('Start date'),
            '#maxlength'   => 10,
            '#description' => t('Date format: YYYY-MM-DD.'),
        ),
        'end_date' => array(
            '#type'        => 'textfield',
            '#title'       => t('End date'),
            '#maxlength'   => 10,
            '#description' => t('Date format: YYYY-MM-DD. Leave empty if it is the same as the start date.'),
        ),
        'title' => array(
            '#type'        => 'textfield',
            '#title'       => t('Title'),
            '#maxlength'   => 255,
            '#description' => t('If not given title of referenced record will be used.'),
        ),
        'body' => array(
            '#type'        => 'scholar_textarea',
            '#title'       => t('Description'),
            '#description' => t('Detailed description about this event.'),
        ),
    );

    foreach ((array) $options as $key => $value) {
        if (!isset($fields[$key])) {
            continue;
        }

        // jezeli podano false jako wartosc pola, nie dodawaj tego pola
        if (false === $value) {
            $fields[$key] = false;

        } else if (is_array($value)) {
            $fields[$key] = array_merge($fields[$key], $value);

        } else if (is_string($value)) {
            $fields[$key]['#title'] = $value;
        }
    }

    $form = array(
        '#tree' => true,
    );

    // aby nie dodawac wybranego pola nalezy podac jego nazwe w kluczu, zas
    // jako wartosc podac false. Jezeli podano jako wartosc tablice, zostanie
    // ona scalona z predefiowana tablica opisujaca pole. Jezeli podano
    // wartosc typu string, zostanie ona ustawiona jako tytul pola. Wartosci
    // innych typow zostana zignorowane podczas dodawania pola.

    if (false !== $fields['start_date']) {
        $form['start_date'] = $fields['start_date'];
    }

    if (false !== $fields['end_date']) {
        $form['end_date'] = $fields['end_date'];
    }

    // dodaj kontener z polami na tytul lub tresc, jezeli pozwolono na dodanie
    // przynajmniej jednego z tych pol

    $add_title = false !== $fields['title'];
    $add_body  = false !== $fields['body'];

    if ($add_title || $add_body) {
        foreach (scholar_languages() as $code => $name) {
            $form[$code] = array(
                '#type'          => 'scholar_checkboxed_container',
                '#checkbox_name' => 'status',
                '#title'         => 'Add event in language: ' . scholar_language_label($code, $name),
                '#tree'          => true,
            );

            if ($add_title) {
                $form[$code]['title'] = $fields['title'];
            }

            if ($add_body) {
                $form[$code]['body'] = $fields['body'];
            }
        }
    }

    return $form;
} // }}}

/**
 * Pola formularza do tworzenia / edycji powiązanych węzłów.
 *
 * @param array $row
 * @param string $table_name
 * @return array
 */
function scholar_nodes_subform() // {{{
{
    $have_menu    = module_exists('menu');
    $have_path    = module_exists('path');
    $have_gallery = module_exists('gallery');

    if ($have_menu) {
        $menus = module_invoke('menu', 'get_menus');
        $parent_options = (array) module_invoke('menu', 'parent_options', $menus, null);
    }

    if ($have_gallery) {
        $gallery_options = (array) module_invoke('gallery', 'gallery_options');

        // gallery_options zawiera co najmniej jeden element, odpowiadajacy
        // pustej galerii. Jezeli tylko on jest dostepny, nie ma sensu dodawac
        // elementow zwiazanych z wyborem galerii.

        if (count($gallery_options) <= 1) {
            $gallery_options = null;
        }
    }

    $form = array(
        '#tree' => true,
    );

    foreach (scholar_languages() as $code => $name) {
        $container = array(
            '#type'     => 'scholar_checkboxed_container',
            '#title'    => t('Publish page in language: @lang', array('@lang' => $name)) . ' (<img src="' . base_path() . 'i/flags/' . $code . '.png" alt="" title="' . $name . '" style="display:inline" />)',
            '#checkbox_name' => 'status',
            '#default_value' => false,
            '#tree'     => true,
        );

        $container['title'] = array(
            '#type'     => 'textfield',
            '#title'    => t('Title'),
            '#description' => t('Page title, if not given it will default to this person\'s full name.'),
        );
        $container['body'] = array(
            '#type'     => 'scholar_textarea',
            '#title'    => t('Body'),
        );

        if ($have_menu) {
            $container['menu'] = array(
                '#type'     => 'fieldset',
                '#title'    => t('Menu settings'),
                '#collapsible' => true,
                '#collapsed' => true,
                '#tree'     => true,
            );
            $container['menu']['mlid'] = array(
                '#type'     => 'hidden',
            );
            $container['menu']['link_title'] = array(
                '#type'     => 'textfield',
                '#title'    => t('Menu link title'),
                '#description' => t('The link text corresponding to this item that should appear in the menu. Leave blank if you do not wish to add this post to the menu.'),
            );
            $container['menu']['parent'] = array(
                '#type'     => 'select',
                '#title'    => t('Parent item'),
                '#options'  => $parent_options,
                '#description' => t('The maximum depth for an item and all its children is fixed at 9. Some menu items may not be available as parents if selecting them would exceed this limit.'),
            );
            $container['menu']['weight'] = array(
                '#type'     => 'weight',
                '#title'    => t('Weight'),
                '#delta'    => 50,
                '#default_value' => 0,
                '#description' => t('Optional. In the menu, the heavier items will sink and the lighter items will be positioned nearer the top.'),
            );
        }

        if ($have_path) {
            $container['path'] = array(
                '#type'     => 'fieldset',
                '#title'    => t('URL path settings'),
                '#collapsible' => true,
                '#collapsed' => true,
            );
            $container['path']['path'] = array(
                '#type'     => 'textfield',
                '#title'    => t('URL path alias'),
                '#description' => t('Optionally specify an alternative URL by which this node can be accessed.'),
            );
        }

        if ($have_gallery && $gallery_options) {
            $container['gallery'] = array(
                '#type'         => 'fieldset',
                '#title'        => t('Gallery settings'),
                '#collapsible'  => true,
                '#collapsed'    => true,
            );
            $container['gallery']['gallery_id'] = array(
                '#type'         => 'select',
                '#title'        => t('Gallery'),
                '#description'  => t('Select a gallery attached to this node.'),
                '#options'      => $gallery_options,
            );
            $container['gallery']['gallery_layout'] = array(
                '#type'         => 'hidden',
                '#default_value' => 'horizontal', // poziomy uklad galerii
            );
        }

        $form[$code] = $container;
    }

    return $form;
} // }}}

/**
 * Funkcja definiująca strukturę formularza dla powiązanych węzłów,
 * uruchamiana podczas standardowej edycji węzła o typie 'scholar'.
 * Dzięki tej funkcji nie trzeba wykrywać powiązanych węzłów 
 * w hooku form_alter.
 * TODO do wywalenia chyba!!!
 */
function scholar_node_form(&$form_state, $node)
{
    // Jezeli wezel jest podpiety do obiektow modulu scholar
    // przekieruj do strony z edycja danego obiektu.
    p($node);
    if ($node->type == 'scholar') {
        $query = db_query("SELECT * FROM {scholar_nodes} WHERE node_id = %d", $node->nid);
        $row   = db_fetch_array($query);

        if ($row) {
            $referer = scholar_referer();
            if ($referer) {
                $destination = 'destination=' . urlencode($referer);
            } else {
                $destination = null;
            }

            switch ($row['table_name']) {
                case 'people':
                    scholar_goto('admin/scholar/people/edit/' . $row['object_id'], $destination);
                    break;
            }
        } else {
            drupal_set_message(t('No binding found for node (%nid)', array('%nid' => $node->nid)));
        }
    }
}

/**
 * Wypełnia pola formularza odpowiadające rekordowi. Pola bezpośrednio
 * należące do rekordu muszą znajdować się w kontenerze 'record'.
 * @param array &$form
 * @param object &$record
 */
function scholar_populate_form(&$form, &$record) // {{{
{
    if (isset($form['record'])) {
        $subform = &$form['record'];

        foreach ($record as $key => $value) {
            if (isset($subform[$key])) {
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
            if (isset($node->gallery_id)) {
                $subform[$language]['gallery']['gallery_id']['#default_value'] = $node->gallery_id;
            }
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
        'category_id' => array(
            '#type'      => 'select',
            '#title'     => t('Category'),
            '#options'   => array(),
        ),
        'url' => array(
            '#type'      => 'textfield',
            '#title'     => t('URL'),
            '#maxlength' => 255,
            '#description' => t('Adres URL strony ze szczegółowymi informacjami.'),
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

    $form['#record'] = $record;

    $form['record'] = array(
        '#type' => 'fieldset',
        '#title' => t('Basic data'),
    );

    foreach ($fields as $key => $value) {
        // jezeli podano nazwe formularza jako wartosc, z numerycznym
        // kluczem, uzyj tej nazwy do pobrania definicji pola
        if (is_int($key)) {
            $key = strval($value);
            $value = true;
        }

        switch ($key) {
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
                $form['nodes']['nodes'] = scholar_nodes_subform($value);
                break;

            case 'events':
                // dodaj formularz edycji wydarzen jedynie wtedy, gdy dostepny
                // jest modul events, aby nie modyfikowac istniejacych wartosci
                // eventow
                if (module_exists('events')) {
                    $form['events'] = array(
                        '#type' => 'fieldset',
                        '#title' => t('Event'),
                    );
                    $form['events']['events'] = scholar_events_form($value);
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

                    $form['record'][$key] = is_array($value) 
                        ? array_merge($record_fields[$key], $value)
                        : $record_fields[$key];

                } elseif (is_array($value)) {
                    // niestandardowe pole formularza, dodaj je do sekcji record
                    $form['record'][$key] = $value;
                }
        }
    }

    if ($record) {
        scholar_populate_form($form, $record);
    }

    return $form;
} // }}}

// vim: fdm=marker
