<?php

function scholar_new_generic() // {{{
{
    $record = new stdClass;
    $schema = drupal_get_schema('scholar_generics');

    if ($schema) {
        foreach ($schema['fields'] as $field => $info) {
            $record->$field = null;
        }
    }

    return $record;
} // }}}

/**
 * @return false|object
 */
function scholar_load_generic($id) // {{{
{
    $query = db_query("SELECT * FROM {scholar_generics} WHERE id = %d", $id);
    return db_fetch_object($query);
} // }}}

/**
 * @param object &$generic
 */
function scholar_save_generic(&$generic)
{
    foreach (get_object_vars($generic) as $key => $value) {
        if (is_string($value)) {
            $value = trim($value);
            $generic->$key = strlen($value) ? $value : null;
        }
    }

    if ($generic->id) {
        scholar_db_write_record('scholar_generics', $generic, 'id');
    } else {
        scholar_db_write_record('scholar_generics', $generic);
    }

    // TODO zapisz powiazane wezly, eventy, zalaczniki
    if ($generic->event) {
        // event: start_date, end_date, url, image_id, [language] => (title, body)
        foreach (scholar_languages() as $code => $name) {
            if (empty($generic->event[$code])) {
                continue;
            }

            // sprawdz czy istnieje binding miedzy generykiem a eventem
            $event = false;
            $query = db_query("SELECT * FROM {scholar_events} WHERE generic_id = %d AND language = '%s'", $generic->id, $code);

            if ($rel = db_fetch_array($query)) {
                $event = events_load_event($rel['event_id']);
            }

            if (empty($event)) {
                $event = events_new_event();
            }

            foreach ($generic->event[$code] as $key => $value) {
                $event->$key = $value;
            }

            $body = $event->body;
            $event->body = '[[ Autogen ]]';

            // zapisz event
            if (events_save_event($event)) {
                p($event);
                // zapisz powiazanie uprzednio usuwajac wczesniejsze powiazania
                db_query("DELETE FROM {scholar_events} WHERE (generic_id = %d AND language = '%s') OR (event_id = %d)", $generic->id, $code, $event->id);
                db_query("INSERT INTO {scholar_events} (generic_id, event_id, language, body) VALUES (%d, %d, '%s', '%s')",
                    $generic->id, $event->id, $code, $body);
            } else p('fail');
            p($event);
        }
    }
}

function scholar_generics_list($subtype) // {{{
{
    $func = 'scholar_' . $subtype . '_list';

    if ($func != __FUNCTION__ && function_exists($func)) {
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

    if ($func != __FUNCTION__ && function_exists($func)) {
        // pobierz argumenty, usun pierwszy, zastap subtype
        // referencja do form_state
        $args = func_get_args();
        array_shift($args);
        $args[0] = &$form_state;

        // pobierz strukture formularza
        $form = call_user_func_array($func, $args);

        // podepnij do niej funkcje obslugujace submit
        if (function_exists($func . '_submit')) {
            $form['#submit'][] = $func . '_submit';
        }

        return $form;
    }

    drupal_set_message("Unable to retrieve form: Invalid generic subtype '$subtype'", 'error');
} // }}}

function scholar_conference_list()
{
    global $pager_total;

    $header = array(
        array('data' => t('Date'), 'field' => 'start_date', 'sort' => 'desc'),
        array('data' => t('Title'), 'field' => 'title'),
        array('data' => t('Country'), 'field' => 'country_name'),
        array('data' => t('Operations'), 'colspan' => '2'),
    );

    $rpp = 25;
    $country_name = scholar_db_country_name('country', 'scholar_generics');
    $sql = "SELECT *, " . $country_name . " AS country_name FROM {scholar_generics} WHERE subtype = 'conference'" . tablesort_sql($header);

    $query = pager_query($sql, $rpp, 0, null);
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        // kraj musi byc stringiem, bo jezeli jest nullem scholar_countries
        // zwroci tablice wszystkich krajow
        $rows[] = array(
            substr($row['start_date'], 0, 10),
            check_plain($row['title']),
            check_plain($row['country_name']),
            l(t('edit'),   "scholar/conferences/edit/{$row['id']}"), 
            intval($row['refcount']) ? '' : l(t('delete'), "scholar/conferences/delete/{$row['id']}"),
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
    $form['url'] = array(
        '#type'      => 'textfield',
        '#title'     => t('URL'),
        '#description' => t('Adres URL strony ze szczegółowymi informacjami.'),
    );

    $form['event'] = scholar_events_form(false);

    $form['node'] = scholar_nodes_subform($record, 'generics');

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

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => t('Save changes'),
    );

    if ($record) {
        foreach (get_object_vars($record) as $key => $value) {
            if (isset($form[$key])) {
                $form[$key]['#default_value'] = $value;
            }
        }

        // obetnij czas z daty poczatku i konca
        $form['start_date']['#default_value'] = substr($record->start_date, 0, 10);
        $form['end_date']['#default_value']   = substr($record->end_date, 0, 10);
    }

    return $form;
}

function scholar_conference_form_submit($form, &$form_state)
{
    $record = empty($form['#record']) ? scholar_new_generic() : $form['#record'];
    $values = $form_state['values'];

    foreach (get_object_vars($record) as $field => $value) {
        if (isset($values[$field])) {
            $record->$field = $values[$field];
        }
    }

    // validate date
    $record->subtype = 'conference';
    p($values);
    p($record);

    $events = array();
    foreach ($values['event'] as $code => $name) {
        $title = trim($generic->event[$code]['title']);
        if (0 == strlen($title)) {
            $title = $record->title;
        }

        $events[$code] = array(
            'start_date' => $record->start_date,
            'end_date'   => $record->end_date,
            'title'      => $values['event'][$code]['title'],
            'body'       => $values['event'][$code]['body'],
            'url'        => $record->url,
            'language'   => $code,
            'image_id'   => $record->image_id,
        );
    }

    $record->event = $events;

    scholar_save_generic($record);
exit;
}

// vim: fdm=marker
