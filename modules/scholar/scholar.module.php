<?php

function scholar_perm() {
  return array('administer scholar', 'manage Scholar contents');
}

function p($var, $label = null)
{
    static $last = 0;
    $colors = array('brown', 'red', 'orange', 'green', 'blue', 'navy', 'violet', 'magenta', 'purple');

    if ($label) {
        $label .= ': ';
    }

    ob_start();
    if (is_array($var) || is_object($var)) {
        print_r($var);
    } else {
        var_dump($var);
    }
    $contents = ob_get_clean();
    $contents = str_replace(array("\r\n", "\r", "\n"), "<br/>", $contents);

    echo '<div style="color:' . $colors[$last] . ';border:1px dotted #999;background:#eee;padding:10px;font-family:monospace;">', $label;
    echo $contents;
    echo '</div>';

    $last = ($last + 1) % count($colors);
}

function scholar_nodeapi($node, $op)
{
    if ($op == 'load') {
    }
}

function scholar_menu()
{
    $items = array();

    $items['scholar'] = array(
        'title'             => t('Scholar'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_index',
    );

    $items['scholar/people'] = array(
        'title'             => t('People'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_people_list',
        'parent'            => 'scholar',
        'file'              => 'scholar.people.php',
    );
    $items['scholar/people/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => t('List'),
        'weight'            => -10, // na poczatku listy
    );
    $items['scholar/people/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Add person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_form'),
        'parent'            => 'scholar/people',
        'file'              => 'scholar.people.php',
    );
    $items['scholar/people/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_form', 3),
        'parent'            => 'scholar/people',
        'file'              => 'scholar.people.php',
    );
    $items['scholar/people/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Delete person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_delete_form', 3),
        'parent'            => 'scholar/people',
        'file'              => 'scholar.people.php',
    );

    $items['scholar/files'] = array(
        'title'             => t('Files'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_file_list',
        'parent'            => 'scholar',
        'file'              => 'scholar.file.php',
    );
    $items['scholar/files/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => t('List'),
        'weight'            => -10,
    );
    $items['scholar/files/upload'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Upload'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_upload_form'),
        'parent'            => 'scholar/files',
        'file'              => 'scholar.file.php',
    );
    $items['scholar/files/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit file'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_edit_form', 3),
        'parent'            => 'scholar/files',
        'file'              => 'scholar.file.php',
    );
    $items['scholar/files/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit file'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_delete_form', 3),
        'parent'            => 'scholar/files',
        'file'              => 'scholar.file.php',
    );
    $items['scholar/files/select'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('File selection'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_file_select',
        'parent'            => 'scholar/files',
        'file'              => 'scholar.file.php',
    );

    return $items;
}

/**
 * 
 *
 * @param array $header         tablica koloumn tabeli w postaci opisanej
 *                              w theme_table()
 * @param string|array $before  jeżeli podano argument typu array, zostanie on
 *                              użyty zamiast parametru $columns, w przeciwnym
 *                              razie argument zostanie umieszczony w wynikowym
 *                              stringu bezpośrednio za klauzulą ORDER BY, przed
 *                              kodem opisującym sortowanie
 * @param array $columns        OPTIONAL tablica z dopuszczalnymi nazwami kolumn
 * @return string
 */
function scholar_tablesort_sql($header, $before = '', $columns = null) // {{{
{
    // jezeli $before jest tablica uzyj jej jako $columns
    if (is_array($before)) {
        $columns = $before;
        $before  = '';
    }

    // jezeli podano niepusta liste kolumn odfiltruj kolumny,
    // ktorych w niej nie ma
    if (is_array($columns)) {
        foreach ($header as $key => $column) {
            if (!isset($column['field']) || !in_array($column['field'], $columns)) {
                unset($header[$key]);
            }
        }
    }

    return tablesort_sql($header, $before, $columns);
} // }}}

/**
 * Dodaje arkusz ze stylami tego modułu.
 */
function scholar_add_css() // {{{
{
    drupal_add_css(drupal_get_path('module', 'scholar') . '/css/scholar.css', 'module', 'all');
} // }}}

/**
 * Dodaje kod JavaScript tego modułu.
 */
function scholar_add_js() // {{{
{
    drupal_add_js(drupal_get_path('module', 'scholar') . '/scholar.js', 'module', 'header');
} // }}}

/**
 * Transliteracja z UTF-8 do ASCII.
 *
 * @param string $string
 * @return string
 */
