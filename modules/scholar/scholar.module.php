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

    echo '<pre style="color:' . $colors[$last] . ';border:1px dotted #999;background:#eee;padding:10px">', $label;
    if (is_array($var) || is_object($var)) {
        print_r($var);
    } else {
        var_dump($var);
    }
    echo '</pre>';

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
 * Dodaje arkusz ze stylami tego modułu.
 */
function scholar_add_css() // {{{
{
    drupal_add_css(drupal_get_path('module', 'scholar') . '/scholar.css', 'module', 'all');
} // }}}

/**
 * Transliteracja z UTF-8 do ASCII
 *
 * @param string $string
 * @return string
 */
function scholar_ascii($string) // {{{
{
    // http://stackoverflow.com/questions/5048401/why-doesnt-translit-work#answer-5048939
    // The transliteration done by iconv is not consistent across implementations.
    // For instance, the glibc implementation transliterates é into e, but libiconv 
    // transliterates it into 'e.

    $string = str_replace(
        array("æ",  "Æ",   "ß",  "þ",  "Þ", "–", "’", "‘", "“", "”", "„"),
        array("ae", "Ae", "ss", "th", "Th", "-", "'", "'", "\"", "\"", "\""), 
        $string
    );

    if (ICONV_IMPL === 'glibc') {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    } else {
        // na podstawie http://smoku.net/artykuly/zend-filter-ascii
        $string = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT', $string);
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

function scholar_index()
{
    return '<pre>' . print_r(func_get_args(), 1) . '</pre>';
}

function scholar_render($html, $modal = false)
{
    if ($modal || isset($_REQUEST['modal'])) {
        echo '<html><head><title>Scholar modal</title></head><body>' . $html . '</body></html>';
        exit;
    }
    return $html;
}

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

    // Nie dopuszczaj do bezposredniej modyfikacji wezlow
    // aktualizowanych automatycznie przez modul scholar.
    // Podobnie z wykorzystawymi eventami.
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
 * @return null|string
 */
function scholar_referer() // {{{
{
    // sciezka od korzenia dokumentow do pliku index.php zakonczona slashem
    $base_url = preg_replace('/index\.php$/', '', $_SERVER['PHP_SELF']);
    $referer  = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

    if (preg_match('/^https?:\/\//', $referer)) {
        // usun protokol, nazwe hosta z referera, oraz sciezke do index.php
        $referer  = preg_replace('/^https?:\/\//', '', $referer);
        $referer  = substr($referer, strpos($referer, '/') + strlen($base_url));

        // teraz referer przechowuje wzgledna sciezke wewnatrz instalacji Drupala
        if (strlen($referer)) {
            return $referer;
        }
    }

    return null;
} // }}}

/**
 * Przekierowanie bez żadnej wyrafinowanej obsługi zmiennej destination.
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

/**
 * Deklaracja dodatkowych pól formularza.
 *
 * @return array
 */
function scholar_elements() // {{{
{
    $elements['scholar_checkboxed_container'] = array(
        '#input' => true,
        '#checkbox_name' => 'status',
    );
    $elements['scholar_file_upload'] = array(
        '#input' => true,
    );

    return $elements;
} // }}}

function form_type_scholar_file_upload_value($element, $post = false)
{



}

function theme_scholar_file_upload($element)
{
    
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

    $theme['scholar_file_uplooad'] = array(
        'arguments' => array('element' => null),
    );

    return $theme;
} // }}}

require_once dirname(__FILE__) . '/scholar.file.php';
require_once dirname(__FILE__) . '/scholar.node.php';
