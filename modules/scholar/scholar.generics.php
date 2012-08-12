<?php

function scholar_new_generic()
{
    return new stdClass;
}

/**
 * @return false|object
 */
function scholar_load_generic($id)
{
    $query = db_query("SELECT * FROM {scholar_generics} WHERE id = %d", $id);
    return db_fetch_object($query);
}

/**
 * @param object &$generic
 */
function scholar_save_generic(&$generic)
{
    if ($generic->id) {
        drupal_write_record('scholar_generics', $generic, 'id');
    } else {
        drupal_write_record('scholar_generics', $generic);
    }

    // TODO zapisz powiazane wezly, eventy, zalaczniki
}

function scholar_generics_list($subtype) // {{{
{
    $func = 'scholar_' . $subtype . '_list';

    if (function_exists($func)) {
        return call_user_func($func);
    }

    drupal_set_message("Unable to retrieve list: Invalid generic subtype '$subtype'", 'error');
} // }}}

/**
 * Funkcja wywołująca formularz dla danego podtypu generycznego.
 * @param array &$form_state
 * @param string $subtype
 */
function scholar_generics_form(&$form_state, $subtype) // {{{
{
    $func = 'scholar_' . $subtype . '_form';

    if (function_exists($func)) {
        // pobierz argumenty, usun pierwszy, zastap subtype
        // referencja do form_state
        $args = func_get_args();
        array_shift($args);
        $args[0] = &$form_state;

        return call_user_func_array($func, $args);
    }

    drupal_set_message("Unable to retrieve form: Invalid generic subtype '$subtype'", 'error');
} // }}}

function scholar_conference_list()
{
    global $pager_total;

    $header = array(
        array('data' => t('Date'), 'field' => 'start_date', 'sort' => 'desc'),
        array('data' => t('Title'), 'field' => 'title'),
        array('data' => t('Country'), 'field' => 'country'),
        array('data' => t('Operations'), 'colspan' => '2'),
    );

    $rpp = 25;
    $sql = "SELECT * FROM {scholar_generics} WHERE subtype = 'conference'" . tablesort_sql($header);

    $query = pager_query($sql, $rpp, 0, null);
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            substr($row['start_date'], 0, 10),
            check_plain($row['title']),
            check_plain($row['country']),
            'edit',
            'delete',
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records'), 'colspan' => 5)
        );
    }

    $html = theme('table', $header, $rows);

    if ($pager_total > 1) {
        $html .= theme('pager', array(), $rpp);
    }

    return $html;
}

function scholar_conference_form(&$form_state, $id = null)
{
    $record = scholar_load_generic($id);

    $form['#record'] = $record;

    $form['title'] = array(
        '#type'      => 'textfield',
        '#title'     => t('Title'),
        '#required'  => true,
        '#description' => t('Nazwa konferencji.'),
    );
    $form['start_date'] = array(
        '#type'      => 'textfield',
        '#maxlength' => 10,
        '#required'  => true,
        '#title'     => t('Start date'),
        '#description' => t('Date format: YYYY-MM-DD.'),
    );
    $form['end_date'] = array(
        '#type'      => 'textfield',
        '#maxlength' => 10,
        '#title'     => t('End date'),
        '#description' => t('Date format: YYYY-MM-DD. Leave empty if it is the same as the start date.'),
    );

    $form['locality'] = array(
        '#type'      => 'textfield',
        '#required'  => true,
        '#title'     => t('Locality'),
        '#description' => t('Nazwa miejscowości, gdzie konferencja będzie mieć miejsce.'),
    );
    $form['country'] = array(
        '#type'      => 'scholar_country',
        '#required'  => true,
        '#title'     => t('Country'),
    );
    $form['category'] = array(
        '#type'      => 'textfield',
        '#required'  => true,
        '#title'     => t('Category'),
        '#description' => t('Uszczegółowienie typu konferencji.'),
    );

    $form['attachments'] = array(
        '#type' => 'fieldset',
        '#title' => t('File attachments'),
    );
    $form['attachments']['files'] = array(
        '#type' => 'scholar_attachment_manager',
        '#default_value' => $row
                            ? scholar_fetch_attachments($record->id, 'generics')
                            : null
    );

    $form['node'] = scholar_nodes_subform($record, 'generics');

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => t('Save changes'),
    );

    return $form;
}

function scholar_conference_form_submit($form, &$form_state)
{
    $record = empty($form['#record']) ? scholar_new_generic() : $form['#record'];
p($record); exit;
    scholar_save_generic($record);
}

// vim: fdm=marker
