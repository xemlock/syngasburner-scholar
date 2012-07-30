<?php

function scholar_perm() {
  return array('administer scholar', 'manage scholar contents');
}

function scholar_form_alter(&$form, &$form_state, $form_id)
{
    // Nie dopuszczaj do bezposredniej modyfikacji wezlow
    // aktualizowanych automatycznie przez modul scholar.
    // Podobnie z wykorzystawymi eventami.
    // echo '<code>', $form_id, '</code>';
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
    $items['scholar/people/list'] = array(
        'type'              => MENU_DEFAULT_LOCAL_TASK,
        'title'             => t('List'),
        'weight'            => -10, // na poczatku listy
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
    $items['scholar/people/delete/%'] = array(
        'type'              => MENU_CALLBACK,
        'title'             => t('Delete person'),
        'access arguments'  => array('use scholar'),
        'page callback'     => 'scholar_render_form',
        'page arguments'    => array('scholar_people_delete_form', 3),
        'parent'            => 'scholar/people',
        'file'              => 'scholar.people.php',
    );
    return $items;
}

function scholar_index()
{
    return '<pre>' . print_r(func_get_args(), 1) . '</pre>';
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
function p($array, $func = null)
{
    static $last = 0;
    $colors = array('red', 'green', 'blue', 'navy', 'magenta', 'orange', 'brown', 'violet');

    echo '<pre style="color:' . $colors[$last] . ';border:1px dotted #999;background:#eee;padding:10px">', $func, $func ? ': ' : '', print_r($array, 1), '</pre>';

    $last = ($last + 1) % count($colors);
}
/**
 * Deklaracja dodatkowych pól formularza.
 *
 * @return array
 */
function scholar_elements() // {{{
{
    $elements['scholar_checkboxed_container'] = array(
        '#input' => true,
        '#checkbox_name' => 'status',
    );

    return $elements;
} // }}}

/**
 * Funkcja renderująca kontener.
 *
 * @param array $element
 * @return string
 */
function theme_scholar_checkboxed_container($element) // {{{
{
    $parents = $element['#parents'];
    if ($parents) {
        $name = array_shift($parents) . ($parents ? '[' . implode('][', $parents) . ']' : '') . '[' . $element['#checkbox_name'] . ']';
    } else {
        $name = $element['#checkbox_name'];
    }

    $output = '<div style="border:1px solid black" id="' . $element['#id'] . '-wrapper">';
    $output .= '<label><input type="checkbox" name="' . $name .'" id="'.$element['#id'].'" value="1" onchange="$(\'#'.$element['#id'].'-wrapper .contents\')[this.checked ? \'show\' : \'hide\']()"' . ($element['#value'] ? ' checked="checked"' : ''). '/><input type="hidden" name="pi" value="3.14159" />' . $element['#title'] . '</label>';
    $output .= '<div class="contents">';
    $output .= $element['#children'];
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<script type="text/javascript">/*<![CDATA[*/$(function(){
        if (!$("#'.$element['#id'].'").is(":checked")) {
            $("#'.$element['#id'].'-wrapper .contents").hide();
        }
})/*]]>*/</script>';

    return $output;
} // }}}

/**
 * @param array $element
 * @param mixed $post           Podtablica z wartościami dla tego elementu
 * @return array                Wartość checkboksa kontrolujacego ten kontener
 */
function form_type_scholar_checkboxed_container_value($element, $post = false) // {{{
{
    $checkbox_name = $element['#checkbox_name'];
    if ($post) {
        $value = isset($post[$checkbox_name]) && $post[$checkbox_name] ? 1 : 0;
    } else {
        $value = $element['#default_value'] ? 1 : 0;
    }

    // musi zwrocic tablice, zeby dzieci kontenera mogly wpisac swoje wartosci
    return array(
        $checkbox_name => $value
    );
} // }}}

/**
 * Funkcja wymagana do renderowania dodatkowych elementów formularza.
 *
 * @return array
 */
function scholar_theme() // {{{
{
    $theme['scholar_checkboxed_container'] = array(
        'arguments' => array('element' => null),
    );

    return $theme;
} // }}}


require_once dirname(__FILE__) . '/scholar.node.php';
