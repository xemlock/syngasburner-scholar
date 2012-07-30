<?php

/* 
 * Narzędzia do manipulacji węzłami
 * 
 * @author xemlock
 * @version 2012-07-27
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
 * @param string $language
 */
function scholar_bind_node(&$node, $object_id, $table_name, $language) // {{{
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
        $node->nid, $table_name, $object_id, $language
    );
    
    db_query(
        "INSERT INTO {scholar_nodes} (table_name, object_id, node_id, language, menu_link_id, path_id, status) VALUES ('%s', %d, %d, '%s', %s, NULL, %d)",
        $table_name, $object_id, $node->nid, $language, $mlid, $node->status
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
 * Pobiera z bazy rekord węzła przypisany do rekordu z danej tabeli,
 * bez treści, z wypełnionymi polami menu i path.
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

        if ($row = db_fetch_array($query)) {
            $node = (object) $row;
            $node->menu = null; // menu link
            $node->path = null; // url alias path dst
            $node->pid  = null; // url alias path id

            // Dociagamy menu link i url alias zgodne z danymi w binding
            if ($binding['menu_link_id']) {
                $query = db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $binding['menu_link_id']);

                if ($row = db_fetch_array($query)) {
                    $node->menu = $row;
                }
            }

            if (db_table_exists('path')) {
                $query = db_query("SELECT * FROM {url_alias} WHERE pid = %d", $binding['path_id']);

                if ($row = db_fetch_array($query)) {
                    // pid jest potrzebne przy usuwaniu/edycji aliasow tak, by 
                    // nie zostawiac osieroconych rekordow, patrz dokumentacja
                    // path_nodeapi()
                    $node->pid  = $row['pid'];
                    $node->path = $row['dst'];
                }
            }

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

    $node->name     = 'Title';
    $node->title    = $node->name;
    $node->body     = '';
    $node->type     = 'page';
    $node->created  = time();
    $node->changed  = $node->created;
    $node->promote  = 0; // Display on front page ? 1 : 0
    $node->sticky   = 0; // Display top of page ? 1 : 0
    $node->format   = 2; // 1:Filtered HTML, 2: Full HTML
    $node->status   = 1; // Published ? 1 : 0
    $node->language = '';

    $node->menu     = null;
    $node->path     = null;
    $node->pid      = null;

    $taxonomy = array();
    if (isset($values['taxonomy'])) {
        $tags = explode(',', $values['taxonomy']);
        foreach ($tags as $value) {
            $taxonomy[$value] = taxonomy_get_term($value);
        }
    }
    $node->taxonomy = $taxonomy;

    return $node;
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
 * Generuje pola formularza do tworzenia/edycji powiązanych węzłów.
 *
 * @param array $row
 * @param string $table_name
 * @return array
 */
function scholar_nodes_subform($row = null, $table_name = null)
{
    $languages = Langs::languages();
    $default_lang = Langs::default_lang();
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
            '#attributes' => array(
                'class' => 'scholar-people-form-menu-settings',
            ),
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
            '#type'     => 'textfield',
            '#title'    => t('URL path alias'),
            '#description' => t('Optionally specify an alternative URL by which this node can be accessed. For example, type "about" when writing an about page. Use a relative path and don\'t add a trailing slash or the URL alias won\'t work.'),
        );

        $form[$code] = $container;
    }

    // ustaw wartosci domyslne jezeli podano id obiektu oraz tabele do ktorej nalezy
    if ($row && $table_name) {
        foreach ($languages as $code => $name) {
            if ($node = scholar_fetch_node($row['id'], $table_name, $code)) {
                // ustaw status jako wartosc checkboksa w kontenerze dla tego jezyka
                $form[$code]['#default_value'] = $node->status;
                $form[$code]['title']['#default_value'] = $node->title; // tytul jest przechowywany w wezle
                $form[$code]['body']['#default_value']  = 'NOT IMPLEMENTED YET!'; // tresc nie, bo tu jest zapisywany rendering

                if ($node->menu) {
                    foreach ($node->menu as $column => $value) {
                        if (isset($form[$code]['menu'][$column])) {
                            $form[$code]['menu'][$column]['#default_value'] = $value;
                        }
                    }
                    $form[$code]['menu']['parent']['#default_value'] = $node->menu['menu_name'] . ':' . $node->menu['plid'];
                }

                $form[$code]['path']['#default_value'] = $node->path;
            }
        }
    }

    return $form;
}
