<?php

function scholar_form_alter(&$form, &$form_state, $form_id)
{
    // Nie dopuszczaj do bezposredniej modyfikacji wezlow
    // aktualizowanych automatycznie przez modul scholar.
    // Podobnie z wykorzystawymi eventami.
    echo '<code>', $form_id, '</code>';
    if ('page_node_form' == $form_id  && $form['#node']) {
        $query = db_query("SELECT * FROM {scholar_nodes} WHERE node_id = %d", $form['#node']->nid);
        $row = db_fetch_array($query);
        if ($row) {
            switch ($row['table_name']) {
                case 'people':
                    $url = 'scholar/people/edit/' . $row['object_id'];
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

function scholar_nodeapi($node, $op)
{
    if ($op == 'load') {
        echo '<pre>', $op, ': ', print_r($node, 1), '</pre>';
    }
}

function scholar_menu()
{
    $items = array();

    $items['scholar'] = array(
        'title'             => t('Scholar'),
        'access arguments'  => array('use scholar'),
        'page callback'     => 'scholar_index',
    );

    $items['scholar/people'] = array(
        'title'             => t('People'),
        'access arguments'  => array('use scholar'),
        'page callback'     => 'scholar_people_list',
        'parent'            => 'scholar',
        'file'              => 'scholar.people.php',
    );
    $items['scholar/people/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Add person'),
        'access arguments'  => array('use scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_form'),
        'parent'            => 'scholar/people',
        'file'              => 'scholar.people.php',
    );
    $items['scholar/people/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit person'),
        'access arguments'  => array('use scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_form', 3),
        'parent'            => 'scholar/people',
        'file'              => 'scholar.people.php',
    );

    return $items;
}

function scholar_index()
{
    return '<pre>' . print_r(func_get_args(), 1) . '</pre>';
}
function scholar_people_list()
{
    $query = db_query('SELECT * FROM {scholar_people} ORDER BY last_name, first_name');
    $html = '';
    while ($row = db_fetch_array($query)) {
        $html .= '<div><a href="' . url('scholar/people/edit/' . $row['id']) . '">' . $row['first_name'] . ' ' . $row['last_name'] . '</a></div>';
    }
    return $html;
}

function scholar_people_add()
{
    return __FUNCTION__;
}

function scholar_render($html)
{
    if (isset($_REQUEST['modal'])) {
        echo '<html><head><title>Scholar modal</title></head><body>' . $html . '</body></html>';
        exit;
    }
    return $html;
}

function scholar_render_form()
{
    $args = func_get_args();
    $html = call_user_func_array('drupal_get_form', $args);
    return scholar_render($html);
}


/**
 * Tworzy powiązanie między rekordem z podanej tabeli a węzłem.
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

    // usuń obecne dowiązanie i utwórz nowe
    db_query("DELETE FROM {scholar_nodes} WHERE node_id = %d", $node->nid);
    db_query(
        "INSERT INTO {scholar_nodes} (table_name, object_id, node_id, language) VALUES ('%s', %d, %d, '%s')",
        $table_name, $object_id, $node->nid, $language
    );

    return true;
} // }}}

/**
 * Pobiera z bazy rekord węzła przypisany do rekordu z danej tabeli.
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
    $row = db_fetch_array($query);

    if ($row) {
        // Potrzebne są tutaj tylko główne pola, wywoływanie node api
        // jest niewskazane, stąd rezygnacja z node_load(). 
        $query = db_query("SELECT * FROM {node} WHERE nid = %d", $row['node_id']);
        $row   = db_fetch_array($query);

        if ($row) {
            $result = (object) $row;
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