function scholar_ascii($string) // {{{
{
    // http://stackoverflow.com/questions/5048401/why-doesnt-translit-work#answer-5048939
    // The transliteration done by iconv is not consistent across
    // implementations. For instance, the glibc implementation transliterates
    // é into e, but libiconv transliterates it into 'e.

    $string = str_replace(
        array("æ",  "Æ",   "ß",  "þ",  "Þ", "–", "’", "‘", "“", "”", "„"),
        array("ae", "Ae", "ss", "th", "Th", "-", "'", "'", "\"", "\"", "\""), 
        $string
    );

    if (ICONV_IMPL === 'glibc') {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    } else {
        // na podstawie http://smoku.net/artykuly/zend-filter-ascii
        $string = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT//IGNORE', $string);
        $string = strtr($string,
            "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe"
          . "\x9c\x9a\xba\x9d\x9f\x9e\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6"
          . "\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3\xd4"
          . "\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2"
          . "\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf0"
          . "\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe",
            "ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYT"
          . "sraaaalccceeeeiiddnnooooruuuuyt");
    }

    return $string;
} // }}}

/**
 * Zwraca względną ścieżkę w obrębie bieżącej instalacji Drupala
 * na podstawie zawartości nagłówka HTTP Referer.
 *
 * @return null|string          null jeżeli ścieżka w nagłówku Referer nie
 *                              jest absolutna, lub jest zewnętrzna względem
 *                              instalacji Drupala
 */
function scholar_referer() // {{{
{
    // $base_url zawiera sciezke od korzenia dokumentow na serwerze do pliku
    // index.php instalacji Drupala zakonczona slashem
    $base_url = preg_replace('/index\.php$/', '', $_SERVER['PHP_SELF']);
    $referer  = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

    if (preg_match('/^https?:\/\//', $referer)) {
        // usun protokol, nazwe hosta z referera
        $referer = preg_replace('/^https?:\/\//', '', $referer);
        $referer = substr($referer, strpos($referer, '/'));

        // upewnij sie, ze sciezka znajduje sie wewnatrz instalacji drupala,
        // a nastepnie usun sciezke do index.php
        $n = strlen($base_url);
        if (!strncasecmp($referer, $base_url, $n)) {
            $referer = substr($referer, $n);

            if (strlen($referer)) {
                return $referer;
            }
        }
    }

    return null;
} // }}}

/**
 * Przekierowanie bez żadnej wyrafinowanej obsługi parametru destination.
 *
 * @param string $path
 * @param string $query
 */
function scholar_goto($path, $query = null) // {{{
{
    // drupal_goto jest fundamentalnie uposledzone ze wzgledu
    // na dzika obsluge destination
    $url = url($path, array('query' => $query, 'absolute' => true));
    $url = str_replace(array("\r", "\n"), '', $url);

    session_write_close();

    header('Status: 302 Found');
    header('Location: '. $url, true, 302);
    exit;
} // }}}


function scholar_index()
{
    p(scholar_referer());
    p($_SERVER['HTTP_REFERER']);
    p(scholar_languages());

    return '<pre>' . print_r(func_get_args(), 1) . '</pre>';
}

function scholar_render($html, $dialog = false)
{
    if ($dialog || (isset($_REQUEST['dialog']) && $_REQUEST['dialog'])) {
        init_theme();
        echo "<!DOCTYPE html>\n"
           . '<html><head>'
           . '<title>' . drupal_get_title() . '</title>'
           . '<link rel="shortcut icon" href="'. check_url(theme_get_setting('favicon')) .'" type="image/x-icon" />'
           . drupal_get_css()
           . drupal_get_js()
           . '</head><body class="scholar-dialog">'
           . theme('status_messages')
           . $html
           . '</body></html>';
        exit;
    }
    return $html;
}

/**
 * Wykorzystuje locale_language_list().
 */
function scholar_languages($language = null, $default = null) // {{{
{
    static $languages = null;

    if (null === $languages) {
        $languages = module_invoke('locale', 'language_list');
    }

    if (null === $language) {
        return $languages;
    }

    if (empty($language) || !isset($languages[$language])) {
        return t('All languages');
    }

    return $languages[$language];
} // }}}

/**
 * Do formularzy o identyfikatorze rozpoczynającym się od scholar_
 * ustawia odpowiednie callbacki oraz atrybut class.
 *
 * @param array &$form
 * @param array &$form_state
 * @param string $form_id
 */
