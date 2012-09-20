<?php

function _scholar_pages_system_index_menu($path_name) // {{{
{
    // chcemy pobrac liste dzieci danej sciezki
    $path = rtrim(scholar_path($path_name), '/') . '/';
    $output = '';
    $menu = scholar_menu_items($path);

    if ($menu) {
        $output .= '<ul>';
        foreach ($menu as $path => $item) {
            $output .= '<li>' . l($item['title'], $path) . '</li>';
        }
        $output .= '</ul>';
    }

    return $output;
} // }}}

function scholar_pages_system_index() // {{{
{
    scholar_add_css();
    drupal_add_js('misc/collapse.js');

    $view = new scholar_view;
    $view->setTemplateDir(SCHOLAR_TEMPLATE_DIR);
    $view->assignFromArray(array(
        'articles'      => _scholar_pages_system_index_menu('generics.article'),
        'journals'      => _scholar_pages_system_index_menu('generics.journal'),
        'conferences'   => _scholar_pages_system_index_menu('generics.conference'),
        'presentations' => _scholar_pages_system_index_menu('generics.presentation'),
        'trainings'     => _scholar_pages_system_index_menu('generics.training'),
        'classes'       => _scholar_pages_system_index_menu('generics.class'),
        'people'        => _scholar_pages_system_index_menu('people'),
        'files'         => _scholar_pages_system_index_menu('files'),
    ));
    return $view->render('index.tpl');
} // }}}
