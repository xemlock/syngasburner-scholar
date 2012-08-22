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
 * @param int $row_id
 * @param string $table_name
 * @param string $language OPTIONAL     jeżeli nie podany zostaną pobrane
 *                                      wiązania dla wszystkich języków
 * @return false|array                  false jeżeli podano język, pusta
 *                                      tablica jeżeli go nie podano
 */
function _scholar_fetch_node_binding($row_id, $table_name, $language = null) // {{{
{
    $sql = sprintf(
        "SELECT * FROM {scholar_nodes} WHERE table_name = '%s' AND row_id = %d",
        db_escape_string($table_name), $row_id
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
 * @param int $row_id
 * @param string $table_name
 * @param string $body OPTIONAL
 */
function _scholar_bind_node(&$node, $row_id, $table_name, $body = '') // {{{
{
    if (empty($node->nid)) {
        return false;
    }

    $mlid = isset($node->menu['mlid']) ? intval($node->menu['mlid']) : null;
    if (empty($mlid)) {
        $mlid = 'NULL';

        // nie usuwamy aliasu gdy jest pusty link w menu, poniewaz alias
        // nalezy do wezla (segmentu) a nie do linku
    }

    // usuń obecne dowiązanie dla tego wezla i utwórz nowe
    db_query(
        "DELETE FROM {scholar_nodes} WHERE node_id = %d OR (table_name = '%s' AND row_id = %d AND language = '%s')", 
        $node->nid, $table_name, $row_id, $node->language
    );

    db_query(
        "INSERT INTO {scholar_nodes} (table_name, row_id, node_id, language, status, menu_link_id, path_id, last_rendered, body) VALUES ('%s', %d, %d, '%s', %d, %s, NULL, NULL, '%s')",
        $table_name, $row_id, $node->nid, $node->language, $node->status, $mlid, $body
    );

    // obejscie problemu z aliasami i wielojezykowoscia, poprzez wymuszenie
    // neutralnosci jezykowej aliasu, patrz http://drupal.org/node/347265
    // (URL aliases not working for content not in default language)

    if (module_exists('path') && isset($node->path)) {
        $path = trim($node->path);

        // wyeliminuj aktualne aliasy dla tej sciezki
        db_query("DELETE FROM {url_alias} WHERE dst = '%s' OR src = 'node/%d'", $path, $node->nid);

        if (strlen($path)) {
            path_set_alias('node/'. $node->nid, $path, null, '');

            // path_set_alias nie zwraca identyfikatora utworzonej sciezki,
            // wiec musimy wyznaczyc go sami
            $query = db_query("SELECT * FROM {url_alias} WHERE dst = '%s'", $path);
            if ($url_alias = db_fetch_array($query)) {
                db_query("UPDATE {scholar_nodes} SET path_id = %d WHERE node_id = %d", $url_alias['pid'], $node->nid);
            }
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
    // a nie tymi dostarczonymi przez nodeapi
    if ($binding['menu_link_id']) {
        $query = db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $binding['menu_link_id']);

        if ($row = db_fetch_array($query)) {
            $node->menu = $row;
        } else {
            $node->menu = null;
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
        } else {
            $node->pid  = null;
            $node->path = null;
        }
    }
} // }}}

/**
 * Pobiera z bazy rekord węzła przypisany do rekordu z danej tabeli,
 * z nieprzetworzoną treścią, z wypełnionymi polami menu i path.
 *
 * @param int $row_id
 * @param string $table_name
 * @param string $language
 * @return false|object
 */
function scholar_fetch_node($row_id, $table_name, $language) // {{{
{
    $node = false;

    if ($binding = _scholar_fetch_node_binding($row_id, $table_name, $language)) {
        // Reczne pobranie zamiast node_load() zeby nie uniknac wywolania
        // hooka nodeapi, poniewaz dostep do tego wezla ma byc jedynie 
        // dla modulu scholar.
        $rendering = _scholar_rendering_enabled(false);

        if ($node = node_load($binding['node_id'])) {
            _scholar_populate_node($node, $binding);
        }

        _scholar_rendering_enabled($rendering);
    }

    return $node;
} // }}}

/**
 * Zwraca wszystkie wezly (segmenty) powiązane z tym rekordem,
 * indeksowane kodem języka.
 * @return array
 */
function scholar_load_nodes($row_id, $table_name) // {{{
{
    $nodes = array();
    $rendering = _scholar_rendering_enabled(false);

    foreach (_scholar_fetch_node_binding($row_id, $table_name) as $binding) {
        if ($node = node_load($binding['node_id'])) {
            _scholar_populate_node($node, $binding);
            $nodes[$node->language] = $node;
        }
    }

    _scholar_rendering_enabled($rendering);

    return $nodes;
} // }}}

/**
 * @param int $row_id
 * @param string $table_name
 * @param array $nodes          wartość w postaci zwracanej przez podformularz węzłów
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

        if (isset($node_data['menu'])) {
            // wyznacz parenta z selecta, na podstawie modules/menu/menu.module:429
            $menu = $node_data['menu'];
            list($menu['menu_name'], $menu['plid']) = explode(':', $node_data['menu']['parent']);

            // menu jest zapisywane za pomoca hooku menu_nodeapi
            $node->menu = $menu;
        }

        // alias zapisywany za pomoca path_nodeapi
        if (isset($node_data['path'])) {
            $node->path = rtrim($node_data['path']['path'], '/');
        }

        // podobnie galeria, za pomoca gallery_nodeapi
        if (isset($node_data['gallery'])) {
            $node->gallery_id = $node_data['gallery']['gallery_id'];
            $node->gallery_layout = $node_data['gallery']['gallery_layout'];
        }

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
 * @param int $row_id
 * @param string $table_name
 */
function scholar_save_node(&$node, $row_id, $table_name) // {{{
{
    $body = trim($node->body);

    $node->type = 'scholar';
    $node->body = ''; // puste body, bo tresc do przetworzenia zostanie
                      // zapisana w bindingu

    $node->revision = null; // nigdy nie tworz nowych rewizji, poniewaz
                            // tresc jest generowana automatycznie

    // jezeli podano pusta nazwe linku w menu, a jest podany jego mlid
    // usun ten link. Trzeba to zrobic tutaj, bo menu_nodeapi tego nie
    // zrobi.
    if (isset($node->menu)) {
        $link_title = trim($node->menu['link_title']);

        if (0 == strlen($link_title)) {
            menu_link_delete(intval($node->menu['mlid']));
            unset($node->menu);
        }
    }

    node_save($node);

    // dodaj węzeł do indeksu powiązanych węzłów
    _scholar_bind_node($node, $row_id, $table_name, $body);

    // przywroc body do wartosci sprzed zapisu
    $node->body = $body;
} // }}}

/**
 * Usuwa węzły powiązane z tym obiektem.
 *
 * @param int $row_id
 * @param string $table_name
 */
function scholar_delete_nodes($row_id, $table_name) // {{{
{
    // po pierwsze zapamietaj wszystkie komunikaty, usuwanie wezla
    // bedzie utawialo wlasne, ktorych nie chcemy pokazywac -
    // dla kazdego usunietego wezla "Page has been deleted".
    $messages = drupal_get_messages();

    $bindings  = _scholar_fetch_node_binding($row_id, $table_name);
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
        // Poniewaz node_delete wywoluje nodeapi z parametrem load, musimy
        // zmienic typ wezla na ignorowany podczas ladowania.
        db_query("UPDATE {node} SET type = 'page' WHERE nid = %d", $binding['node_id']);

        // usuwamy binding
        db_query("DELETE FROM {scholar_nodes} WHERE node_id = %d", $binding['node_id']);

        // teraz spokojnie mozemy usunac wezel, jako ze przestal on
        // nalezec do scholara
        node_delete($binding['node_id']);
    }

    // usun komunikaty ustawione podczas usuwania wezla
    drupal_get_messages();

    // przywroc komunikaty sprzed usuniecia wezlow
    foreach ($messages as $type => $type_messages) {
        foreach ($type_messages as $message) {
            drupal_set_message($message, $type);
        }
    }
} // }}}

/**
 * Zwraca informacje o rekordzie będącym właścicielem węzła
 * (segmentu) o podanym identyfikatorze.
 *
 * @param int $node_id
 * @return false|array
 */
function scholar_node_owner_info($node_id) // {{{
{
    $query = db_query("SELECT * FROM {scholar_nodes} WHERE node_id = %d", $node_id);
    return db_fetch_array($query);
} // }}}


// vim: fdm=marker
