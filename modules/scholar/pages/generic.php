<?php

/**
 * Ładuje plik z funkcjami właściwymi dla stron danego podtypu rekordów
 * generycznych.
 *
 * @param string $subtype
 */
function _scholar_generics_include($subtype) // {{{
{
    $file = dirname(__FILE__) . '/generic/'
          . preg_replace('/[^._0-9a-z]/i', '', $subtype) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
} // }}}

/**
 * Funkcja generująca listę rekordów danego podtypu. Warunkiem jej działania
 * jest istnienie funkcji o nazwie _scholar_{subtype}_list_spec, która jeżeli
 * nie dostanie żadnego argumentu zwróci tablicę definiującą nagłówek tabeli, 
 * a gdy dostanie jako argument tablicę, na jej podstawie zwróci tablicę
 * wartości do umieszczenia w wierszu tabeli.
 *
 * @param string $subtype
 * @return string
 */
function scholar_generics_list($subtype) // {{{
{
    global $pager_total;

    _scholar_generics_include($subtype);

    $func = '_scholar_generics_' . $subtype . '_list_spec';

    if (!function_exists($func)) {
        drupal_set_message("Unable to retrieve list: Invalid subtype '$subtype'", 'error');
        return '';
    }

    // funkcja ma zwracac naglowek tabeli, jezeli nie podano wiersza
    $header = call_user_func($func);

    // bierzemy pod uwage tylko rekordy o podanym podtypie
    $conds  = array('subtype' => $subtype);

    // sortujemy w pierwszej kolejnosci po nazwie kategorii
    $before = 'category_name';

    // specyfikacja paginatora
    $limit = scholar_admin_page_size();
    $pager = array('limit' => $limit, 'element' => 0);

    // pobierz rekordy
    $query = scholar_generics_recordset($conds, $header, $before, $pager);
    $rows  = array();

    // liczba kolumn w tabeli
    $colspan = 0;
    foreach ($header as $col) {
        $colspan += isset($col['colspan']) ? max(1, $col['colspan']) : 1;
    }

    $last_category_name = '';

    $q = array('query' => 'destination=' . $_GET['q']);

    while ($row = db_fetch_array($query)) {
        $category_name = trim($row['category_name']);

        // dodaj naglowek z nazwa kategorii i linkiem edycji kategorii
        if ($last_category_name != $category_name) {
            $last_category_name = $category_name;
            // dodaj naglowek z nazwa kategorii, poniewaz do tego IFa wejdziemy
            // tylko wtedy, kiedy nazwa kategorii jest niepusta, mamy pewnosc,
            // ze catgegory_id bedzie mialo poprawna wartosc
            $edit_link = ' <span class="region-link">'
                . scholar_oplink(t('edit'), "categories.generics.{$row['subtype']}", 'edit/%d', $row['category_id'])
                . '</span>';

            $rows[] = array(
                'data' => array(
                    array(
                        'data' => check_plain($category_name) . $edit,
                        'colspan' => $colspan - 1,
                        'class' => 'region',
                    ),
                    array(
                        'data' => $edit_link,
                        'class' => 'region',
                    ),
                ),
                'class' => 'region',
            );
        }

        $rows[] = call_user_func($func, $row);
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records'), 'colspan' => $colspan),
        );
    }

    $html = scholar_theme_table($header, $rows);

    if ($pager_total > 1) {
        $html .= theme('pager', array(), $limit);
    }

    return $html;
} // }}}

/**
 * Funkcja wywołująca formularz dla danego podtypu generycznego.
 *
 * @param string $subtype
 * @param int $id
 * @return array
 */
function scholar_generics_form(&$form_state, $subtype, $id = null) // {{{
{
    _scholar_generics_include($subtype);

    $func = 'scholar_generics_' . $subtype . '_form';

    if ($func != __FUNCTION__ && function_exists($func)) {
        if (null === $id) {
            $record = null;
        } else {
            $conds  = array('id' => $id, 'subtype' => $subtype);
            $record = scholar_load_record('generics', $conds, scholar_path("generics.$subtype"));
        }

        // przygotuj argumenty do wygenerowania formularza
        $args = array(&$form_state, $record);

        // pobierz strukture formularza
        $form = call_user_func_array($func, $args);

        if (function_exists($func . '_validate')) {
            $form['#validate'][] = $func . '_validate';
        }

        $form['#subtype'] = $subtype;
        $form['#submit']  = array('_scholar_generics_form_submit');

        return $form;
    }

    drupal_set_message("Unable to retrieve form: Invalid subtype '$subtype'", 'error');
    return '';
} // }}}

/**
 * Jeżeli isnieje funkcja _scholar_podtyp_form_process_values
 * zostanie ona uruchomiona (jako arguyment dostanie referencję
 * do tablicy z wartościami pól formularza.
 */
