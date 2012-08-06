<?php

/*
 * Narzędzia do manipulowania rekordami osób
 *
 * @author xemlock
 * @version 2012-08-02
 */

/**
 * Pobiera z bazy danych rekord osoby o podanym identyfikatorze.
 *
 * @param int $id               identyfikator osoby
 * @param bool $redirect        czy zgłosić błąd i przekierować do listy
 *                              osób, jeżeli osoba nie została znaleziona
 */
function scholar_people_fetch_row($id, $redirect = false) // {{{
{
    $query = db_query('SELECT * FROM {scholar_people} WHERE id = %d', $id);
    $row   = db_fetch_array($query);

    if (empty($row) && $redirect) {
        drupal_set_message(t('Invalid person id supplied (%id)', array('%id' => $id)), 'error');
        drupal_goto('scholar/people');
        exit;
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
    variable_set('scholar_last_change', date('Y-m-d H:i:s'));
} // }}}

function scholar_people_form(&$form_state, $id = null) // {{{
{
    $row  = $id ? scholar_people_fetch_row($id, true) : null;
    $form = array('#row' => $row);

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

    gallery_image_selector($form, 'image_id', empty($row['image_id']) ? null : $row['image_id']);
    $form['image_id']['#title'] = t('Photo');

    // link do wezlow zalezne od jezyka, ustawienia aliasu
    $languages = scholar_languages();
    $default_lang = language_default('language');

    $form[] = array(
        '#type' => 'markup',
        '#value' => '<div style="clear:both;"><hr/></div>',
    );

    $form['attachments'] = array(
        '#type' => 'fieldset',
        '#title' => t('File attachments'),
    );
    $form['attachments']['files'] = array(
        '#type' => 'scholar_attachment_manager',
    );

    $form['node'] = scholar_nodes_subform($row, 'people');

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => t('Save changes'),
    );

    // jezeli formularz dotyczy konkretnego rekordu ustaw domyslne wartosci pol
    if ($row) {
        foreach ($row as $column => $value) {
            // FIXME ze wzgledu na niedoskonalosc pakietu gallery domyslna wartosc
            // image_id musi byc ustawiona w gallery_image_selector() i nie 
            // moze zostac nadpisana tutaj, w przeciwnym razie podglad obrazu
            // nie zostanie wyswietlony.
            if (isset($form[$column]) && $column != 'image_id') {
                $form[$column]['#default_value'] = $value;
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
    $langs  = scholar_languages();
p($values);exit;
    if ($row) {
        db_query(
            "UPDATE {scholar_people} SET first_name = '%s', last_name = '%s', image_id = '%s' WHERE id = %d",
            $values['first_name'],
            $values['last_name'],
            $values['image_id'],
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
            "INSERT INTO {scholar_people} (first_name, last_name, image_id) VALUES ('%s', '%s', %d)",
            $values['first_name'],
            $values['last_name'],
            $values['image_id']
        );
        $row = $values;
        $row['id'] = db_last_insert_id('scholar_people', 'id');
    }

    // przygotuj wezly, do ktorych zapisywane beda renderingi
    // strony danej osoby
    foreach ($langs as $code => $name) {
        $status = intval((bool) $values[$code]['status']);

        // jezeli status jest zerowy, a wezel nie istnieje nie tworz nowego
        if (!$status && empty($nodes[$code])) {
            continue;
        }

        // jezeli jest wezel to ustaw w nim status na zero
        // wpp utworz jezeli trzeba wezel i ustaw jego status na 1
        if (empty($nodes[$code])) {
            $nodes[$code] = scholar_create_node();
        }

        $title = trim($values[$code]['title']);
        if (empty($title)) {
            $title = $values['first_name'] . ' ' . $values['last_name'];
        }

        $node = $nodes[$code];

        $node->status   = $status;
        $node->language = $code;
        $node->title    = $title;
        $node->body     = trim($values[$code]['body']);

        // wyznacz parenta z selecta, na podstawie modules/menu/menu.module:429
        $menu = $values[$code]['menu'];
        list($menu['menu_name'], $menu['plid']) = explode(':', $values[$code]['menu']['parent']);

        // menu jest zapisywane za pomoca hookow: menu_nodeapi, path_nodeapi
        $node->menu = $menu;
        $node->path = rtrim($values[$code]['path']['path'], '/');

        scholar_save_node($node, $row['id'], 'people');
    }
    variable_set('scholar_last_change', date('Y-m-d H:i:s'));

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
    $row = scholar_people_fetch_row($id, true);

    $form = array('#row' => $row);
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

    scholar_add_css();

    return $form;
} // }}}

function scholar_people_delete_form_submit($form, &$form_state) // {{{
{
    if ($row = $form['#row']) {
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

/**
 * Lista osób.
 *
 * @return string
 */
function scholar_people_list() // {{{
{
    $header = array(
        array('data' => t('Last name'), 'field' => 'last_name', 'sort' => 'asc'),
        array('data' => t('First name'), 'field' => 'first_name'),
        array('data' => t('Operations'), 'colspan' => '2')
    );

    $query = db_query("SELECT * FROM {scholar_people}" . tablesort_sql($header));
    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['last_name']),
            check_plain($row['first_name']),
            l(t('edit'),   "scholar/people/edit/{$row['id']}"), 
            l(t('delete'), "scholar/people/delete/{$row['id']}"),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4)
        );
    }

    $html = '';
    $html .= theme('table', $header, $rows);
    return $html;
} // }}}

