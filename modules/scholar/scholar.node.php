<?php

/* 
 * Narzędzia do manipulacji węzłami
 * 
 * @author xemlock
 * @version 2012-07-31
 */

/**
 * Pobiera z bazy danych rekord wiążący węzeł z obiektem z podanej tabeli.
 *
 * @param int $object_id
 * @param string $table_name
 * @param string $language OPTIONAL     jeżeli nie podany zostaną pobrane
 *                                      wiązania dla wszystkich języków
 * @return false|array                  false jeżeli podano język, pusta
 *                                      tablica jeżeli go nie podano
 */
function _scholar_fetch_node_binding($object_id, $table_name, $language = null) // {{{
{
    $sql = sprintf(
        "SELECT * FROM {scholar_nodes} WHERE table_name = '%s' AND object_id = %d",
        db_escape_string($table_name), $object_id
    );

    if (null === $language) {
        $query  = db_query($sql);
        $result = array();

        while ($row = db_fetch_array($query)) {
            $result[] = $row;
        }

    } else {
        $sql .= sprintf(" AND language = '%s'", db_escape_string($language));
        $query  = db_query($sql);
        $result = db_fetch_array($query);
    }

    return $result;
} // }}}

/**
 * Tworzy powiązanie między rekordem z podanej tabeli a istniejącym węzłem.
 *
 * @param object &$node
 * @param int $object_id
 * @param string $table_name
 * @param string $body OPTIONAL
 */
function _scholar_bind_node(&$node, $object_id, $table_name, $body = '') // {{{
{
    if (empty($node->nid)) {
        return false;
    }

    // przygotuj identyfikator powiazanego linka w menu
    $mlid = isset($node->menu['mlid']) ? intval($node->menu['mlid']) : null;
    if (empty($mlid)) {
        $mlid = 'NULL';
    }

    // usuń obecne dowiązanie dla tego wezla i utwórz nowe
    db_query(
        "DELETE FROM {scholar_nodes} WHERE node_id = %d OR (table_name = '%s' AND object_id = %d AND language = '%s')", 
        $node->nid, $table_name, $object_id, $node->language
    );
    
    db_query(
        "INSERT INTO {scholar_nodes} (table_name, object_id, node_id, language, status, menu_link_id, path_id, last_rendered, body) VALUES ('%s', %d, %d, '%s', %d, %s, NULL, NULL, '%s')",
        $table_name, $object_id, $node->nid, $node->language, $node->status, $mlid, $body
    );

    // obejscie problemu z aliasami i wielojezykowoscia, poprzez wymuszenie
    // neutralnosci jezykowej aliasu, patrz http://drupal.org/node/347265
    // (URL aliases not working for content not in default language)
    $path = null;

    if (module_exists('path') && isset($node->path)) {
        // wyeliminuj duplikaty urla aby nie sprawialy problemu
        db_query("DELETE FROM {url_alias} WHERE dst = '%s'", $node->path);
        path_set_alias('node/'. $node->nid, $node->path, NULL, '');

        // path_set_alias nie zwraca identyfikatora utworzonej sciezki,
        // wiem musimy wyznaczyc go sami
        $query = db_query("SELECT * FROM {url_alias} WHERE dst = '%s'", $node->path);
        if ($path = db_fetch_array($query)) {
            db_query("UPDATE {scholar_nodes} SET path_id = %d WHERE node_id = %d", $path['pid'], $node->nid);
        }
    }

    return true;
} // }}}

/**
 * Ustawia wartości węzła.
 *
 * @param object $node
 * @param array $binding
 */
function _scholar_populate_node(&$node, $binding) // {{{
{
    $node->menu = null; // menu_link
    $node->path = null; // url_alias path dst
    $node->pid  = null; // url_alias path id
    $node->body = $binding['body']; // nieprzetworzona tresc wezla

    // Dociagamy menu link i url alias zgodne z danymi w binding
    if ($binding['menu_link_id']) {
        $query = db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $binding['menu_link_id']);

        if ($row = db_fetch_array($query)) {
            $node->menu = $row;
        }
    }

    if (db_table_exists('url_alias')) {
        $query = db_query("SELECT * FROM {url_alias} WHERE pid = %d", $binding['path_id']);

        if ($row = db_fetch_array($query)) {
            // pid jest potrzebne przy usuwaniu/edycji aliasow tak, by 
            // nie zostawiac osieroconych rekordow, patrz dokumentacja
            // path_nodeapi()
            $node->pid  = $row['pid'];
            $node->path = $row['dst'];
        }
    }
} // }}}

