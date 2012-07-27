<?php

/* 
 * Narzędzia do manipulacji węzłami
 * 
 * @author xemlock
 * @version 2012-07-27
 */

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
//echo __FUNCTION__;echo '<pre>';var_dump($node);echo '<pre>'; exit;

    // przygotuj identyfikator powiazanego linka w menu
    $mlid = isset($node->menu['mlid']) ? intval($node->menu['mlid']) : null;
    if (empty($mlid)) {
        $mlid = 'NULL';
    }

    // usuń obecne dowiązanie dla tego wezla i utwórz nowe
    db_query("DELETE FROM {scholar_nodes} WHERE node_id = %d", $node->nid);
    db_query(
        "INSERT INTO {scholar_nodes} (table_name, object_id, node_id, language, menu_link_id, path_id) VALUES ('%s', %d, %d, '%s', %s, NULL)",
        $table_name, $object_id, $node->nid, $language, $mlid
    );

    // obejscie problemu z aliasami i wielojezykowoscia, poprzez wymuszenie
    // neutralnosci jezykowej aliasu, patrz http://drupal.org/node/347265
    // (URL aliases not working for content not in default language)
    $path = null;

    if (module_exists('path') && isset($node->path)) {
        // wyeliminuj problemu z duplikatami
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
    $result = false;

    $query = db_query(
        "SELECT * FROM {scholar_nodes} WHERE table_name = '%s' AND object_id = %d AND language = '%s'",
        $table_name, $object_id, $language
    );

    if ($binding = db_fetch_array($query)) {
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
                    // pid jest potrzebne przy usuwaniu/edycji aliasow tak,
                    // by nie zostawiac osieroconych rekordow
                    $node->pid  = $row['pid'];
                    $node->path = $row['dst'];
                }
            }

            $result = $node;
        }
    }

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
    $node->language = 'en';

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

