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
        "INSERT INTO {scholar_nodes} (table_name, object_id, node_id, language, menu_link_id, path_id) VALUES ('%s', %d, %d, '%s', %s, NULL)",
        $table_name, $object_id, $node->nid, $language, $mlid
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

            if (module_exists('path')) {
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
    $bindings = _scholar_fetch_node_binding($object_id, $table_name);
    foreach ($bindings as $binding) {
        // Tutaj musimy uzyc nodeapi zeby poprawnie usunac rekord wezla,
        // usuniete zostana linki menu i aliasy sciezek.
        node_delete($binding['node_id']);

        // Dla absolutnej pewnosci usun powiazane linki menu i aliasy.
        // Jezeli dane sa rozspojnione, to oczywiscie zostanie usuniete 
        // wiecej niz trzeba. Spoko :)
        db_query("DELETE FROM {menu_links} WHERE mlid = %d", $binding['menu_link_id']);
        db_query("DELETE FROM {url_alias} WHERE pid =%d", $binding['path_id']);

        db_query("DELETE FROM {scholar_nodes} WHERE node_id = %d", $binding['node_id']);
    }
} // }}}

