<?php

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
    global $language, $pager_total;

    $func = '_scholar_' . $subtype . '_list_spec';

    if (!function_exists($func)) {
        drupal_set_message("Unable to retrieve list: Invalid generic subtype '$subtype'", 'error');
        return;
    }

    // funkcja ma zwracac naglowek tabeli, jezeli nie podano wiersza
    $header = call_user_func($func);

    // sprawdz, czy potrzebna jest kolumna z nazwa kraju, jezeli tak,
    // dodaj ja do zapytania
    $cols = 'g.*, n.name AS category_name';

    foreach ($header as $col) {
        if (isset($col['field']) && 'country_name' == $col['field']) {
            $cols .= ', ' . scholar_db_country_name('g.country', 'scholar_generics')
                   . ' AS country_name';
            break;
        }
    }

    $where = array(
        'g.subtype'   => $subtype,
        '?n.language' => $language->language,
    );

    $rpp = scholar_admin_page_size();
    $sql = "SELECT $cols FROM {scholar_generics} g LEFT JOIN {scholar_category_names} n ON g.category_id = n.category_id WHERE " 
         . scholar_db_where($where)
         . tablesort_sql($header);

    $query = pager_query($sql, $rpp, 0, null);
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = call_user_func($func, $row);
    }

    if (empty($rows)) {
        $colspan = 0;

        foreach ($header as $col) {
            $colspan += isset($col['colspan']) ? max(1, $col['colspan']) : 1;
        }

        $rows[] = array(
            array('data' => t('No records'), 'colspan' => $colspan)
        );
    }

    $html = theme('table', $header, $rows);

    if ($pager_total > 1) {
        $html .= theme('pager', array(), $rpp);
    }

    return $html;
} // }}}

/**
 * Funkcja wywołująca formularz dla danego podtypu generycznego.
 *
 * @param array &$form_state
 * @param string $subtype
 * @return array
 */
function scholar_generics_form(&$form_state, $subtype, $id = null) // {{{
{
    $func = 'scholar_' . $subtype . '_form';

    if ($func != __FUNCTION__ && function_exists($func)) {
        if (null === $id) {
            $record = null;
        } else {
            $record = scholar_load_generic($id, $subtype, scholar_admin_path($subtype));
        }

        // przygotuj argumenty do wygenerowania formularza
        $args = array(&$form_state, &$record);

        // pobierz strukture formularza
        $form = call_user_func_array($func, $args);

        $form['#subtype'] = $subtype;
        $form['#submit']  = array('_scholar_generics_form_submit');

        return $form;
    }

    drupal_set_message("Unable to retrieve form: Invalid generic subtype '$subtype'", 'error');
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
    $process = '_scholar_' . $subtype . '_form_process_values';

    $values = $form_state['values'];

    // zamien na null wartosci parent_id i category_id jezeli sa
    // puste lub zerami
    foreach (array('parent_id', 'category_id') as $key) {
        if (isset($values[$key])) {
            $value = intval($values[$key]);
            $values[$key] = $value ? $value : null;
        }
    }

    $values['title'] = isset($values['title']) ? trim($values['title']) : '';

    // jezeli nie podano tytulu wezla, uzyj tytulu rekordu
    if (isset($values['nodes'])) {
        foreach ($values['nodes'] as $language => &$node) {
            $title = trim($node['title']);

            if (0 == strlen($title)) {
                $title = $values['title'];
            }

            $node['title'] = $title;
        }
        unset($node);
    }

    // to samo tyczy sie tytulu dla eventow
    if (isset($values['events'])) {
        foreach ($values['events'] as $language => &$event) {
            $title = trim($event['title']);

            if (0 == strlen($title)) {
                $title = $values['title'];
            }

            $event['title'] = $title;
        }
        unset($event);
    }
p($values);
    if (function_exists($process)) {
        $args = array(&$values);
        call_user_func_array($process, $args);
    }

    $record = empty($form['#record']) ? scholar_new_generic() : $form['#record'];

    // wypelnij rekord danymi z formularza
    scholar_populate_record($record, $values);

    // dla pewnosci ustaw odpowiedni podtyp
    $record->subtype = $subtype;

    scholar_save_generic($record);
    drupal_goto(scholar_admin_path($subtype));
} // }}}

function scholar_generics_delete_form(&$form_state, $subtype, $id) // {{{
{
    $record = scholar_load_generic($id, $subtype, scholar_admin_path($subtype));

    $form = array(
        '#record' => $record,
    );

    $form = confirm_form($form,
        t('Are you sure you want to delete %title?', array('%title' => $record->title)),
        scholar_admin_path($subtype),
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
        scholar_delete_generic($record);
        drupal_goto(scholar_admin_path($record->subtype));
    }
} // }}}


