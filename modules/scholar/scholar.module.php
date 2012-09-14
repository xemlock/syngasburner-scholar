<?php

function p($var, $label = null)
{
    static $last = 0;
    $colors = array('brown', 'red', 'orange', 'green', 'blue', 'navy', 'violet', 'magenta', 'purple');

    if ($label) {
        $label = '<strong>' . $label . '</strong>: ';
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

function _scholar_include($names, $rootdir = null) // {{{
{
    if (null === $rootdir) {
        $rootdir = dirname(__FILE__);
    }

    foreach ((array) $names as $name) {
        $path = $rootdir . '/' . ltrim($name, '/');

        if (is_file($path)) {
            require_once $path;

        } elseif (is_dir($path)) {
            if ($dh = @opendir($path)) {
                while ($entry = readdir($dh)) {
                    if ($entry{0} == '.') {
                        continue;
                    }

                    $filepath = $path . '/' . $entry;

                    if (is_dir($filepath)) {
                        _scholar_include($entry, $path);

                    } else if (is_file($filepath) && '.php' === substr($entry, -4)) {
                        require_once $filepath;
                    }
                }
                closedir($dh);
            }
        }
    }
} // }}}

function scholar_preprocess_page(&$vars)
{
}

function scholar_perm() { // {{{
  return array('administer scholar');
} // }}}




/**
 * Dodaje arkusz ze stylami tego modułu.
 */
function scholar_add_css() // {{{
{
    drupal_add_css(drupal_get_path('module', 'scholar') . '/css/scholar.admin.css', 'module', 'all');
} // }}}

/**
 * Dodaje kod JavaScript tego modułu.
 */
function scholar_add_js() // {{{
{
    drupal_add_js(drupal_get_path('module', 'scholar') . '/js/scholar.admin.js', 'module', 'header');
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
 * @param int $row_id
 * @param string $table_name
 * @param string $fragment
 */
function scholar_redirect_to_form($row_id, $table_name, $query = null, $fragment = null) // {{{
{
    switch ($table_name) {
        case 'people':
            $record = scholar_load_record('people', $row_id, scholar_path('people'));
            return scholar_goto(scholar_path('people', 'edit/%d', $record->id), $query, $fragment);

        case 'generics':
            $record = scholar_load_record('generics', $row_id, scholar_path());
            return scholar_goto(scholar_path("generics.{$record->subtype}", 'edit/%d', $record->id), $query, $fragment);

        case 'categories':
            $record = scholar_load_record('categories', $row_id, scholar_path());
            return scholar_goto(scholar_path("categories.{$record->table_name}.{$record->subtype}", 'edit/%d', $record->id), $query, $fragment);

        case 'pages':
            $record = scholar_load_record('pages', $row_id, scholar_path('pages'));
            return scholar_goto(scholar_path('pages', 'edit/%d', $record->id), $query, $fragment);
    }
} // }}}

/**
 * Wbrew pozorom to nie tyle jest formularz, co funkcja wywoływana
 * podczas edycji węzła. Formularz wywoływany automatycznie dla węzłów typu scholar.
 * Funkcja definiująca strukturę formularza dla powiązanych węzłów,
 * uruchamiana podczas standardowej edycji węzła o typie 'scholar'.
 * Dzięki tej funkcji nie trzeba wykrywać powiązanych węzłów
 * w hooku form_alter, albo w nodeapi.
 */
function scholar_node_form(&$form_state, $node) // {{{
{
    if (empty($node->nid)) {
        drupal_set_message(t('Node of the Scholar content type cannot be directly created.'), 'error');
        return scholar_goto('node/add');
    }

    // Jezeli wezel jest podpiety do rekordu modulu scholar przekieruj do
    // strony z formularzem edycji tegoz rekordu
    if ($binding = _scholar_fetch_node_binding($node->nid)) {
        // po edycji wroc to strony z podgladem wezla
        scholar_redirect_to_form($binding['row_id'], $binding['table_name'], 'destination=node/' . $node->nid, '!scholar-form-vtable-nodes');

    } else {
	    drupal_set_message(t('Database corruption detected. No binding found for node (%nid)', array('%nid' => $node->nid)), 'error');
    }
} // }}}

/**
 * Do formularzy o identyfikatorze rozpoczynającym się od scholar_
 * ustawia odpowiednie callbacki oraz atrybut class.
 *
 * @param array &$form
 * @param array &$form_state
 * @param string $form_id
 */
function scholar_form_alter(&$form, &$form_state, $form_id) // {{{
{
    if ('node_type_form' == $form_id && isset($form['#node_type']) && $form['#node_type']->type == 'scholar') {
        // nie mozna modyfikowac
        drupal_set_message(t('Modification of the Scholar content type is not allowed.'), 'error');
        scholar_goto('admin/content/types');
    }

    if (0 == strncmp($form_id, 'scholar_', 8)) {
        scholar_prepare_form(&$form);
    }
} // }}}

function scholar_eventapi(&$event, $op) // {{{
{
    switch ($op) {
        case 'prepare':
            if ($info = scholar_event_owner_info($event->id)) {
                return scholar_redirect_to_form($info['row_id'], $info['table_name'], 'destination=admin/content/events', '!scholar-form-vtable-events');
            }
            break;

        case 'load':
            if (_scholar_rendering_enabled()) {
                // TODO feature do zrealizowania w przyszlosci
            }
            break;
    }
} // }}}

function scholar_node_info() {
    return array(
        'scholar' => array(
            'name' => 'Scholar',
            'module' => 'scholar',
            'description' => t('Internal content type used by Scholar module. Do not create content of this type, as it will not work.'),
            'locked' => true,
        ),
    );
}


function scholar_nodeapi(&$node, $op)
{
    // dolacz pliki
    if ($op == 'view' && $node->type == 'scholar') {
        drupal_add_js(drupal_get_path('module', 'scholar') . '/js/scholar.js', 'module', 'header');
        drupal_add_css(drupal_get_path('module', 'scholar') . '/css/scholar.css', 'module', 'all');

        $binding = _scholar_fetch_node_binding($node->nid);
        if ($binding) {
            $files = scholar_load_files($binding['row_id'], $binding['table_name'], scholar_language());
            foreach ($files as $file) {
                $node->files[] = scholar_drupalize_file($file);
            }
        }
    }

    if ($op == 'load' && $node->type == 'scholar' && _scholar_rendering_enabled()) {
        $binding = _scholar_fetch_node_binding($node->nid);

        if (empty($binding)) {
            $node->body = '';
            return;
        }

        scholar_add_css();
        if (empty($binding['last_rendered']) || $binding['last_rendered'] < variable_get('scholar_last_change', 0)) {
            $func = 'scholar_render_' . $binding['table_name'] . '_node';
            $body = '';

            $view = new scholar_view('_scholar_render_escape');
            $view->setTemplateDir(dirname(__FILE__) . '/templates');

            if (function_exists($func)) {
                $body = $func($view, $binding['row_id'], $node);
            }
            global $language;
            $bbcode = '[__language="' . $language->language . '"]'
                    
                    . $body . $binding['body'];

            // $node->body = $bbcode; return;







            
            //            $bbcode = file_get_contents(dirname(__FILE__) . '/bbcode/kierownik_projektu.bbcode');
            $rendering = '';
            try {
                $tree = scholar_markup_parser()->parse($bbcode);
                $renderer = scholar_markup_renderer();
                $rendering = $renderer->render($tree);
                $preface   = scholar_markup_converter_preface();
                if ($preface) {
                    $rendering = $preface . $rendering;
                }

                // poniewaz w formacie Full HTML (2) znaki nowego wiersza sa automatycznie
                // przeksztalcane na znaczniki BR, zamien je na spacje. Znaki nowego wiersza
                // wewnatrz PRE sa zamienione na BR przez renderer.
                $rendering = str_replace(array("\r\n", "\n", "\r"), ' ', $rendering);
//echo($rendering); exit;
                // niestety dodaje tez paragrafy, ale to mozna obejsc ustawiajac odpowiednie marginesy.

            } catch (Exception $e) {
            p($e);
            }
//p($bbcode); exit;
            $node->body = '<div class="scholar-rendering">' . $rendering . '</div>';
            // $node->body = $markup;
            $node->created = $node->changed = time();
            // db_query("UPDATE {node} SET created = %d, changed = %d WHERE nid = %d", $node->created, $node->changed, $node->nid);
            // db_query("UPDATE {node_revisions} SET body = '%s', timestamp = %d WHERE nid = %d AND vid = %d", $markup, $timestamp, $node->nid, $node->vid);
            // db_query("UPDATE {scholar_nodes} SET last_rendered = %d WHERE node_id = %d", $timestamp, $node->nid);
        }
    }
}

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

function scholar_render_itempicker($callback) // {{{
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
} // }}}

function scholar_render_form() // {{{
{
    $args = func_get_args();
    $html = call_user_func_array('drupal_get_form', $args);
    return scholar_render($html);
} // }}}

function scholar_theme() // {{{
{
    // hook_theme jest wywolywany raz, podczas instalacji modulu. Wtedy
    // modul nie jest jeszcze aktywny, wiec hook_init nie jest wywolywany.
    // Wersja deweloperska wymaga do poprawnego dzialania uruchomienia
    // __scholar_init, ladujacego niezbedne pliki. Stad koniecznosc
    // jawnego wywolania tej funkcji.
    __scholar_init();

    $theme = array();

    $theme += scholar_elements_theme();

    $theme['scholar_textfield']   = array('arguments' => array('element' => null));
    $theme['scholar_select']      = array('arguments' => array('element' => null));
//  $theme['scholar_label']       = array('arguments' => array('title' => null, 'required' => null));
//  $theme['scholar_description'] = array('arguments' => array('description' => null));
//  $theme['scholar_dl']          = array('arguments' => array('data' => null, 'attributes' => null));

    return $theme;
} // }}}

function __scholar_init() // {{{
{
    // ladowanie powiazanych plikow dopiero gdy srodowisko jest w pelni
    // zainicjowane (zaleznosc od Zend Framework)
    _scholar_include(array('include', 'models'));
} // }}}

function scholar_init() // {{{
{
    __scholar_init();
} // }}}

function scholar_menu() // {{{
{
    __scholar_init();

    return _scholar_menu();
} // }}}

// vim: fdm=marker