function scholar_form_alter(&$form, &$form_state, $form_id)
{
    if (0 === strncmp($form_id, 'scholar_', 8)) {
        // callback #submit o nazwie takiej jak nazwa formularza
        // z przyrostkiem _submit jest automaycznie dodawany przez
        // drupal_prepare_form() wywolywana przez drupal_get_form().

        $form['#validate'] = isset($form['#validate']) ? (array) $form['#validate'] : array();
        $validate_callback = $form_id . '_validate';
        if (function_exists($validate_callback)) {
            $form['#submit'][] = $validate_callback;
        }

        $form['#attributes'] = isset($form['#attributes']) ? (array) $form['#attributes'] : array();
        if (!isset($form['#attributes']['class'])) {
            $form['#attributes']['class'] = 'scholar';
        } else {
            $form['#attributes']['class'] .= ' scholar';
        }

        return;
    }

    // Nie dopuszczaj do bezposredniej modyfikacji wezlow aktualizowanych
    // automatycznie przez modul scholar. Podobnie z wykorzystawymi eventami.
    // echo '<code>', $form_id, '</code>';
    if ('page_node_form' == $form_id  && $form['#node']) {
        $query = db_query("SELECT * FROM {scholar_nodes} WHERE node_id = %d", $form['#node']->nid);
        $row = db_fetch_array($query);
        if ($row) {
            switch ($row['table_name']) {
                case 'people':
                    $url = 'scholar/people/edit/' . $row['object_id'];
                    break;

                default:
                    $url = null;
                    break;
            }
            echo '<h1 style="color:red">Direct modification of scholar-referenced nodes is not allowed!</h1>';
            if ($url) {
                echo '<p>You can edit scholar object <a href="' . url($url) . '">here</a>.</p>';
            }
            // exit;
        }
    }
}

function scholar_render_form()
{
    $args = func_get_args();
    $html = call_user_func_array('drupal_get_form', $args);
    return scholar_render($html);
}

/**
 * Deklaracja dodatkowych pól formularza.
 *
 * @return array
 */
function scholar_elements() // {{{
{
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
 * @param array $element
 * @param array &$form_state
 */
function form_type_scholar_attachment_manager_validate($element, &$form_state)
{
    $count = count(scholar_languages());

    // jezeli dodano element przynajmniej jedna z etykiet musi byc podana
    // poniewaz podczas nadawania wartosci polu niepuste etykiety sa ustawiane
    // tylko dla dostepnych jezykow, wystarczy sprawdzic, czy liczba etykiet
    // jest rowna liczbie jezykow
    foreach ((array) $element['#value'] as $data) {
        if (count($data['labels']) < $count) {
            // Każdy załączony plik musi mieć nadaną przynajmniej jedną etykietę
            form_error($element, t('Each attached file must be given at least one label.'));
            break;
        }
    }
}

/**
 * @param array $element
 * @param mixed $post
 */
function form_type_scholar_attachment_manager_value($element, $post = false)
{
    $value = array();

    if (false === $post) {
        // formularz nie zostal przeslany, uzyj domyslnej wartosci
        // na podstawie dolaczonego do elementu obiektu
        if ($element['#files']) {
            
        }
    
    } else {
        $languages = scholar_languages();

        foreach ((array) $post as $data) {
            if (!isset($data['file_id'])) {
                continue;
            }

            $file_id = intval($data['file_id']);
            $labels  = array();

            foreach ($languages as $language => $name) {
                if (!isset($data['label'][$language])) {
                    continue;
                }

                $label = trim(strval($data['label'][$language]));
                if (strlen($label)) {
                    $labels[$language] = $label;
                }
            }

            // file_id jako klucz eliminuje ewentualne duplikaty plikow
            $value[$file_id] = array(
                'file_id' => $file_id,
                'labels'  => $labels,
            );
        }
    }

    return $value;
}

/**
 * @param array $element
 * @return string
 */
function theme_scholar_attachment_manager($element)
{
    scholar_add_css();
    drupal_add_js('misc/tabledrag.js', 'core');
    drupal_add_js('misc/tableheader.js', 'core');

    scholar_add_js();

    $langicons = module_exists('languageicons');
    $languages = array();
    foreach (scholar_languages() as $code => $name) {
        $languages[] = array(
            'code' => $code,
            'name' => $name,
            'flag' => $langicons ? theme('languageicons_icon', (object) array('language' => $code), $name) : null,
        );
    }

    $settings = array(
        'prefix'        => $element['#name'],
        'urlFileSelect' => url('scholar/files/select'),
        'urlFileUpload' => url('scholar/files/upload', array('query' => 'dialog=1')),
    );

    $html = '<p class="help">Each file must be given label in at least one language.
        If label is given, file will be listed on page in that language.</p>' .
        '<div id="' . $element['#id'] . '"></div>' .
        '<script type="text/javascript">new Scholar.attachmentManager(\'#' . $element['#id'] . '\', ' . drupal_to_js($settings) .', ' . drupal_to_js($languages) . ')</script>';
    

    return $html;
}

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

/**
 * Funkcja wymagana do renderowania dodatkowych elementów formularza.
 *
 * @return array
 */
function scholar_theme() // {{{
{
    $theme['scholar_checkboxed_container'] = array(
        'arguments' => array('element' => null),
    );

    $theme['scholar_attachment_manager'] = array(
        'arguments' => array('element' => null),
    );

    return $theme;
} // }}}

require_once dirname(__FILE__) . '/scholar.file.php';
require_once dirname(__FILE__) . '/scholar.node.php';
