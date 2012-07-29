<?php

/*
 * Narzędzia do manipulowania rekordami osób
 *
 * @author xemlock
 * @version 2012-07-27
 */

/**
 * Pobiera z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param int $id
 */
function scholar_people_fetch_row($id) // {{{
{
    $query = db_query('SELECT * FROM {scholar_people} WHERE id = ' . intval($id));
    $row   = db_fetch_array($query);

    if (empty($row)) {
        drupal_set_message(t('Invalid person id supplied (%id)', array('%id' => $id)), 'error');
    }

    return $row;
} // }}}

/**
 * Usuwa z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param int $id
 */
function scholar_people_delete($id) // {{{
{
    scholar_delete_nodes($id, 'people');
    db_query("DELETE FROM {scholar_authors} WHERE person_id = %d", $id);
    db_query("DELETE FROM {scholar_people} WHERE id = %d", $id);
} // }}}

function scholar_people_form(&$form_state, $id = null) // {{{
{
    // drupal_set_title(t('New person'));
    $row = $id ? scholar_people_fetch_row($id) : null;

    $form = array(
        '#row'      => $row,
        '#submit'   => array(
            'scholar_people_form_submit',
        ),
        '#validate' => array(
            'scholar_people_form_validate',
        ),
        '#attributes' => array(
            'class' => 'scholar-people-form',
        ),
    );

    // ustawienia osoby
    $form['id'] = array(
        '#type'     => 'hidden',
    );
    $form['first_name'] = array(
        '#type'     => 'textfield',
        '#title'    => t('First name'),
        '#required' => true,
    );
    $form['last_name'] = array(
        '#type'     => 'textfield',
        '#title'    => t('Last name'),
        '#required' => true,
    );

    gallery_image_selector($form, 'image_id', isset($row['image_id']) ? $row['image_id'] : null);
    $form['image_id']['#title'] = t('Photo');

    $form['status'] = array(
        '#type'     => 'checkbox',
        '#title'    => t('Opublikuj stronę osoby'),
        '#description' => 'If checked an auto-generated page dedicated to this person will be publicly available.',
        '#default_value' => true,
        '#attributes' => array(
            // ukryj fieldsety z ustawieniami menu 
            //'onchange' => "var sel='.scholar-people-form-menu-settings',chk=this.checked;\$(sel).each(function(){\$(this)[chk?'show':'hide']()})",
        ),
    );

    // link do wezlow zalezne od jezyka, ustawienia aliasu
    $languages = Langs::languages();
    $default_lang = Langs::default_lang();

    $form[] = array(
        '#type' => 'markup',
        '#value' => '<div style="clear:both;"><hr/></div>',
    );

    foreach ($languages as $code => $name) {
        $form[$code] = array();
       
        $form[$code]['status'] = array(
            '#type' => 'scholar_checkboxed_container',
            '#title' => t('Publish page in language: @lang', array('@lang' => $name)) . ' (<img src="' . base_path() . 'i/flags/' . $code . '.png" alt="" title="' . $name . '" style="display:inline" />)',
        );

        $form[$code]['fieldset'] = array(
            '#type' => 'fieldset',
            '#title' => t('Menu settings'),
            '#collapsible' => true,
            '#collapsed' => $code != $default_lang,
            '#tree' => true,
            '#attributes' => array(
                'class' => 'scholar-people-form-menu-settings',
            ),
        );

        $elements = array();
        $elements['node'] = array();
        $elements['node']['title'] = array(
            '#type'     => 'textfield',
            '#title'    => t('Title'),
            '#description' => t('Page title, if not given it will default to this person\'s full name.'),
        );
        $elements['node']['body'] = array(
            '#type'     => 'textarea',
            '#title'    => t('Body'),
            '#description' => t('Use BBCode markup, supperted tags are listed <a href="#!">here</a>'),
        );

        $elements['menu'] = array();
        $elements['menu']['mlid'] = array(
            '#type'     => 'hidden',
        );
        $elements['menu']['link_title'] = array(
            '#type'     => 'textfield',
            '#title'    => t('Menu link title'),
            '#description' => t('The link text corresponding to this item that should appear in the menu. Leave blank if you do not wish to add this post to the menu.'),
        );
        $elements['menu']['parent'] = array(
            '#type'     => 'select',
            '#title'    => t('Parent item'),
            '#options'  => menu_parent_options(menu_get_menus(), null),
            '#description' => t('The maximum depth for an item and all its children is fixed at 9. Some menu items may not be available as parents if selecting them would exceed this limit.'),
        );
        $elements['menu']['weight'] = array(
            '#type'     => 'weight',
            '#title'    => t('Weight'),
            '#delta'    => 50,
            '#default_value' => 0,
            '#description' => t('Optional. In the menu, the heavier items will sink and the lighter items will be positioned nearer the top.'),
        );

        $elements['path'] = array(
            '#type'     => 'textfield',
            '#title'    => t('URL path alias'),
            '#description' => t('Optionally specify an alternative URL by which this node can be accessed. For example, type "about" when writing an about page. Use a relative path and don\'t add a trailing slash or the URL alias won\'t work.'),
        );

        $form[$code]['fieldset'] += $elements;
    }

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => t('Save changes'),
    );

    // jezeli formularz dotyczy konkretnego rekordu ustaw domyslne wartosci pol
    if ($row) {
        foreach ($row as $column => $value) {
            if (isset($form[$column])) {
                $form[$column]['#default_value'] = $value;
            }
        }

        // nadaj domyslne wartosci ustawieniom wezlow
        foreach ($languages as $code => $name) {
            if ($node = scholar_fetch_node($row['id'], 'people', $code)) {
                if ($node->menu) {
                    foreach ($node->menu as $column => $value) {
                        if (isset($form[$code]['menu'][$column])) {
                            $form[$code]['menu'][$column]['#default_value'] = $value;
                        }
                    }
                    $form[$code]['menu']['parent']['#default_value'] = $node->menu['menu_name'] . ':' . $node->menu['plid'];
                }

                $form[$code]['path']['#default_value'] = $node->path;
            }
        }
        
    }

    return $form;
} // }}}

