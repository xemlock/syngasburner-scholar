<?php

define('SCHOLAR_TEMPLATE_DIR', dirname(__FILE__) . '/templates');
define('SCHOLAR_VERSION',      '@SCHOLAR_VERSION');
define('SCHOLAR_REVISION',     '@SCHOLAR_REVISION');

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
        scholar_prepare_form($form);
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

/**
 * @param string $html
 * @return string
 */
function scholar_sanitize_html($html) // {{{
{
    preg_match_all('/<(?P<tag>\/?[-_a-z0-9]+)[\s>]/i', $html,
        $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    if ($matches) {
        $tag_stack = array();

        // znaczniki nie wymagajace zamkniecia, zgodnie z:
        // http://www.w3.org/TR/html4/index/elements.html
        $empty_tags = array(
            'area', 'base', 'basefont', 'br', 'col', 'frame',
            'hr', 'img', 'input', 'isindex', 'link', 'meta',
            'param',
        );

        foreach ($matches as $match) {
            $tag = strtolower($match['tag'][0]);
            $pos = $match['tag'][1];

            if (substr($tag, 0, 1) === '/') {
                $last_tag = end($tag_stack);

                if (empty($last_tag)) {
                    // zamkniecie znacznika wystapilo przed otwarciem, uznaj
                    // tresc za niepoprawna skladniowo, a przez to potencjalnie
                    // niebezpieczna
                    $teaser = '';
                    break;
                }

                if (substr($tag, 1) !== $last_tag['tag']) {
                    // niepoprawnie zamkniety tag, przytnij tresc do tego
                    // miejsca i przerwij przetwarzanie.
                    // Pozycja jest zmniejszona o 1, poniewaz $pos jest pozycja
                    // odpowiadajaca ukosnikowi w nazwie taga, a tresc musi
                    // zostac przycieta do nawiasu katowego otwierajacego tag.
                    // Do przycinania uzyta jest funkcja substr() a nie
                    // drupal_substr(), poniewaz matchowanie w preg_match_all()
                    // jest jednobajtowe (brak modyfikatora /u)
                    $html = substr($html, 0, $pos - 1);
                    break;
                }

                // zdejmij znacznik ze stosu
                array_pop($tag_stack);

            } else {
                // zignoruj otwarcie tagow bez tresci
                if (in_array(strtolower($tag), $empty_tags)) {
                    continue;
                }
                $tag_stack[] = compact('tag', 'pos');
            }
        }

        if ($tag_stack) {
            // niezamkniety znacznik, usun ostatni ze stosu i pozamykaj
            // pozostale. Nie zamykaj ostatniego, bo nie mamy pewnosci,
            // czy ten znacznik jest w ogole kompletny, tzn. zakonczony
            // prawym nawiasem katowym.
            $last_tag = array_pop($tag_stack);

            $html = substr($html, 0, $last_tag['pos'] - 1);
            while ($last_tag = array_pop($tag_stack)) {
                $html .= '</' . $last_tag['tag'] . '>';
            }
        }
    }

    return $html;
} // }}}

/**
 * Tworzy zajawkę na podstawie podanej treści. Z zajawki usunięte zostają
 * wszystkie tagi HTML za wyjątkiem A, SUB i SUP.
 *
 * @param string $body
 *     kod HTML do przetworzenia
 * @param int $length
 *     maksymalna długość zajawki, wartości mniejsze lub równe 0 znoszą
 *     ograniczenie długości. Jeżeli wynikiem przetwarzania będzie ciąg
 *     znaków o długości większej niż $length, zostanie on obcięty do
 *     $length - strlen($ellipsis) znaków, po czym zostanie do niego
 *     dopisany parametr $ellipsis.
 * @param string $ellipsis
 *     Opcjonalny przyrostek dopisywany do przyciętej treści zajawki.
 *     Domyślnie jest to wielokropek ('...').
 * @return string
 */
function scholar_node_teaser($body, $length = 0, $ellipsis = '...') // {{{
{
    // przygotuj zajawke, wstaw spacje przed i po elementach blokowych
    $teaser = preg_replace('/<(\/?)(address|blockquote|div|ul|ol|li|dl|dt|dd|h1|h2|h3|h4|h5|h6|p|table)/i', ' <\1\2', $body);

    // usun wszystkie tagi poza <a>, <sub> i <sup>
    $teaser = strip_tags($teaser, '<a><sub><sup>');

    // usun otaczajace biale znaki, zamien ciagi bialych znakow
    // na pojedyncze spacje
    $teaser = trim($teaser);
    $teaser = preg_replace('/\s+/', ' ', $teaser);

    // przytnij tekst do okreslonej liczby znakow (jezeli wieksza
    // od zera), jezeli tekst byl dluzszy zamien cztery ostatnie
    // znaki na wielokropek poprzedzony spacja (zeby dopasowac wynik
    // do zadanej dlugosci)
    $length = max(0, $length);

    if ($length && drupal_strlen($teaser) > $length) {
        if ($length <= strlen($ellipsis)) {
            $teaser = $ellipsis;
        } else {
            $teaser = drupal_substr($teaser, 0, $length - strlen($ellipsis));
            $teaser = scholar_sanitize_html($teaser);
            $teaser .= $ellipsis;
        }
    } else {
        $teaser = scholar_sanitize_html($teaser);
    }

    return $teaser;
} // }}}

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
        if (!scholar_setting_node_cache() || empty($binding['last_rendered']) || $binding['last_rendered'] < scholar_setting('last_change')) {
            $func = 'scholar_render_' . $binding['table_name'] . '_node';
            $body = '';

            $view = new scholar_view;
            $view->setTemplateDir(SCHOLAR_TEMPLATE_DIR);

            if (function_exists($func)) {
                $body = $func($view, $binding['row_id'], $node);
            }

            $bbcode = '[__language="' . $node->language . '"]' . $body . $binding['body'];

            $timestamp = time();

            $rendered_body = '<div class="scholar-node">' . scholar_render_markup($bbcode) . '</div>';
            $teaser = scholar_node_teaser($rendered_body, 512);

            db_query("UPDATE {node} SET changed = %d WHERE nid = %d", $timestamp, $node->nid);
            db_query("UPDATE {node_revisions} SET body = '%s', teaser = '%s', timestamp = %d WHERE nid = %d AND vid = %d", $rendered_body, $teaser, $timestamp, $node->nid, $node->vid);
            db_query("UPDATE {scholar_nodes} SET last_rendered = %d WHERE node_id = %d", $timestamp, $node->nid);

            $node->body = $rendered_body;
            $node->changed = $timestamp;
        }
    }
}