/**
 * @param array &$form_state
 * @param object &$record
 */
function scholar_conference_form(&$form_state, &$record = null) // {{{
{
    if ($record) {
        $record->start_date = substr($record->start_date, 0, 10);
        $record->end_date   = substr($record->end_date, 0, 10);
    }

    $form = scholar_generic_form(array(
        'title' => t('Conference name'),
        'start_date' => array(
            '#maxlength' => 10,
            '#description' => t('Date format: YYYY-MM-DD.'),
        ), 
        'end_date' => array(
            '#maxlength' => 10,
            '#description' => t('Date format: YYYY-MM-DD. Leave empty if it is the same as the start date.'),
        ),
        'locality',
        'country',
        'url', 
        'category_id',
        'files',
        'events',
        'nodes',
    ), $record);

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => $record ? t('Save changes') : t('Add conference'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_admin_path('presentation'),
    );

    return $form;
} // }}}

function _scholar_conference_form_process_values(&$values) // {{{
{
    // data poczatku i konca maja obcieta czesc zwiazana z czasem,
    // trzeba ja dodac aby byla poprawna wartoscia DATETIME
    $values['start_date'] .= ' 00:00:00';
    if (strlen($values['end_date'])) {
        $values['end_date'] .= ' 00:00:00';
    }

    // dodaj czas do eventow
    foreach ($values['events'] as $language => &$event) {
        $title = trim($event['title']);
        if (0 == strlen($title)) {
            $title = $values['title'];
        }
        $event['title']      = $title;
        $event['start_date'] = $values['start_date'];
        $event['end_date']   = $values['end_date'];
        $event['language']   = $language;
        $event['image_id']   = $values['image_id'];
    }
} // }}}

