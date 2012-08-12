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
    $elements['scholar_checkboxed_container'] = array(
        '#input'            => true,
        '#checkbox_name'    => 'status',
    );
    $elements['scholar_attachment_manager'] = array(
        '#input'            => true,
        '#files'            => array(),
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

    return $theme;
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
    scholar_add_css();
    drupal_add_js('misc/tabledrag.js', 'core');
    drupal_add_js('misc/tableheader.js', 'core');

    scholar_add_js();

    $langicons = module_exists('languageicons');

    $settings = array(
        'prefix'        => $element['#name'],
        'urlFileSelect' => url('scholar/files/select'),
        'urlFileUpload' => url('scholar/files/upload', array('query' => 'dialog=1')),
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

