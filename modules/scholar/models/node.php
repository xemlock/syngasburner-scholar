<?php

/* 
 * Narzędzia do manipulacji węzłami
 * 
 * @author xemlock
 * @version 2012-08-19
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
    $node = false;

    if ($binding = _scholar_fetch_node_binding($object_id, $table_name, $language)) {
        // Reczne pobranie zamiast node_load() zeby nie uniknac wywolania
        // hooka nodeapi, poniewaz dostep do tego wezla ma byc jedynie 
        // dla modulu scholar.
        $query = db_query("SELECT * FROM {node} WHERE nid = %d", $binding['node_id']);

        if ($node = db_fetch_object($query)) {
            _scholar_populate_node($node, $binding);
        }
    }

    return $node;
} // }}}

/**
 * Zwraca wszystkie segmenty powiązane z tym rekordem, indeksowane 
 * kodem języka.
 * @return array
 */
function scholar_fetch_nodes($object_id, $table_name) // {{{
{
    $nodes = array();

    foreach (_scholar_fetch_node_binding($object_id, $table_name) as $binding) {
        $query = db_query("SELECT * FROM {node} WHERE nid = %d", $binding['node_id']);

        if ($node = db_fetch_object($query)) {
            _scholar_populate_node($node, $binding);
            $nodes[$node->language] = $node;
        }
    }

    return $nodes;
} // }}}

/**
 * @param int $row_id
 * @param string $table_name
 * @param array $nodes
 */
function scholar_save_nodes($row_id, $table_name, $nodes) // {{{
{
    foreach ($nodes as $language => $node_data) {
        // sprobuj pobrac wezel powiazany z tym obiektem
        $node = scholar_fetch_node($row_id, $table_name, $language);
        $status = intval($node_data['status']) ? 1 : 0;

        if (empty($node)) {
            // jezeli status jest zerowy, a wezel nie istnieje nie tworz nowego
            if (!$status) {
                continue;
            }

            // status niezerowy, utworz nowy wezel
            $node = scholar_create_node();
        }

        $node->status   = $status;
        $node->language = $language;
        $node->title    = $node_data['title'];
        $node->body     = $node_data['body'];

        // wyznacz parenta z selecta, na podstawie modules/menu/menu.module:429
        $menu = $node_data['menu'];
        list($menu['menu_name'], $menu['plid']) = explode(':', $node_data['menu']['parent']);

        // menu jest zapisywane za pomoca hookow: menu_nodeapi, path_nodeapi
        $node->menu = $menu;
        $node->path = rtrim($node_data['path']['path'], '/');

        scholar_save_node($node, $row_id, $table_name);
    }
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
    $body = trim($node->body);

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
        // Dla absolutnej pewnosci usun powiazane linki menu i aliasy.
        // Jezeli dane sa rozspojnione, to oczywiscie zostanie usuniete 
        // wiecej niz trzeba. Spoko :)
        db_query("DELETE FROM {menu_links} WHERE mlid = %d", $binding['menu_link_id']);

        if ($url_alias) {
            db_query("DELETE FROM {url_alias} WHERE pid =%d", $binding['path_id']);
        }

	db_query("DELETE FROM {scholar_nodes} WHERE node_id = %d", $binding['node_id']);

        // Tutaj musimy uzyc nodeapi zeby poprawnie usunac rekord wezla,
        // usuniete zostana linki menu i aliasy sciezek.
        node_delete($binding['node_id']);
    }
} // }}}

// vim: fdm=marker
