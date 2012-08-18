<?php

/**
 * Flagi dla attachemnts_form.
 */
define('SCHOLAR_FILES',  0x01);
define('SCHOLAR_NODES',  0x02);
define('SCHOLAR_EVENTS', 0x04);

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
    $elements['scholar_element_people'] = array(
        '#input'            => true,
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
 * Zwraca listę wszystkich krajów.
 */
function scholar_countries($code = null) // {{{
{
    global $language;
    static $countries;

    if (null === $countries) {
        $key = 'scholar_countries_' . $language->language;

        if (!($data = cache_get($key))) {
            $locale = new Zend_Locale($language->language);
            $zflang = $locale->getLanguage();

            $countries = Zend_Locale::getTranslationList('Territory', $zflang, 2);
            // filter out unknown region (ZZ)
            unset($countries['ZZ']);

            switch ($zflang) {
                case 'pl':
                    // remove SAR part from China administered country names, as
                    // it is not obligatory, see: 
                    // http://en.wikipedia.org/wiki/Hong_Kong#cite_note-1
                    foreach ($countries as $key => $value) {
                        $countries[$key] = str_ireplace(', Specjalny Region Administracyjny Chin', '', $value);
                    }
                    break;
            }

            // this of course won't work on Windows, see:
            // https://bugs.php.net/bug.php?id=46165
            asort($countries, SORT_LOCALE_STRING);

            cache_set($key, $countries);
        } else {
            $countries = (array) $data->data;
        }
    }

    if (null === $code) {
        return $countries;
    }

    return isset($countries[$code]) ? $countries[$code] : null;
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

    $theme['scholar_element_people'] = array(
        'arguments' => array('element' => null),
    );

    return $theme;
} // }}}

function form_type_scholar_element_people_value($element, $post = false)
{
    $value = array();

    if (false === $post) {
        
    
    } else {

    }

    return $value;
}

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
 * @param mixed $post           Podtablica z wartościami dla tego elementu
 * @return array                Wartość checkboksa kontrolujacego ten kontener
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

function scholar_language_label($language, $name = null) // {{{
{
    static $have_languageicons = null;

    if (null === $have_languageicons) {
        $have_languageicons = module_exists('languageicons');
    }

    if (null === $name) {
        $name = scholar_languages($language);
    }

    if ($have_languageicons) {
        $dummy = new stdClass;
        $dummy->language = $language;

        return theme('languageicons_icon', $dummy, $name) . ' ' . $name;
    }
    
    return $name;
} // }}}

function scholar_events_form($date = true)
{
    $form = array(
        '#tree' => true,
    );

    if ($date) {
        $form['start_date'] = array(
            '#type' => 'textfield',
            '#title' => t('Start date'),
            '#maxlength' => 10,
            '#required' => true,
            '#description' => t('Date format: YYYY-MM-DD.'),
        );
        $form['end_date'] = array(
            '#type' => 'textfield',
            '#title' => t('End date'),
            '#maxlength' => 10,
            '#description' => t('Date format: YYYY-MM-DD. Leave empty if it is the same as the start date.'),
        );
    }

    foreach (scholar_languages() as $code => $name) {
        $form[$code] = array(
            '#type'          => 'scholar_checkboxed_container',
            '#checkbox_name' => 'status',
            '#title'         => 'Add event in language: ' . scholar_language_label($code, $name),
            '#tree'          => true,
        );

        $form[$code]['title'] = array(
            '#type'        => 'textfield',
            '#title'       => t('Title'),
            '#description' => t('If not given title of referenced record will be used.'),
        );
        
        $form[$code]['body'] = array(
            '#type'        => 'scholar_textarea',
            '#title'       => t('Description'),
            '#description' => t('Detailed description about this event'),
        );
    }

    return $form;
}

/**
 * Generuje pola formularza do tworzenia / edycji powiązanych węzłów.
 *
 * @param array $row
 * @param string $table_name
 * @return array
 */
function scholar_nodes_subform() // {{{
{
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
            '#options'  => menu_parent_options(menu_get_menus(), null),
            '#description' => t('The maximum depth for an item and all its children is fixed at 9. Some menu items may not be available as parents if selecting them would exceed this limit.'),
        );
        $container['menu']['weight'] = array(
            '#type'     => 'weight',
            '#title'    => t('Weight'),
            '#delta'    => 50,
            '#default_value' => 0,
            '#description' => t('Optional. In the menu, the heavier items will sink and the lighter items will be positioned nearer the top.'),
        );

        $container['path'] = array(
            '#type'     => 'fieldset',
            '#title'    => t('URL path settings'),
            '#collapsible' => true,
            '#collapsed' => true,
        );
        $container['path']['path'] = array(
            '#type'     => 'textfield',
            '#title'    => t('URL path alias'),
            '#description' => t('Optionally specify an alternative URL by which this node can be accessed. For example, type "about" when writing an about page. Use a relative path and don\'t add a trailing slash or the URL alias won\'t work.'),
        );

        $form[$code] = $container;
    }

    return $form;
} // }}}

function scholar_attachments_form($flags, &$record, $table_name)
{
    if ($record) {
        $row_id = is_object($record) ? $record->id : $record['id'];
    } else {
        $row_id = null;
    }

    $form = array(
        '#type' => 'fieldset',
        '#title' => 'attachments',
    );

    if ($flags & SCHOLAR_FILES) {
        $form['attachments'] = array(
            '#type' => 'fieldset',
            '#title' => t('File attachments'),
            //        '#collapsible' => true, // collapsible psuje ukrywanie kolumny z waga
            //        '#collapsed' => true,
        );
        $form['attachments']['files'] = array(
            '#type' => 'scholar_attachment_manager',
            '#default_value' => $row_id ? scholar_fetch_attachments($row_id, $table_name) : null
        );
    }

    if ($flags & SCHOLAR_EVENTS) {
        $form['event'] = scholar_events_form($flags);
    }

    if ($flags & SCHOLAR_NODES) {
        $form['node'] = scholar_nodes_subform($record, $table_name);
    }

    return $form;
}

// vim: fdm=marker
