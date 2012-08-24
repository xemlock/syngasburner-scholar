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

function _scholar_include($names) // {{{
{
    $__dir__ = dirname(__FILE__);

    foreach ((array) $names as $name) {
        $path = $__dir__ . '/' . ltrim($name, '/');

        if (is_file($path)) {
            require_once $path;

        } elseif (is_dir($path)) {
            if ($dh = @opendir($path)) {
                while ($entry = readdir($dh)) {
                    $filepath = $path . '/' . $entry;
                    if (is_file($filepath) && '.php' === substr($entry, -4)) {
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

function scholar_perm() {
  return array('administer scholar');
}


/**
 * Zwraca liczbę rekordów na stronę w listach rekordów.
 *
 * @return int
 */
function scholar_admin_page_size() // {{{
{
    return 25;
} // }}}

/**
 * Ustawia albo zwraca wartość sterującą renderingiem węzłów (segmentów).
 * Jeżeli nie podano żadnego argumentu zwrócona zostanie aktualna
 * wartość. Jeżeli podano nową, zostanie ona ustawiona, przy czym zwrócona
 * zostanie poprzednia wartość.
 *
 * @param bool $enabled OPTIONAL        true żeby włączyć renderowanie,
 *                                      false aby wyłączyć
 * @return bool
 */
function _scholar_rendering_enabled($enabled = null) // {{{
{
    static $_enabled = true;

    if (null !== $enabled) {
        $previous = $_enabled;
        $_enabled = (bool) $enabled;

        return $previous;
    }

    return $_enabled;
} // }}}

/**
 * Funkcja wywoływana po pomyślnym zapisie lub usunięciu rekordów
 * osób, kategorii i rekordów generycznych oraz przy usuwaniu / zmianie nazwy plików.
 * Zmiana lub usunięcie wydarzeń i węzłów nie wpływa na rendering. 
 */
function scholar_invalidate_rendering() // {{{
{
    variable_set('scholar_last_change', date('Y-m-d H:i:s'));
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
    // Jezeli wezel jest podpiety do rekordu modulu scholar przekieruj do
    // strony z formularzem edycji tegoz rekordu
    if ($info = scholar_node_owner_info($node->nid)) {
	switch ($info['table_name']) {
	    case 'people':
		return scholar_goto(scholar_admin_path('people/edit/' . $info['row_id']));

	    case 'generics':
		$record = scholar_load_generic($info['row_id'], null, scholar_admin_path());
	        return scholar_goto(scholar_admin_path($record->subtype . '/edit/' . $record->id));

	    case 'categories':
		$record = scholar_fetch_category($info['row_id'], false, false, scholar_admin_path());
		return scholar_goto(scholar_category_path($record->table_name, $record->subtype, 'edit/' . $record->id));
	}

    } else {
	drupal_set_message(t('Database corruption detected. No binding found for node (%nid)', array('%nid' => $node->nid)), 'error');
    }
} // }}}



function scholar_nodeapi($node, $op)
{
    if ($op == 'load' && $node->type == 'scholar' && _scholar_rendering_enabled()) {
        $info = scholar_node_owner_info($node->nid);
        if (empty($info)) {
            $node->body = '';
            return;
        }

        if (empty($info['last_rendered']) || $info['last_rendered'] < variable_get('scholar_last_change', 0)) {
            $func = 'render_' . $info['table_name'] . '_node';
            $body = '';

            if (function_exists($func)) {
                $body = $func($info['row_id'], $node);
            }

            // TODO najpierw sprawdz czy ZF jest dostepny
            _scholar_include('classes');

            $parser = new scholar_parser;
            $parser->addTag('chapter')
                   ->addTag('section');

            $renderer = new scholar_renderer(array('brInCode' => true));
            $renderer->addConverter('preface', new scholar_converter_preface)
                     ->addConverter('chapter', new scholar_converter_chapter)
                     ->addConverter('section', new scholar_converter_section)
                     ->addConverter('block',   new scholar_converter_block)
                     ->addConverter('box',     new scholar_converter_box)
                     ->addConverter('res',     new scholar_converter_res);

            $bbcode = $body . $info['body'];
            //            $bbcode = file_get_contents(dirname(__FILE__) . '/bbcode/kierownik_projektu.bbcode');
            $rendering = '';
            try {
                $tree = $parser->parse($bbcode);
                $rendering = $renderer->render($tree);
                $preface   = $renderer->getConverter('preface')->render();
                if ($preface) {
                    $rendering = $preface . $rendering;
                }

                // poniewaz w formacie Full HTML (2) znaki nowego wiersza sa automatycznie
                // przeksztalcane na znaczniki BR, zamien je na spacje. Znaki nowego wiersza
                // wewnatrz PRE sa zamienione na BR przez renderer.
                $rendering = str_replace(array("\r\n", "\n", "\r"), ' ', $rendering);

                // niestety dodaje tez paragrafy, ale to mozna obejsc ustawiajac odpowiednie marginesy.

            } catch (Exception $e) {}
//p($bbcode); exit;
            $node->body = '<div class="scholar-rendering">' . $rendering . '</div>';
            // $node->body = $markup;
            // $node->created = $node->changed = $timestamp;
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

function scholar_render_form() // {{{
{
    $args = func_get_args();
    $html = call_user_func_array('drupal_get_form', $args);
    return scholar_render($html);
} // }}}

function __scholar_init() // {{{
{
    _scholar_include(array('include', 'models'));
} // }}}

function scholar_init() // {{{
{
    __scholar_init();
} // }}}

function scholar_theme() // {{{
{
    // hook_theme jest wywolywany raz, podczas instalacji modulu. Wtedy
    // modul nie jest jeszcze aktywny, wiec hook_init nie jest wywolywany.
    // Wersja deweloperska wymaga do poprawnego dzialania uruchomienia
    // __scholar_init, ladujacego sa niezbedne pliki. Stad koniecznosc
    // jawnego wywolania tej funkcji.

    __scholar_init();

    return scholar_elements_theme();
} // }}}

// vim: fdm=marker
