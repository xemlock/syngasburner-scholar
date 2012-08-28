<?php

/**
 * Pola formularza do tworzenia / edycji powiązanych węzłów.
 *
 * @param array $row
 * @param string $table_name
 * @return array
 */
function scholar_nodes_subform() // {{{
{
    $have_menu    = module_exists('menu');
    $have_path    = module_exists('path');
    $have_gallery = module_exists('gallery');

    if ($have_menu) {
        $menus = module_invoke('menu', 'get_menus');
        $parent_options = (array) module_invoke('menu', 'parent_options', $menus, null);
    }

    if ($have_gallery) {
        $gallery_options = (array) module_invoke('gallery', 'gallery_options');

        // gallery_options zawiera co najmniej jeden element, odpowiadajacy
        // pustej galerii. Jezeli tylko on jest dostepny, nie ma sensu dodawac
        // elementow zwiazanych z wyborem galerii.

        if (count($gallery_options) <= 1) {
            $gallery_options = null;
        }
    }

    $form = array(
        '#tree' => true,
    );

    foreach (scholar_languages() as $code => $name) {
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
            '#type'     => 'scholar_textarea',
            '#title'    => t('Body'),
        );

        if ($have_menu) {
            $container['menu'] = array(
                '#type'     => 'fieldset',
                '#title'    => t('Menu settings'),
                '#collapsible' => true,
                '#collapsed' => true,
                '#tree'     => true,
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
                '#options'  => $parent_options,
                '#description' => t('The maximum depth for an item and all its children is fixed at 9. Some menu items may not be available as parents if selecting them would exceed this limit.'),
            );
            $container['menu']['weight'] = array(
                '#type'     => 'weight',
                '#title'    => t('Weight'),
                '#delta'    => 50,
                '#default_value' => 0,
                '#description' => t('Optional. In the menu, the heavier items will sink and the lighter items will be positioned nearer the top.'),
            );
        }

        if ($have_path) {
            $container['path'] = array(
                '#type'     => 'fieldset',
                '#title'    => t('URL path settings'),
                '#collapsible' => true,
                '#collapsed' => true,
            );
            $container['path']['path'] = array(
                '#type'     => 'textfield',
                '#title'    => t('URL path alias'),
                '#description' => t('Optionally specify an alternative URL by which this node can be accessed.'),
            );
        }

        if ($have_gallery && $gallery_options) {
            $container['gallery'] = array(
                '#type'         => 'fieldset',
                '#title'        => t('Gallery settings'),
                '#collapsible'  => true,
                '#collapsed'    => true,
            );
            $container['gallery']['gallery_id'] = array(
                '#type'         => 'select',
                '#title'        => t('Gallery'),
                '#description'  => t('Select a gallery attached to this node.'),
                '#options'      => $gallery_options,
            );
            $container['gallery']['gallery_layout'] = array(
                '#type'         => 'hidden',
                '#default_value' => 'horizontal', // poziomy uklad galerii
            );
        }

        $form[$code] = $container;
    }

    return $form;
} // }}}

// vim: fdm=marker
