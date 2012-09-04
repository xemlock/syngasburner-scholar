<?php

/**
 * Strona wyświetlająca listę kategorii.
 *
 * @param string $table_name
 * @param string $subtype OPTIONAL
 */
function scholar_category_list($table_name, $subtype = null) // {{{
{
    global $language;

    $header = array(
        array('data' => t('Name'), 'field' => 'n.name', 'sort' => 'asc'),
        array('data' => t('Size'), 'title' => t('Number of category members')),
        array('data' => t('Operations'), 'colspan' => 2),
    );

    $conds = array(
        'table_name' => $table_name,
    );
    if ($subtype) {
        $conds['subtype'] = $subtype;
    }

    $query = scholar_categories_recordset($conds, $header);
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['name']),
            intval($row['refcount']),
            scholar_oplink(t('edit'), "categories.{$table_name}.{$subtype}", 'edit/%d', $row['id']),
            scholar_oplink(t('delete'), "categories.{$table_name}.{$subtype}", 'delete/%d', $row['id']),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4),
        );
    }

    return scholar_theme_table($header, $rows);
} // }}}

/**
 * Strona z formularzem edycji kategorii. Jeżeli istnieje funkcja o nazwie
 * scholar_category_form_{table_name}_{subtype} zostanie ona wywolana do
 * zmodyfikowania struktury formularza. Gdy nie podano podtypu lub jest on
 * pusty nazwa funkcji nie zawiera przyrostka _{subtype}.
 *
 * @param array &$form_state
 * @param string $table_name
 * @param string $subtype OPTIONAL
 * @param int $id OPTIONAL
 */
function scholar_category_form(&$form_state, $table_name, $subtype = null, $id = null) // {{{
{
    if (null === $id) {
        // pusty rekord, musi miec ustawione pola table_name i subtype,
        // bo beda one niezbedne podczas zapisu do bazy danych
        $record = new stdClass;
        $record->table_name = $table_name;
        $record->subtype = $subtype;

    } else {
        $conds = array(
            'id'         => $id,
            'table_name' => $table_name,
            'subtype'    => $subtype,
        );
        $record = scholar_load_record('categories', $conds, scholar_path("categories.{$table_name}.{$subtype}"));
    }

    $names = array(
        '#tree' => true,
    );

    foreach (scholar_languages() as $code => $name) {
        $names[$code] = array(
            '#type'          => 'textfield',
            '#title'         => scholar_language_label($code, t('Name')),
            '#description'   => t('Category name in language: @language', array('@language' => $name)),
            '#required'      => true,
            '#default_value' => $record ? $record->names[$code] : null,
        );
    }

    $fields = array(
        // 'names' => $names,
        'names' => array(
            '#type'  => 'scholar_element_langtext',
            '#title'       => t('Category name'),
            '#description' => t('Enter category names for each language.'),
            '#required'    => true,
            '#maxlength'   => 128,
        ),
        // 'files',
        // 'nodes',
    );

    // dodaj dodatkowe pola zwiazane z kategoriami rekordow konkretnych typow
    $callback = 'scholar_category_form_' . $table_name;
    if ($subtype) {
        $callback .= '_' . $subtype;
    }

    if (function_exists($callback)) {
        call_user_func_array($callback, array(&$fields));
    }

    $form = scholar_generic_form($fields, $record);

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => empty($record->id) ? t('Save') : t('Save changes'),
    );
    $form['cancel'] = array(
        '#type' => 'scholar_element_cancel',
        '#value' => scholar_path("categories.{$table_name}.{$subtype}"),
    );

    return $form;
} // }}}

/**
 * Tworzy lub aktualizuje rekord kategorii na podstawie danych
 * przesłanych w formularzu.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_category_form_submit($form, &$form_state) // {{{
{
    $record = $form['#record'];

    if ($record) {
        $is_new = empty($record->id);
        $values = $form_state['values'];

        scholar_populate_record($record, $values);

        if (isset($record->nodes)) {
            // jezeli nie podano tytulow wezla (segmentu) uzyj nazwy kategorii
            foreach ($record->nodes as $language => &$node) {
                $title = trim($node['title']);
                if (0 == strlen($title)) {
                    $title = strval($record->names[$language]);
                }
                $node['title'] = $title;
            }
            unset($node);
        }

        // zapisz kategorie
        scholar_save_record('categories', $record);

        drupal_set_message($is_new
            ? t('Category %title added successfully.', array('%title' => $title))
            : t('Category %title updated successfully.', array('%title' => $title))
        );
        drupal_goto(scholar_path("categories.{$record->table_name}.{$record->subtype}"));
    }
} // }}}

/**
 * Strona z formularzem potwierdzającym usunięcie rekordu kategorii.
 *
 * @param array &$form_state
 * @param int $id
 */
function scholar_category_delete_form(&$form_state, $id) // {{{
{
    global $language;

    $record = scholar_load_record('categories', $id, scholar_path());

    $form = array(
        '#record' => $record,
    );

    $form = confirm_form($form,
        t('Are you sure you want to delete category (%name)?', array('%name' => $record->names[$language->language])),
        scholar_path("categories.{$record->table_name}.{$record->subtype}"),
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    return $form;
} // }}}

/**
 * Usuwa rekord kategorii.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_category_delete_form_submit($form, &$form_state) // {{{
{
    global $language;

    $record = $form['#record'];

    if ($record) {
        scholar_delete_record('categories', $record);
        drupal_set_message(t('Category %name deleted successfully', array('%name' => $record->names[$language->language])));
        drupal_goto(scholar_path("categories.{$record->table_name}.{$record->subtype}"));
    }
} // }}}

function scholar_category_form_generics_conference(&$fields) // {{{
{
    // dodaj pola formularza odpowiedzialne za konfiguracje
    // powiazanych wezlow
    $fields[] = 'nodes';
} // }}}

// vim: fdm=marker
