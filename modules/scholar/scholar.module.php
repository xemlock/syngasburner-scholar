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
    if ($op == 'load' && $node->type == 'scholar') {
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

function scholar_preprocess_page(&$vars)
{
}

/**
 * Definicja menu wywoływana i zapamiętywana podczas instalacji modułu.
 * @return array
 */
function scholar_menu() // {{{
{
    $root = scholar_admin_path();

    $items = array();

    $items[$root] = array(
        'title'             => t('Scholar'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_index',
    );

    $items[$root . '/people'] = array(
        'title'             => t('People'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_people_list',
        'parent'            => $root,
        'file'              => 'pages/people.php',
    );
    $items[$root . '/people/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => t('List'),
        'weight'            => -10, // na poczatku listy
    );
    $items[$root . '/people/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Add person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_form'),
        'parent'            => $root . '/people',
        'file'              => 'pages/people.php',
    );
    $items[$root . '/people/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_form'),
        'parent'            => $root . '/people',
        'file'              => 'pages/people.php',
    );
    $items[$root . '/people/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Delete person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_delete_form'),
        'parent'            => $root . '/people',
        'file'              => 'pages/people.php',
    );
    $items[$root . '/people/itempicker'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Select people'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_itempicker',
        'page arguments'    => array('scholar_people_itempicker'),
        'parent'            => $root . '/people',
        'file'              => 'pages/people.php',
    );

    $items += _scholar_generic_menu(
        'conference', 
        t('Conferences'), 
        array('edit' => t('Edit conference'))
    );
    $items += _scholar_generic_menu(
        'presentation',
        t('Presentations'), 
        array('edit' => t('Edit presentation'))
    );
    $items += _scholar_generic_menu(
        'article',
        t('Articles'),
        array(
            'add'  => t('Add article'),
            'edit' => t('Edit article'),
        )
    );
    $items += _scholar_generic_menu(
        'book',
        t('Books'),
        array('edit' => t('Edit book'))
    );

    $items[$root . '/file'] = array(
        'title'             => t('Files'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_file_list',
        'parent'            => $root,
        'file'              => 'pages/file.php',
    );
    $items[$root . '/file/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => t('List'),
        'weight'            => -10,
    );
    $items[$root . '/file/upload'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Upload file'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_upload_form'),
        'parent'            => $root . '/file',
        'file'              => 'pages/file.php',
    );
    $items[$root . '/file/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit file'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_edit_form'),
        'parent'            => $root . '/file',
        'file'              => 'pages/file.php',
    );
    $items[$root . '/file/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Delete file'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_delete_form'),
        'parent'            => $root . '/file',
        'file'              => 'pages/file.php',
    );
    $items[$root . '/file/itempicker'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('File selection'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_itempicker',
        'page arguments'    => array('scholar_file_itempicker'),
        'parent'            => $root . '/file',
        'file'              => 'pages/file.php',
    );

    _scholar_menu_add_page_argument_positions($items);

    return $items;
} // }}}

function scholar_admin_path($path = '') // {{{
{
    if (strlen($path)) {
        $path = '/' . ltrim($path);
    }
    return 'admin/scholar' . $path;
} // }}}

/**
 * Zwraca liczbę rekordów na stronę w listach rekordów.
 *
 * @return int
 */
function scholar_admin_page_size()
{
    return 25;
}

/**
 * @param array &$items
 */
function _scholar_menu_add_page_argument_positions(&$items) // {{{
{
    foreach ($items as $path => &$item) {
        if (false === strpos($path, '%')) {
            continue;
        }

        if (!isset($item['page arguments'])) {
            $item['page arguments'] = array();
        }

        $parts = explode('/', $path);
        for ($i = 0, $n = count($parts); $i < $n; ++$i) {
            if (strpos($parts[$i], '%') !== false) {
                $item['page arguments'][] = $i;
            }
        }
    }
    unset($item);
} // }}}

function _scholar_generic_menu($subtype, $title, $titles = array()) // {{{
{
    $category_titles = array(
        'list'   => t('Categories'),
        'add'    => t('Add category'),
        'edit'   => t('Edit category'),
        'delete' => t('Delete category'),
    );

    if (isset($titles['categories'])) {
        $category_titles = array_merge($category_titles, (array) $titles['categories']);
    }

    $titles = array_merge(array(
        'list'       => t('List'),
        'add'        => t('Add'),
        'edit'       => t('Edit'),
        'delete'     => t('Delete'),
    ), $titles);

    $titles['categories'] = $category_titles;

    $root_path = scholar_admin_path($subtype);

    $items[$root_path] = array(
        'title'             => $title,
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_generics_list',
        'page arguments'    => array($subtype),
        'parent'            => dirname($root_path),
        'file'              => 'pages/generic.php',
    );
    $items[$root_path . '/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => $titles['list'],
        'weight'            => -10,
    );
    $items[$root_path . '/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => $titles['add'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_form', $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/generic.php',
        'weight'            => 0,
    );
    $items[$root_path . '/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => $titles['edit'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_form', $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/generic.php',
    );
    $items[$root_path . '/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => $titles['delete'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_delete_form', $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/generic.php',
    );

    $items[$root_path . '/category/list'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => $titles['categories']['list'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_category_list',
        'page arguments'    => array('generics', $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/category.php',
        'weight'            => 5,
    );
    $items[$root_path . '/category/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => $titles['categories']['add'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_category_form', 'generics', $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/category.php',
        'weight'            => 10,
    );
    $items[$root_path . '/category/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => $titles['categories']['edit'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_category_form', 'generics', $subtype),
        'file'              => 'pages/category.php',
    );
    $items[$root_path . '/category/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => $titles['categories']['delete'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_category_delete_form'),
        'file'              => 'pages/category.php',
    );

    return $items;
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
    scholar_add_js();
    scholar_add_css();

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

function scholar_render_itempicker($callback)
{
    $items = $callback($options);

    $options = array_merge((array) $options, array(
        'filterSelector' => '#name-filter',
        'filterReset'    => '#reset-filter',
        'showOnInit'     => false,
    ));

    ob_start();
?><script type="text/javascript">$(function() {
  new Scholar.ItemPicker('#items', <?php echo drupal_to_js($items) ?>, <?php echo drupal_to_js($options) ?>);
});</script>
Filtruj: <input type="text" id="name-filter" placeholder="<?php echo 'Search file'; ?>"/><button type="button" id="reset-filter">Wyczyść</button>
Dwukrotne kliknięcie zaznacza element
<hr/>
<div id="items"></div>
<?php
    return scholar_render(ob_get_clean(), true);
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

    return isset($languages[$language]) ? $languages[$language] : '';
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
                    $url = 'admin/scholar/people/edit/' . $row['object_id'];
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

function __scholar_init()
{
    $dir = dirname(__FILE__);
    $sub = array('library', 'models');

    foreach ($sub as $subdir) {
        if ($dh = @opendir($dir . '/' . $subdir)) {
            while ($entry = readdir($dh)) {
                if ('.php' === substr($entry, -4)) {
                    require_once $dir . '/' . $subdir . '/' . $entry;
                }
            }
        }
    }
}

__scholar_init();

// vim: fdm=marker