function _scholar_generics_form_submit($form, &$form_state) // {{{
{
    // jezeli istnieje funkcja do zmodyfikowania danych formularza
    // przed zapisem, uruchom ja
    $subtype = $form['#subtype'];
    $process = '_scholar_generics_' . $subtype . '_form_process_values';

    $values = $form_state['values'];

    // zamien na null wartosci parent_id i category_id jezeli sa
    // puste lub zerami
    foreach (array('parent_id', 'category_id') as $key) {
        if (isset($values[$key])) {
            $value = intval($values[$key]);
            $values[$key] = $value ? $value : null;
        }
    }

    $image_id = isset($values['image_id']) ? intval($values['image_id']) : 0;
    $values['image_id'] = $image_id ? $image_id : null;

    $title = isset($values['title']) ? trim($values['title']) : '';
    $values['title'] = $title;

    // jezeli nie podano tytulu wezla, uzyj tytulu rekordu
    if (isset($values['nodes'])) {
        foreach ($values['nodes'] as $language => &$node) {
            $node_title = trim($node['title']);

            if (0 == strlen($node_title)) {
                $node_title = $title;
            }

            $node['title'] = $node_title;
        }
        unset($node);
    }

    // to samo tyczy sie tytulu dla eventow, ponadto skopiuj obraz do eventu
    if (isset($values['events'])) {
        foreach ($values['events'] as $language => &$event) {
            $event_title = trim($event['title']);

            if (0 == strlen($event_title)) {
                $event_title = $title;
            }

            $event['title']    = $event_title;
            $event['image_id'] = $image_id;
        }
        unset($event);
    }

    if (function_exists($process)) {
        $args = array(&$values);
        call_user_func_array($process, $args);
    }

    $is_new = empty($form['#record']);
    $record = $is_empty ? new stdClass : $form['#record'];

    // wypelnij rekord danymi z formularza
    scholar_populate_record($record, $values);

    // dla pewnosci ustaw odpowiedni podtyp
    $record->subtype = $subtype;

    if (scholar_save_record('generics', $record)) {
        if (empty($record->title)) {
            drupal_set_message($is_new
                ? t('Entry created successfully.')
                : t('Entry updated successfully.')
            );
        } else {
            drupal_set_message($is_new
                ? t('%title created successfully.', array('%title' => $record->title))
                : t('%title updated successfully.', array('%title' => $record->title))
            );
        }

        // tu zadziala destination
        drupal_goto(scholar_path("generics.$subtype"));
    }
} // }}}

function scholar_generics_delete_form(&$form_state, $subtype, $id) // {{{
{
    $conds  = array('id' => $id, 'subtype' => $subtype);
    $record = scholar_load_record('generics', $conds, scholar_path("generics.$subtype"));

    $form = array(
        '#record' => $record,
    );

    $cancel = isset($_GET['destination']) ? $_GET['destination'] : scholar_path("generics.$subtype");

    $form = confirm_form($form,
        t('Are you sure you want to delete %title?', array('%title' => $record->title)),
        $cancel,
        t('All related node and event records will be removed. This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    return $form;
} // }}}

function scholar_generics_delete_form_submit($form, &$form_state) // {{{
{
    $record = $form['#record'];

    if ($record) {
        scholar_delete_record('generics', $record);
        drupal_set_message(t('%title deleted successfully.', array('%title' => $record->title)));
        drupal_goto(scholar_path("generics.{$record->subtype}"));
    }
} // }}}

/**
 * Lista rekordów potomnych podpiętych do rekordu o podanym identyfikatorze.
 *
 * @param string $subtype
 * @param int $id
 * @param string $children_subtype
 */
function scholar_generics_children_form(&$form_state, $subtype, $id, $children_subtype) // {{{
{
    _scholar_generics_include($subtype);
    _scholar_generics_include($children_subtype);

    $conds  = array('id' => $id, 'subtype' => $subtype);
    $record = scholar_load_record('generics', $conds, scholar_path("generics.$subtype"));

    $func = 'scholar_generics_' . $subtype . '_children_' . $children_subtype . '_form';

    if (function_exists($func)) {
        return $func($form_state, $record);
    } else {
        

    }

    drupal_set_message("Unable to retrieve children list: Invalid parent-children subtype specification: '$subtype' and '$children_subtype'", 'error');
    return '';
} // }}}

function scholar_generics_children_form_submit($form, &$form_state)
{
    if ($form['#record']) {
        $record = $form['#record'];
        $values = $form_state['values'];

        if (scholar_generic_update_children_weights($record->id, (array) $values['weight'])) {
            drupal_set_message(t('Children order updated successfully.'));
        }

        drupal_goto(scholar_path("generics.{$record->subtype}"));
    }
}


// vim: fdm=marker