/**
 * Pobiera z bazy rekord węzła przypisany do rekordu z danej tabeli,
 * z nieprzetworzoną treścią, z wypełnionymi polami menu i path.
 *
 * @param int $object_id
 * @param string $table_name
 * @param string $language
 * @return false|object
 */
function scholar_fetch_node($object_id, $table_name, $language) // {{{
{
    static $_nodes = array();

    if (isset($_nodes[$table_name][$object_id])) {
        return $_nodes[$table_name][$object_id];
    }

    $result = false;

    if ($binding = _scholar_fetch_node_binding($object_id, $table_name, $language)) {
        // Reczne pobranie zamiast node_load() zeby nie uniknac wywolania
        // hooka nodeapi, poniewaz dostep do tego wezla ma byc jedynie 
        // dla modulu scholar.
        $query = db_query("SELECT * FROM {node} WHERE nid = %d", $binding['node_id']);

        if ($node = db_fetch_object($query)) {
            _scholar_populate_node($node, $binding);
            $result = $node;
        }
    }

    $_nodes[$table_name][$obejct_id] = $result;

    return $result;
} // }}}

/**
 * Tworzy pusty węzeł gotowy do zapisu za pomocą node_save().
 *
 * @param array $values OPTIONAL
 * @return object
 */
function scholar_create_node($values = array()) // {{{
{
    $node = new stdClass;

    $node->title    = '';
    $node->body     = '';
    $node->type     = 'scholar';
    $node->created  = time();
    $node->changed  = null;
    $node->promote  = 0; // Display on front page ? 1 : 0
    $node->sticky   = 0; // Display top of page ? 1 : 0
    $node->format   = 2; // 1:Filtered HTML, 2: Full HTML
    $node->status   = 1; // Published ? 1 : 0
    $node->language = '';
    $node->revision = null;

    $node->menu     = null;
    $node->path     = null;
    $node->pid      = null;

    return $node;
} // }}}

/**
 * Zapisuje węzeł i podpina go do obiektu z podanej tabeli.
 *
 * @param object &$node
 * @param int $object_id
 * @param string $table_name
 */
function scholar_save_node(&$node, $object_id, $table_name) // {{{
{
    $body = $node->body;

    $node->type = 'scholar';
    $node->body = ''; // puste body, bo tresc do przetworzenia zostanie
                      // zapisana w bindingu

    $node->revision = null; // nigdy nie tworz nowych rewizji, poniewaz
                            // tresc jest generowana automatycznie

    node_save($node);

    // dodaj węzeł do indeksu powiązanych węzłów
    _scholar_bind_node($node, $object_id, $table_name, $body);

    // przywroc body do wartosci sprzed zapisu
    $node->body = $body;
} // }}}

/**
 * Usuwa węzły powiązane z tym obiektem.
 *
 * @param int $object_id
 * @param string $table_name
 */
function scholar_delete_nodes($object_id, $table_name) // {{{
{
    $bindings  = _scholar_fetch_node_binding($object_id, $table_name);
    $url_alias = db_table_exists('url_alias');

    foreach ($bindings as $binding) {
        // Tutaj musimy uzyc nodeapi zeby poprawnie usunac rekord wezla,
        // usuniete zostana linki menu i aliasy sciezek.
        node_delete($binding['node_id']);

        // Dla absolutnej pewnosci usun powiazane linki menu i aliasy.
        // Jezeli dane sa rozspojnione, to oczywiscie zostanie usuniete 
        // wiecej niz trzeba. Spoko :)
        db_query("DELETE FROM {menu_links} WHERE mlid = %d", $binding['menu_link_id']);

        if ($url_alias) {
            db_query("DELETE FROM {url_alias} WHERE pid =%d", $binding['path_id']);
        }

        db_query("DELETE FROM {scholar_nodes} WHERE node_id = %d", $binding['node_id']);
    }
} // }}}

/**
 * Funkcja definiująca strukturę formularza dla powiązanych węzłów,
 * uruchamiana podczas standardowej edycji węzła o typie 'scholar'.
 * Dzięki tej funkcji nie trzeba wykrywać powiązanych węzłów 
 * w hooku form_alter.
 */
function scholar_node_form(&$form_state, $node)
{
    // Jezeli wezel jest podpiety do obiektow modulu scholar
    // przekieruj do strony z edycja danego obiektu.
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
                    scholar_goto('scholar/people/edit/' . $row['object_id'], $destination);
                    break;
            }
        } else {
            drupal_set_message(t('No binding found for node (%nid)', array('%nid' => $node->nid)));
        }
    }
}

