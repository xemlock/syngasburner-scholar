<?php

/**
 * Definicja menu wywoływana i zapamiętywana podczas instalacji modułu.
 * Implementacja hook_menu.
 * @return array
 */
function _scholar_menu() // {{{
{
    $root  = 'admin/scholar';
    $items = array();

    $items[$root] = array(
        'title'             => t('Scholar'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_index',
        '@scholar_path'     => 'root',
    );

    $items[$root . '/people'] = array(
        'title'             => t('People'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_people_list',
        'parent'            => $root,
        'file'              => 'pages/people.php',
        '@scholar_path' => 'people',
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

    $items[$root . '/conferences'] = array(
        'title'             => t('Conferences'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_goto',
        'page arguments'    => array($root . '/conferences/conference'),
    );
    $items += _scholar_generic_menu($root . '/conferences/conference',
        'conference', 
        t('Conferences'), 
        array('edit' => t('Edit conference'))
    );
    $items[$root . '/conferences/conference']['weight'] = -10;
    $items += _scholar_category_menu($root . '/conferences/conference', 'generics', 'conference', array(
        'edit'   => t('Edit conference category'),
        'delete' => t('Delete conference category'),
    ));

    $items += _scholar_generic_menu($root . '/conferences/presentation',
        'presentation',
        t('Presentations'), 
        array('edit' => t('Edit presentation'))
    );
    $items += _scholar_category_menu($root . '/conferences/presentation', 'generics', 'presentation', array(
        'edit'   => t('Edit presentation category'),
        'delete' => t('Delete presentation category'),
    ));

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
    $items += _scholar_category_menu($root . '/book', 'generics', 'book', array(
        'edit'   => t('Edit book category'),
        'delete' => t('Delete book category'),
    ));

    // zeby byly taby pierwszego poziomu w elementach w trainings/
    // korzeniem poddrzewa jest przekierowanie do listy szkolen.
    // Gdybysmy dali liste szkolej w korzeniu, a pozniej 2 razy DEFAULT_TASK
    // w trainings/training i trainings/training/list dostalibysmy taby
    // drugiego poziomu.
    $items[$root . '/trainings'] = array(
        'title'             => t('Trainings'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_goto',
        'page arguments'    => array($root . '/trainings/training'),
    );
    $items += _scholar_generic_menu($root . '/trainings/training',
        'training',
        t('Trainings'),
        array('edit' => t('Edit training'))
    );
    $items[$root . '/trainings/training']['weight'] = -10;
    $items += _scholar_generic_menu($root . '/trainings/class',
        'class',
        t('Classes'),
        array('edit' => t('Edit class'))
    );
    $items += _scholar_category_menu($root . '/trainings/class', 'generics', 'class', array(
        'edit'   => t('Edit class category'),
        'delete' => t('Delete class category'),
    ));

    $items[$root . '/file'] = array(
        'title'             => t('Files'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_file_list',
        'parent'            => $root,
        'file'              => 'pages/file.php',
        '@scholar_path'     => 'files',
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

    $items[$root . '/page'] = array(
        'type'              => MENU_LOCAL_TASK,
        'title'             => t('Pages'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_page_list',
        'parent'            => $root,
        'file'              => 'pages/page.php',
        'weight'            => 10,
        '@scholar_path'     => 'pages',
    );
    $items[$root . '/page/edit/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Edit page'),
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_page_form'),
        'parent'            => $root . '/page',
        'file'              => 'pages/page.php',
    );

    $items[$root . '/schema'] = array(
        'type'              => MENU_CALLBACK,
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_show_schema',
    );

    _scholar_menu_add_page_argument_positions($items);

    return $items;
} // }}}

function _scholar_menu_extract_paths($items) // {{{
{
    $paths = array();

    foreach ($items as $path => $item) {
        foreach ($item as $key => $value) {
            if (strncasecmp($key, '@scholar_path', 13)) {
                continue;
            }

            // key jest nazwa wlasciwosci, value nazwa sciezki

            // jezeli podano modyfikatory w postaci @scholar_path(f1, f2, f3)
            // uruchom je na sciezce tego elementu w sposob nastepujacy:
            // f3(f2(f1($path)))

            // 1. usun przedrostek
            $key = substr($key, 13);

            // 2. jezeli nawias otwierajacy jest pierwszym, a zamykajacy ostatnim
            //    znakiem usun je i przejdz do parsowania listy funkcji
            if ('(' == substr($key, 0, 1) && ')' == substr($key, -1)) {
                // 3. rozbij liste uzywajac przecinka, i pozbadz sie bialych znakow
                //    wokol kazdego elementu
                $modifiers = array_map('trim', explode(',', substr($key, 1, -1)));

                // 4. wywolaj funkcje modyfikujace sciezke
                foreach ($modifiers as $modifier) {
                    if (function_exists($modifier)) {
                        $path = $modifier($path);
                    }
                }
            }

            $paths[$value] = $path;
        }
    }

    return $paths;
} // }}}

/**
 * @param string $name
 *     nazwa ścieżki zdefiniowana w {@see _scholar_menu}.
 * @param string $path
 * @param ...
 *     parametry przekazane jako wartości symboli zastępczych umieszczonych
 *     w zmiennej $path
 * @return
 *     ścieżka w drzewie odpowiadająca podanej nazwie. Jeżeli ścieżka nie
 *     istnieje zwrócona zostaje ścieżka o nazwie 'root'.
 */
function scholar_path($path_name, $subpath = '') // {{{
{
    static $paths = null;

    if (null === $paths) {
        $cid = 'scholar_path';

        if (!($data = cache_get($cid))) {
            $paths = _scholar_menu_extract_paths(_scholar_menu());
            cache_set($cid, $paths);

        } else {
            $paths = $data->data;
        }
    }

    if (isset($paths[$path_name])) {
        $subpath = (string) $subpath;

        // jezeli podano wartosci dla symboli zastepczych, wstaw je
        // do podanej pod-sciezki
        if (func_num_args() > 2) {
            $args = array_slice(func_get_args(), 2);
            $subpath = vsprintf($subpath, $args);
        }

        $subpath = ltrim($subpath, '/');

        if (strlen($subpath)) {
            $subpath = '/' . ltrim($subpath, '/');
        }

        return $paths[$path_name] . $subpath;
    }

    return $paths['root'];
} // }}}

function scholar_show_schema() // {{{
{
    $html = '';

    foreach (drupal_get_schema() as $name => $table) {
        if (strncmp('scholar_', $name, 8)) {
            continue;
        }

        $html .= db_prefix_tables(
            implode(";\n", db_create_table_sql($name, $table)) . ";\n"
        );
        $html .= "\n";
    }

    drupal_set_title('Schema');
    return '<pre><code class="sql">' . $html . '</code></pre>';
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
        // ze sciezka kategorii jest tak, ze aby byla ona na tym samym
        // poziomie co odnosniki do kategoryzowanych rekordow nie moze
        // istniec element menu o sciezce $root_path/category.
        // Aby sciezke moc z powodzeniem wykorzystac w scholar_path()
        // musimy sciezke o katalog wyzej oznaczyc annotacja, stad dirname
        '@scholar_path(dirname)' => "categories.$table_name.$subtype",
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
        '@scholar_path'     => "generics.$subtype",
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
    $items[$root_path . '/children/%/%'] = array(
        'type'              => MENU_CALLBACK,
        // brak tytulu, funkcja odpowiedzialna za generowanie strony musi sama
        // go ustawic
        'access arguments'  => array('administer scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_generics_children_form', $subtype),
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
