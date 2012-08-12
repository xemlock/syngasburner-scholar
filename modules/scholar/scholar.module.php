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
    $contents = str_replace('  ', '&nbsp;&nbsp;', $contents);

    echo '<div style="color:' . $colors[$last] . ';border:1px dotted #999;background:#eee;padding:10px;font-family:monospace;">', $label;
    echo $contents;
    echo '</div>';

    $last = ($last + 1) % count($colors);
}

function scholar_nodeapi($node, $op)
{
    if ($op == 'load' && $node->type = 'scholar') {
        // trzeba wyrenderowac tresc!!!
        $query = db_query("SELECT * FROM {scholar_nodes} WHERE node_id = %d", $node->nid);
        $binding = db_fetch_array($query);
      //  p($binding);

        if (empty($binding['last_rendered']) || $binding['last_rendered'] < variable_get('scholar_last_change', 0)) {
       //     p('RENDERING');
            // trzeba wygenerowac body
            switch ($binding['table_name']) {
                case 'people':
                    $timestamp = time();
                    $markup = $binding['body']
                            . "\n"
                            . "[PUBLIKACJE]\n"
                            . "[SZKOLENIA]\n";
                    db_query("UPDATE {node_revisions} SET body = '%s', timestamp = %d WHERE nid = %d AND vid = %d", $markup, $timestamp, $node->nid, $node->vid);
                    $node->body = $markup;
                    $node->created = $node->changed = $timestamp;
                    db_query("UPDATE {node} SET created = %d, changed = %d WHERE nid = %d", $node->created, $node->changed, $node->nid);

                    db_query("UPDATE {scholar_nodes} SET last_rendered = %d WHERE node_id = %d", $timestamp, $node->nid);
                    break;
            
            
            }
        }
       // else p('VALID');


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

    $items['scholar/conferences'] = array(
        'title'             => t('Conferences'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_generics_list',
        'page arguments'    => array('conference'),
        'parent'            => 'scholar',
        'file'              => 'scholar.generics.php',
    );
    $items['scholar/conferences/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => t('List'),
        'weight'            => -10,
    );
    $items['scholar/conferences/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Add'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_form', 'conference'),
        'parent'            => 'scholar/conferences',
        'file'              => 'scholar.generics.php',
    );
    $items['scholar/conferences/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit conference'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_form', 'conference', 3),
        'parent'            => 'scholar/conferences',
        'file'              => 'scholar.generics.php',
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
 * Dodaje do wartości znaki unikowe na użytek zapytań SQL oraz otacza
 * ją pojedynczymi apostrofami.
 *
 * @param mixed $value
 * @return string
 */
function scholar_db_quote($value) // {{{
{
    if (null === $value) {
        return 'NULL';
    } else if (is_int($value)) {
        return $value;
    } else if (is_float($value)) {
        // %F non-locale aware floating-point number
        return sprintf('%F', $value);
    }
    return "'" . db_escape_string($value) . "'";
} // }}}

/**
 * Jeżeli wartosć jest tablicą zostanie użyta klauzula WHERE IN.
 * @param array $conds          tablica z warunkami
 */
function scholar_db_where($conds) // {{{
{
    $where = array();

    foreach ($conds as $key => $value) {
        if (is_array($value)) {
            $values = count($value) 
                    ? '(' . join(',', array_map('scholar_db_quote', $value)) . ')'
                    : '(NULL)';
            $where[] = db_escape_table($key) . ' IN ' . $values;
        } else {
            $where[] = db_escape_table($key) . " = " . scholar_db_quote($value);
        }
    }

    return implode(' AND ', $where);
} // }}}

/**
 * Zwraca wyrażenie SQL, które przekształca kod kraju w jego nazwę
 * w bieżącym języku.
 * @param string $column        nazwa kolumny przechowującej dwuliterowy kod kraju
 * @param string $table         nazwa tabeli
 * @return string               wyrażenie CASE przekształcające kod kraju w jego nazwę
 */
function scholar_db_country_name($column, $table) // {{{
{
    $column = db_escape_table($column);
    $table  = db_escape_table($table);

    if (empty($column) || empty($table)) {
        return 'NULL';
    }

    // pobierz liste wystepujacych w tabeli krajow
    $query = db_query("SELECT DISTINCT $column FROM {$table} WHERE $column IS NOT NULL");
    $codes = array();

    while ($row = db_fetch_array($query)) {
        $codes[] = $row[$column];
    }

    // jezeli przeszukiwania w podanej kolumnie nie daly zadnych wynikow
    // nazwa kraju bedzie pusta
    if (empty($codes)) {
        return 'NULL';
    }

    $countries = scholar_countries();

    $sql = "CASE $column";
    foreach ($codes as $code) {
        $country = isset($countries[$code]) ? $countries[$code] : null;
        $sql .= " WHEN " . scholar_db_quote($code) . " THEN " . scholar_db_quote($country);
    }
    $sql .= " ELSE NULL END";

    return $sql;
} // }}}

// drupal_write_record dziala dobrze, jezeli zadna z wartosci obiektu nie
// jest nullem, co jest ok, gdy operujemy na tabelach gdzie wszystkie kolumny
// maja wlasciwosc NOT NULL. Dla tabeli z generykami to oczywiscie nie jest
// prawdziwe i funkcja drupalowa po prostu nie dziala.
function scholar_db_write_record($table, &$record, $update = array())
{
    return drupal_write_record($table, $record, $update);
}

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
    drupal_add_js(drupal_get_path('module', 'scholar') . '/js/scholar.js', 'module', 'header');
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

/**
 * Ustawia / zwraca czas ostatniej modyfikacji rekordów w tabelach
 * zarządzanych przez moduł.
 * @param int $time OPTIONAL
 * @return int
 */
function scholar_last_change($time = null) // {{{
{
    if (null === $time) {
        return variable_get('scholar_last_change', 0);
    }

    $time = intval($time);

    variable_set('scholar_last_change', $time);

    return $time;
} // }}}

function scholar_render($html, $dialog = false) // {{{
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
} // }}}

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

function scholar_theme() // {{{
{
    return scholar_elements_theme();
} // }}}

function scholar_block()
{
    
}


require_once dirname(__FILE__) . '/scholar.form.php';
require_once dirname(__FILE__) . '/scholar.file.php';
require_once dirname(__FILE__) . '/scholar.node.php';

// vim: fdm=marker