function scholar_presentation_form(&$form_state, &$record = null) // {{{
{
    $form = scholar_generic_form(array(
        'title',
        'start_date' => t('Data i czas'),
        'parent_id'  => t('Konferencja'),
        'authors' => t('Prowadzący'),
        'files',
        'nodes',
        'events' => array(
            // prezentacje odbywaja sie jednego dnia
            'end_date' => false,
        ),
    ), $record);

    $form['submit'] = array(
        '#type'  => 'submit',
        '#value' => $record ? t('Save changes') : t('Add presentation'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_admin_path('presentation'),
    );

    return $form;
} // }}}

function scholar_book_form(&$form_state, &$record = null) // {{{
{
    if ($record) {
        $record->start_date = intval($record->start_date);
    }

    // rendering: $authors: <a href="$url">$title</a> $details
    // <a href="http://syngasburner.eu/pl/publikacje/monografia-ekoenergetyka">"Eco-energetics - biogas and syngas"</a> (red. A. Cenian, J. Gołaszewski i T. Noch)
    // <a href="http://www.springer.com/physics/classical+continuum+physics/book/978-3-642-03084-0">"Advances in Turbulence XII, Proceedings of the Twelfth European Turbulence Conference, September 7–10, 2009, Marburg, Germany"</a>, Springer Proceedings in Physics, Vol. 132
    // jezeli pierwszym znakiem jest nawias otwierajacy <{[( dodaj details za " "
    // w przeciwnym razie dodaj ", "

    $categories = scholar_category_options('generics', 'book');

    $form = scholar_generic_form(array(
        'title' => array(
            '#required'    => true,
        ),
        'start_date' => array(
            '#title'       => t('Year'),
            '#maxlength'   => 4,
            '#description' => 'Pozostaw puste jeżeli jest to seria wydawnicza (czasopismo).',
        ),
        'category_id' => empty($categories) ? false : array(
            '#options'     => $categories,
        ),
        'authors' => array(
            '#title' => t('Authors'),
            '#description' => 'Wypełnij jeżeli książka. Informacje o redakcji umieść w polu \'szczegóły\'.',
        ),
        'details' => array(
            '#title' => 'Szczegóły wydawnicze',
            '#description' => 'Np. redaktorzy, seria wydawnicza, wydawca',
        ),
        'image_id',
        'url',
        'events' => array( // np. info o wydaniu ksiazki, bez daty koncowej
            'end_date'     => false,
        ),
        'nodes',  // dodatkowa wewnetrzna strona poswiecona ksiazce
        'files',  // pliki
    ), $record);

    array_unshift($form, array(
        '#type' => 'fieldset',
        '#title' => 'Pomoc',
        array(
            '#type' => 'markup',
            '#value' => 'To niekoniecznie musi być książka, może to też być czasopismo (jako seria wydawnicza, a nie pojedynczy numer).',
        ),
    ));

    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => $record ? t('Save changes') : t('Add book'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_admin_path('book'),
    );

    return $form;
} // }}}

function _scholar_book_form_process_values(&$values) // {{{
{
    $values['start_date'] = sprintf("%04d", $values['start_date']) . '-01-01 00:00:00';
    $values['end_date']   = null;
} // }}}

function scholar_article_form(&$form_state, &$record = null) // {{{
{
    if ($record) {
        // intval konczy na pierwszym niepoprawnym znaku, wiec dostaniemy
        // poprawna wartosc roku
        $record->start_date = intval($record->start_date);
    }

    // artykuly moga nalezec do ksiazki (wydawnictwa zwartego)
    $parents    = scholar_generic_parent_options('book');
    $categories = scholar_category_options('generics', 'article');

    $form = scholar_generic_form(array(
        'title' => array(
            '#required'    => true,
        ),
        'start_date' => array(
            '#title'       => t('Year'),
            '#maxlength'   => 4,
            '#required'    => true,
        ),
        'category_id' => empty($categories) ? false : array(
            '#options'     => $categories,
        ),
        'authors' => array(
            '#description' => 'Pamiętaj o ustawieniu odpowiedniej kolejności autorów.',
        ),
        'details' => array(
            '#title'       => 'Szczegóły bibliograficzne',
            '#description' => 'Np. nr tomu, strony',
        ),
        'parent_id' => empty($parents) ? false : array(
            '#options'     => $parents,
        ),
        'image_id',
        'url',
        'events' => array( // np. info o wydaniu ksiazki, bez daty koncowej
            'end_date'     => false,
        ),
        'nodes',  // dodatkowa wewnetrzna strona poswiecona wydawnictwu
        'files',  // pliki
    ), $record);

    $form['submit'] = array(
        '#type'  => 'submit',
        '#value' => $record ? t('Save changes') : t('Add article'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_admin_path('article'),
    );

    return $form;
} // }}}

function _scholar_article_form_process_values(&$values) // {{{
{
    // poniewaz jako date artykulu zapisuje sie tylko rok, trzeba
    // dodac do niego brakujace znaki, aby byl poprawna wartoscia DATETIME
    $values['start_date'] = sprintf("%04d", $values['start_date']) . '-01-01 00:00:00';
    $values['end_date']   = null;
} // }}}

/**
 * @return array
 */
function _scholar_article_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Year'),       'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Authors'),    'field' => 'bib_authors'),
            array('data' => t('Title'),      'field' => 'title'),
            array('data' => t('Category'),   'field' => 'category_name'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        intval($row['start_date']),
        str_replace(' et al.', ' <em>et al.</em>', check_plain($row['bib_authors'])),
        check_plain($row['title']),
        check_plain($row['category_name']),
        l(t('edit'),  scholar_admin_path('article/edit/' . $row['id'])),
        l(t('delete'), scholar_admin_path('article/delete/' . $row['id'])),
    );
} // }}}

function _scholar_book_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Year'),       'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Authors'),    'field' => 'bib_authors'),
            array('data' => t('Title'),      'field' => 'title'),
            array('data' => t('Category'),   'field' => 'category_name'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        intval($row['start_date']),
        str_replace(' et al.', ' <em>et al.</em>', check_plain($row['bib_authors'])),
        check_plain($row['title']),
        check_plain($row['category_name']),
        l(t('edit'),  scholar_admin_path('book/edit/' . $row['id'])),
        l(t('delete'), scholar_admin_path('book/delete/' . $row['id'])),
    );
} // }}}

function _scholar_conference_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Date'), 'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'), 'field' => 'title'),
            array('data' => t('Country'), 'field' => 'country_name'),
            array('data' => t('Category'),   'field' => 'category_name'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        substr($row['start_date'], 0, 10),
        check_plain($row['title']),
        check_plain($row['country_name']),
        check_plain($row['category_name']),
        l(t('edit'),  scholar_admin_path('conference/edit/' . $row['id'])),
        intval($row['refcount']) ? '' : l(t('delete'), scholar_admin_path('conference/delete/' . $row['id'])),
    );
} // }}}

function _scholar_presentation_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Date'), 'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'), 'field' => 'title'),
            array('data' => t('Country'), 'field' => 'country_name'),
            array('data' => t('Category'),   'field' => 'category_name'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        substr($row['start_date'], 0, 10),
        check_plain($row['title']),
        check_plain($row['country_name']),
        check_plain($row['category_name']),
        l(t('edit'),  scholar_admin_path('presentation/edit/' . $row['id'])),
        intval($row['refcount']) ? '' : l(t('delete'), scholar_admin_path('presentation/delete/' . $row['id'])),
    );
} // }}}

// vim: fdm=marker