/**
 * Zapisanie do bazy nowej osoby, lub modyfikacja istniejącej na
 * podstawie danych przesłanych w formularzu.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_people_form_submit($form, &$form_state) // {{{
{
    $row    = isset($form['#row']) ? $form['#row'] : null;
    $is_new = empty($row);
    $values = $form_state['values'];
    $nodes  = array();
    $langs  = Langs::languages();

    if ($row) {
        db_query(
            "UPDATE {scholar_people} SET first_name = '%s', last_name = '%s', image_id = '%s', status = %d WHERE id = %d",
            $values['first_name'],
            $values['last_name'],
            $values['image_id'],
            $values['status'],
            $row['id']
        );

        foreach ($langs as $code => $name) {
            $node = scholar_fetch_node($row['id'], 'people', $code);
            if ($node) {
                $nodes[$code] = $node;
            }
        }

    } else {
        db_query(
            "INSERT INTO {scholar_people} (first_name, last_name, image_id, status) VALUES ('%s', '%s', %d, %d)",
            $values['first_name'],
            $values['last_name'],
            $values['image_id'],
            $values['status']
        );
        $row = $values;
        $row['id'] = db_last_insert_id('scholar_people', 'id');
    }

    // przygotuj wezly, do ktorych zapisywane beda renderingi
    // strony danej osoby
    foreach ($langs as $code => $name) {
        // TODO
        // jezeli status jest zerowy, a wezel nie istnieje nie tworz nowego
        // jezeli jest wezel to ustaw w nim status na zero
        // wpp utworz jezeli trzeba wezel i ustaw jego status na 1
        if (empty($nodes[$code])) {
            $nodes[$code] = scholar_create_node();
        }

        $node = $nodes[$code];

        $node->status   = intval($values['status']);
        $node->type     = 'page';
        $node->language = $code;
        $node->title    = $values['first_name'] . ' ' . $values['last_name'];

        // wyznacz parenta z selecta, na podstawie modules/menu/menu.module:429
        $menu = $values[$code]['menu'];
        list($menu['menu_name'], $menu['plid']) = explode(':', $values[$code]['menu']['parent']);

        // menu jest zapisywane za pomoca hookow: menu_nodeapi, path_nodeapi
        $node->menu = $menu;
        $node->path = rtrim($values[$code]['path'], '/');

        node_save($node);

        // dodaj węzeł do indeksu powiązanych węzłów
        scholar_bind_node($node, $row['id'], 'people', $code);
    }

    drupal_set_message($is_new
        ? t('Person created successfully')
        : t('Person updated successfully')
    );
    drupal_goto('scholar/people');
} // }}}

function scholar_people_form_validate($form, &$form_state) // {{{
{
    return true;
} // }}}

function scholar_people_delete_form(&$form_state, $id) // {{{
{
    $row = scholar_people_fetch_row($id);
    if (empty($row)) {
        return '';
    }

    $form = array(
        '#row'      => $row,
    );
    $form['id'] = array(
        '#type'     => 'hidden',
        '#value'    => $row['id'],
    );

    $form = confirm_form($form,
        t('Are you sure you want to delete person (%first_name %last_name)?', 
            array(
                '%first_name' => $row['first_name'],
                '%last_name'  => $row['last_name'],
            )
        ),
        'scholar/people',
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );
    $form['#submit'][] = 'scholar_people_delete_submit';

    return $form;
} // }}}

function scholar_people_delete_submit($form, &$form_state) // {{{
{
    if ($form['#row']) {
        $row = $form['#row'];

        scholar_people_delete($row['id']);
        drupal_set_message(t(
            'Person deleted successfully (%first_name %last_name)',
            array(
                '%first_name' => $row['first_name'],
                '%last_name'  => $row['last_name'],
            )
        ));
    }
    drupal_goto('scholar/people');
} // }}}

function scholar_people_list() // {{{
{
    $query = db_query('SELECT * FROM {scholar_people} ORDER BY last_name, first_name');
    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['last_name']),
            check_plain($row['first_name']),
            l(t('edit'),   "scholar/people/edit/{$row['id']}"), 
            l(t('delete'), "scholar/people/delete/{$row['id']}")
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4)
        );
    }

    $header = array(
        array('data' => t('Last name'), 'field' => 'last_name', 'sort' => 'asc'),
        array('data' => t('First name'), 'field' => 'first_name'),
        array('data' => t('Operations'), 'colspan' => '2')
    );

    $html = '';
    $html .= theme('table', $header, $rows);
    return $html;
} // }}}