function scholar_eventapi(&$event, $op) // {{{
{
    switch ($op) {
        case 'prepare':
            if ($binding = scholar_event_owner_info($event->id)) {
                if (isset($_GET['destination'])) {
                    $query = 'destination=' . $_GET['destination'];
                } else {
                    $query = 'destination=admin/content/events';
                }
                return scholar_redirect_to_form($binding['row_id'], $binding['table_name'], $query, '!scholar-form-vtable-events');
            }
            break;

        case 'load':
            if (_scholar_rendering_enabled() && $binding = scholar_event_owner_info($event->id)) {
                $render = !scholar_setting_node_cache() || empty($binding['last_rendered']) || $binding['last_rendered'] < scholar_setting('last_change');
                if ($render) {
                    $body = scholar_render_markup('[__language="' . $binding['language'] . '"]' . $binding['body']);
                    db_query("UPDATE {events} SET body = '%s' WHERE id = %d", $body, $event->id);
                    db_query("UPDATE {scholar_events} SET last_rendered = %d", time());
                    $event->body = $body;
                }
            }
            break;
    }
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
        'filterSelector' => '#scholar-itempicker-filter-name',
        'filterReset'    => '#scholar-itempicker-filter-reset',
        'showOnInit'     => false,
    ));

    ob_start();
?><script type="text/javascript">$(function() {
new Scholar.ItemPicker('#scholar-itempicker-items', <?php echo drupal_to_js($items) ?>, <?php echo drupal_to_js($options) ?>);
setTimeout(function() { $('#scholar-itempicker-filter-name').focus(); }, 100);
});</script>
<div id="scholar-itempicker">
<div id="scholar-itempicker-filter"><input type="text" id="scholar-itempicker-filter-name" placeholder="<?php echo t('Search'); ?>"/><button type="button" id="scholar-itempicker-filter-reset"><?php echo t('Reset') ?></button>
</div>
<div id="scholar-itempicker-items"></div>
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
    // scholar_init, ladujacego niezbedne pliki. Stad koniecznosc
    // jawnego wywolania tej funkcji.
    scholar_init();

    $theme = array();

    $theme += scholar_elements_theme();

    $theme['scholar_checkbox']    = array('arguments' => array('element' => null));
    $theme['scholar_radios']      = array('arguments' => array('element' => null));
    $theme['scholar_select']      = array('arguments' => array('element' => null));
    $theme['scholar_textfield']   = array('arguments' => array('element' => null));

//  $theme['scholar_label']       = array('arguments' => array('title' => null, 'required' => null));
//  $theme['scholar_description'] = array('arguments' => array('description' => null));
//  $theme['scholar_dl']          = array('arguments' => array('data' => null, 'attributes' => null));

    return $theme;
} // }}}

function scholar_init() // {{{
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    // ladowanie powiazanych plikow dopiero gdy srodowisko jest w pelni
    // zainicjowane (wymagane przez zaleznosc od Zend Framework)
    _scholar_include(array('include', 'models'));

    $initialized = true;
} // }}}

function scholar_menu() // {{{
{
    scholar_init();

    return _scholar_menu();
} // }}}

// vim: fdm=marker
