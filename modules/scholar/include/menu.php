<?php

function scholar_admin_path($path = '') // {{{
{
    if (strlen($path)) {
        $path = '/' . ltrim($path);
    }
    return 'admin/scholar' . $path;
} // }}}

/**
 * Definicja menu wywoływana i zapamiętywana podczas instalacji modułu.
 * Implementacja hook_menu.
 * @return array
 */
function scholar_menu() // {{{
{
    $root = scholar_admin_path();

    $items = array();

    $items[$root] = array(
        'title'             => t('Scholar'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_index',
    );

    $items[$root . '/people'] = array(
        'title'             => t('People'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_people_list',
        'parent'            => $root,
        'file'              => 'pages/people.php',
    );
    $items[$root . '/people/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => t('List'),
        'weight'            => -10, // na poczatku listy
    );
    $items[$root . '/people/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Add person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_form'),
        'parent'            => $root . '/people',
        'file'              => 'pages/people.php',
    );
    $items[$root . '/people/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_form'),
        'parent'            => $root . '/people',
        'file'              => 'pages/people.php',
    );
    $items[$root . '/people/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Delete person'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_delete_form'),
        'parent'            => $root . '/people',
        'file'              => 'pages/people.php',
    );
    $items[$root . '/people/itempicker'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Select people'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_itempicker',
        'page arguments'    => array('scholar_people_itempicker'),
        'parent'            => $root . '/people',
        'file'              => 'pages/people.php',
    );

    $items += _scholar_generic_menu($root . '/conference',
        'conference', 
        t('Conferences'), 
        array('edit' => t('Edit conference'))
    );
    $items += _scholar_category_menu($root . '/conference', 'generics', 'conference');

    $items += _scholar_generic_menu($root . '/presentation',
        'presentation',
        t('Presentations'), 
        array('edit' => t('Edit presentation'))
    );
    $items += _scholar_category_menu($root . '/presentation', 'generics', 'presentation');

    $items += _scholar_generic_menu($root . '/article',
        'article',
        t('Articles'),
        array(
            'add'  => t('Add article'),
            'edit' => t('Edit article'),
        )
    );
    $items += _scholar_generic_menu($root . '/book',
        'book',
        t('Books'),
        array('edit' => t('Edit book'))
    );
    $items += _scholar_category_menu($root . '/book', 'generics', 'book');

    $items[$root . '/file'] = array(
        'title'             => t('Files'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_file_list',
        'parent'            => $root,
        'file'              => 'pages/file.php',
    );
    $items[$root . '/file/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => t('List'),
        'weight'            => -10,
    );
    $items[$root . '/file/upload'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Upload file'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_upload_form'),
        'parent'            => $root . '/file',
        'file'              => 'pages/file.php',
    );
    $items[$root . '/file/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit file'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_edit_form'),
        'parent'            => $root . '/file',
        'file'              => 'pages/file.php',
    );
    $items[$root . '/file/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Delete file'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_file_delete_form'),
        'parent'            => $root . '/file',
        'file'              => 'pages/file.php',
    );
    $items[$root . '/file/itempicker'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('File selection'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_itempicker',
        'page arguments'    => array('scholar_file_itempicker'),
        'parent'            => $root . '/file',
        'file'              => 'pages/file.php',
    );

    _scholar_menu_add_page_argument_positions($items);

    return $items;
} // }}}

function _scholar_category_menu($root_path, $table_name, $subtype = null, $titles = array()) // {{{
{
    $titles = array_merge(array(
        'list'   => t('Categories'),
        'add'    => t('Add category'),
        'edit'   => t('Edit category'),
        'delete' => t('Delete category'),
    ), $titles);

    $items[$root_path . '/category/list'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => $titles['list'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_category_list',
        'page arguments'    => array($table_name, $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/category.php',
        'weight'            => 5,
    );
    $items[$root_path . '/category/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => $titles['add'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_category_form', $table_name, $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/category.php',
        'weight'            => 10,
    );
    $items[$root_path . '/category/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => $titles['edit'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_category_form', $table_name, $subtype),
        'file'              => 'pages/category.php',
    );
    $items[$root_path . '/category/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => $titles['delete'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_category_delete_form'),
        'file'              => 'pages/category.php',
    );

    return $items;
} // }}}

function _scholar_generic_menu($root_path, $subtype, $title, $titles = array()) // {{{
{
    $titles = array_merge(array(
        'list'       => t('List'),
        'add'        => t('Add'),
        'edit'       => t('Edit'),
        'delete'     => t('Delete'),
    ), $titles);

    $items[$root_path] = array(
        'title'             => $title,
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_generics_list',
        'page arguments'    => array($subtype),
        'parent'            => dirname($root_path),
        'file'              => 'pages/generic.php',
    );
    $items[$root_path . '/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => $titles['list'],
        'weight'            => -10,
    );
    $items[$root_path . '/add'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => $titles['add'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_form', $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/generic.php',
        'weight'            => 0,
    );
    $items[$root_path . '/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => $titles['edit'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_form', $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/generic.php',
    );
    $items[$root_path . '/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => $titles['delete'],
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_delete_form', $subtype),
        'parent'            => $root_path,
        'file'              => 'pages/generic.php',
    );

    return $items;
} // }}}

/**
 * @param array &$items
 */
function _scholar_menu_add_page_argument_positions(&$items) // {{{
{
    foreach ($items as $path => &$item) {
        if (false === strpos($path, '%')) {
            continue;
        }

        if (!isset($item['page arguments'])) {
            $item['page arguments'] = array();
        }

        $parts = explode('/', $path);
        for ($i = 0, $n = count($parts); $i < $n; ++$i) {
            if (strpos($parts[$i], '%') !== false) {
                $item['page arguments'][] = $i;
            }
        }
    }
    unset($item);
} // }}}


// vim: fdm=marker