/*function scholar_node_edit_form(&$form_state, $node_id)
{
    // Edycja ustawien wezla niedostepnych przy edycji obiektu scholara.
    // Tutaj musimy wykorzystac hook_nodeapi aby kazdy z modulow mogl
    // odpowiednio zmodyfikowac formularz.

    module_load_include('inc', 'node', 'node.pages');
    $node = node_load(intval($node_id));

    if (empty($node)) {
        drupal_set_message(t('Invalid node id (%nid)', array('%nid' => $node->nid)));
        return;
    }

    if ($node->type != 'scholar') {
        drupal_set_message(t('Invalid node type (%type)', array('%type' => $node->type)));
        return;
    }

    // spraw zeby moduly myslaly, ze modyfikuja standardowy formularz
    // edycji wezla-strony
    $form = array();
    $form['type'] = array(
        '#type'         => 'textfield',
        '#value'        => $node->type,
    );
    $form['#node'] = $node;

    taxonomy_form_alter(&$form, $form_state, 'scholar_node_form');
    gallery_form_alter(&$form, $form_state, 'scholar_node_form');
p($form);
    return $form;
}*/

/**
 * Generuje pola formularza do tworzenia / edycji powiązanych węzłów.
 *
 * @param array $row
 * @param string $table_name
 * @return array
 */
function scholar_nodes_subform($row = null, $table_name = null) // {{{
{
    $languages = scholar_languages();
    $default_lang = language_default('language');
    $form = array();

    foreach ($languages as $code => $name) {
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
            '#type'     => 'textarea',
            '#title'    => t('Body'),
            '#description' => t('Use BBCode markup, supperted tags are listed <a href="#!">here</a>'),
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

    // ustaw wartosci domyslne jezeli podano id obiektu oraz tabele,
    // do ktorej nalezy
    if ($row && $table_name) {
        foreach ($languages as $code => $name) {
            if ($node = scholar_fetch_node($row['id'], $table_name, $code)) {
                // ustaw wartosc checkboksa sterujacego kontenerem rowna
                // wartosci statusu wezla
                $form[$code]['#default_value'] = $node->status;

                $form[$code]['title']['#default_value'] = $node->title;
                $form[$code]['body']['#default_value']  = $node->body;

                if ($node->menu) {
                    foreach ($node->menu as $column => $value) {
                        if (isset($form[$code]['menu'][$column])) {
                            $form[$code]['menu'][$column]['#default_value'] = $value;
                        }
                    }

                    $form[$code]['menu']['parent']['#default_value'] = $node->menu['menu_name'] . ':' . $node->menu['plid'];
                }

                $form[$code]['path']['path']['#default_value'] = $node->path;
            }
        }
    }

    $files_id = 'storage_' . md5(microtime() . rand());
    $html = 
        '<script type="text/javascript">var ' . $files_id . '= new (function() {
            var items = {};
            var receiver;
            this.has = function(file_id) {
                return typeof items["_" + file_id] !== "undefined";
            }
            this.add = function(file_id) {
                items["_" + file_id] = true;
                if (receiver && typeof receiver.notifyAdd == "function") {
                    receiver.notifyAdd(file_id);
                }
            }
            this.receiver = function(r) {
                receiver = r;
            }
            this.del = function(file_id) {
                var key = "_" + file_id;
                if (key in items) {
                    delete items[key];
                    if (receiver && typeof receiver.notifyDelete == "function") {
                        receiver.notifyDelete(file_id);
                    }
                }
            }
            this.all = function() {
                return items;
            }
        })()</script>'
         . '<table><thead><tr><th>Filename</th><th>Category</th><th>Size</th>';
    foreach ($languages as $code => $name) {
        $html .= '<th><img src="' . base_path() . 'i/flags/' . $code . '.png" alt="" title="' . $name . '" /></th>';
    }
    $html .= '</tr></thead><tbody><tr>
        <td>Plik</td>
        <td><select></select></td>
        <td>22kB</td>
        <td><input type="checkbox" /></td>
        </tr></tbody></table><button type="button" onclick="window.open(\''.url('scholar/files/select').'#!'.$files_id.'\',\'file-select\', \'menubar=1,resizable=1,width=640,height=480,scrollbars=1\')">Wybierz plik</button><button type="button" onclick="window.open(\''.url('scholar/files/upload', array('query' => 'modal=1')).'#!'.$files_id.'\',\'file-upload\', \'menubar=1,resizable=1,width=640,height=480,scrollbars=1\')">Wgraj plik</button>';
    $form['attachments'] = array(
        '#type'         => 'fieldset',
        '#title'        => t('File attachments'),
        '#collapsed'    => true,
        '#collapsible'  => true,
    );
    $form['attachments']['files'] = array(
        '#type' => 'markup',
        '#value' => $html,
    );

    return $form;
} // }}}

