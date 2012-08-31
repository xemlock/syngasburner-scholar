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
            $cols .= ', CASE LOWER(locality) WHEN \'internet\' THEN NULL ELSE '
                   . scholar_db_country_name('g.country', 'scholar_generics')
                   . ' END AS country_name';
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
            $conds  = array('id' => $id, 'subtype' => $subtype);
            $record = scholar_load_record('generics', $conds, scholar_admin_path($subtype));
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
        drupal_goto(scholar_admin_path($subtype));
    }
} // }}}

function scholar_generics_delete_form(&$form_state, $subtype, $id) // {{{
{
    $conds  = array('id' => $id, 'subtype' => $subtype);
    $record = scholar_load_record('generics', $conds, scholar_admin_path($subtype));

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
        scholar_delete_record('generics', $record);
        drupal_set_message(t('%title deleted successfully.', array('%title' => $record->title)));
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

    $categories = scholar_category_options('generics', 'conference');

    $form = scholar_generic_form(array(
        '#id' => 'scholar-conference-form',
        'title' => array(
            '#title' => t('Conference name'),
            '#required' => true
        ),
        'start_date' => array(
            '#maxlength' => 10,
            '#required' => true,
            '#description' => t('Date format: YYYY-MM-DD.'),
        ), 
        'end_date' => array(
            '#maxlength' => 10,
            '#required' => true,
            '#description' => t('Date format: YYYY-MM-DD. Leave empty if it is the same as the start date.'),
        ),
        'locality' => array(
            '#required' => true,
            '#description' => t('In case of virtual conferences enter "internet" (without quotes, case-insensitive) to ignore country.'),
        ),
        'country',
        'suppinfo' => array(
            '#description' => t('Additional details about this conference.'),
        ),
        scholar_element_separator(),
        'category_id' => empty($categories) ? false : array(
            '#options' => $categories,
        ),
        'list' => array(
            '#type' => 'checkbox',
            // Uwzględnij prezentacje z tej konferencji przy automatycznym tworzeniu list
            '#title' => t('Include presentations from this conference in auto-generated lists'),
            // Ustawienie to dotyczy stron osób oraz strony z wystąpieniami na konferencjach.
            '#description' => t('This setting applies to person pages and conference presentations page.'),
            '#default_value' => true,
        ),
        scholar_element_separator(),
        'image_id',
        'url',
        'files',
        'nodes',
        'events' => array(
            // dane poczatku i konca wydarzenia beda pobierane z danych konferencji
            'start_date' => false,
            'end_date'   => false,
        ),
    ), $record);

    // dodaj wylaczanie pola country jezeli w miejsce miejscowosci podano 'internet'
    drupal_add_js("$(function(){var f=$('#scholar-conference-form'),l=f.find('input[name=\"locality\"]'),c=f.find('select[name=\"country\"]'),d=function(){c[$.trim(l.val())=='internet'?'attr':'removeAttr']('disabled',true)};l.keyup(d);d()})", 'inline');

    $form['vtable']['presentations'] = array(
        '#type' => 'scholar_element_vtable_row',
        '#title' => t('Presentations'),
        '#description' => t('Change the order of presentations'),
    );


    $form['submit'] = array(
        '#type'     => 'submit',
        '#value'    => empty($record) ? t('Save') : t('Save changes'),
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
    // prezentacje moga nalezec do konferencji
    $parents    = scholar_generic_parent_options('conference');
    $categories = scholar_category_options('generics', 'presentation');

    // pusty tytul oznacza uczestnictwo w konferencji bez zadnego
    // wystapienia publicznego. Jezeli brak zdefiniowanych konferencji
    // ustaw pole tytulu jako wymagane.
    $form = scholar_generic_form(array(
        'title' => empty($parents) ? array('#required' => true) : array(
            '#description' => t('Leave empty to mark conference attendance if no public presentation was given. In this case, a conference must be chosen.'),
        ),
        'authors' => array(
            '#title'       => t('Authors'),
            '#required'    => true,
            '#description' => t('Remember about correct order, if there is more than one author or contributor.'),
        ),
        'parent_id' => empty($parents) ? false : array(
            '#title'       => t('Conference'),
            '#options'     => $parents,
            '#description' => t('A conference during which this presentation was given.'),
            // jezeli w adresie strony podano identyfikator konferencji
            // ustaw ja jako domyslna wartosc pola
            '#default_value' => isset($_GET['conference']) ? intval($_GET['conference']) : null,
        ),
        'start_date',
        'category_id' => empty($categories) ? false : array(
            '#options'     => $categories,
            '#description' => t('Specify presentation type, e.g. speech, poster, etc.'),
        ),
        'suppinfo' => array(
            '#description' => t('Additional details about this presentation.'),
        ),
        'files',
        'nodes',
        'events' => array(
            // prezentacje odbywaja sie jednego dnia
            'start_date' => array(
                '#title' => t('Date'),
            ),
            'end_date'   => false,
        ),
    ), $record);

    $form['#validate'][] = 'scholar_presentation_form_validate';

    $form['submit'] = array(
        '#type'  => 'submit',
        '#value' => empty($record) ? t('Save') : t('Save changes'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_admin_path('presentation'),
    );

    return $form;
} // }}}

function scholar_presentation_form_validate($form, &$form_state) // {{{
{
    $values = $form_state['values'];

    $empty_title  = empty($values['title']) || ctype_space((string) $values['title']);
    $empty_parent = empty($values['parent_id']) || 0 == intval($values['parent_id']);

    if ($empty_title && $empty_parent) {
        form_set_error('title', t('Presentation title is required if no conference is chosen.'));
    }
} // }}}

function _scholar_presentation_form_process_values(&$values) // {{{
{
    // jezeli pusty tytul, czyli obecnosc na konferencji bez wystapienia
    // publicznego, usun kategorie
    $values['start_date'] = substr($values['start_date'], 0, 10);
    $values['end_date'] = null;

    $values['title'] = trim($values['title']);

    if (empty($values['title'])) {
        $values['category_id'] = null;
    }
} // }}}

function scholar_book_form(&$form_state, &$record = null) // {{{
{
    if ($record) {
        $record->start_date = strlen($record->start_date) 
            ? intval(substr($record->start_date, 0, 4))
            : '';
    }

    $categories = scholar_category_options('generics', 'book');

    $form = scholar_generic_form(array(
        'title' => array(
            '#required'    => true,
        ),
        'start_date' => array(
            '#title'       => t('Year'),
            '#maxlength'   => 4,
            '#description' => 'Pozostaw puste jeżeli jest to seria wydawnicza lub czasopismo.',
        ),
        'bib_details' => array(
            '#description' => t('Information about editors, series, publisher etc.'),
        ),
        'authors' => array(
            '#title' => t('Authors'),
            '#description' => 'Wypełnij jeżeli książka. Informacje o redakcji umieść w polu \'szczegóły\'.',
        ),
        scholar_element_separator(),
        'category_id' => empty($categories) ? false : array(
            '#options'     => $categories,
        ),
        scholar_element_separator(),
        'image_id',
        'url',
        'files',  // pliki
        'nodes',  // dodatkowa wewnetrzna strona poswiecona ksiazce
        'events' => array( // np. info o wydaniu ksiazki, bez daty koncowej
            'end_date'     => false,
        ),
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
    $start_date = trim($values['start_date']);

    if (strlen($start_date)) {
        $start_date = sprintf("%04d", $values['start_date']) . '-01-01 00:00:00';
    } else {
        $start_date = null;
    }

    $values['start_date'] = $start_date;
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
    $parents = scholar_generic_parent_options('book');

    $form = scholar_generic_form(array(
        'title' => array(
            '#required'    => true,
        ),
        'start_date' => array(
            '#title'       => t('Year'),
            '#description' => t('Date of publication'),
            '#maxlength'   => 4,
            '#required'    => true,
        ),
        'authors' => array(
            '#description' => t('Remember about correct order, if there is more than one author or contributor.'),
        ),
        'bib_details' => array(
            '#description' => t('e.g. volume and issue number, page numbers etc.'), // np. numery tomu i wydania, numery stron
        ),
        'parent_id' => empty($parents) ? false : array(
            '#options'     => $parents,
        ),
        'url',
        'files',
        'events' => array( // np. info o wydaniu ksiazki, bez daty koncowej
            'end_date'     => false,
        ),
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
    // nic poza dopelnieniem wartosci start_date z roku do pelnego
    // typu DATETIME i wykasowanie wartosci end_date
    _scholar_book_form_process_values($values);
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
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        intval($row['start_date']),
        str_replace(' et al.', ' <em>et al.</em>', check_plain($row['bib_authors'])),
        check_plain($row['title']),
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

    if (strlen($row['start_date'])) {
        $year = intval(substr($row['start_date'], 0, 4));
    } else {
        $year = '';
    }

    return array(
        $year,
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
            array('data' => t('Date'),     'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Title'),    'field' => 'title'),
            array('data' => t('Country'),  'field' => 'country_name'),
            array('data' => t('Category'), 'field' => 'category_name'),
            array('data' => t('Listed')),
            array('data' => t('Operations'), 'colspan' => '3'),
        );
    }

    return array(
        substr($row['start_date'], 0, 10),
        check_plain($row['title']),
        check_plain($row['country_name']),
        check_plain($row['category_name']),
        $row['list'] ? t('Yes') : t('No'),
        l(t('edit'),  scholar_admin_path('conference/edit/' . $row['id'])),
        $row['child_count'] ? l(t('presentations (!count)', array('!count' => $row['child_count'])),  scholar_admin_path('conference/presentations/' . $row['id'])) : '',
        l(t('delete'), scholar_admin_path('conference/delete/' . $row['id'])),
    );
} // }}}

function _scholar_presentation_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Authors'),  'field' => 'bib_authors'),
            array('data' => t('Title'),    'field' => 'title'),
            array('data' => t('Category'), 'field' => 'category_name'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    $title = trim($row['title']);

    return array(
        check_plain($row['bib_authors']),
        empty($title) ? '<em>' . t('attendance only') . '</em>' : check_plain($title),
        check_plain($row['category_name']),
        l(t('edit'),  scholar_admin_path('presentation/edit/' . $row['id'])),
        l(t('delete'), scholar_admin_path('presentation/delete/' . $row['id'])),
    );
} // }}}

/**
 * Strona z listą wszystkich prezentacji podpiętych do danej
 * konferencji. Daje możliwość sortowania prezentacji.
 */
function scholar_conference_presentations_form(&$form_state, $id)
{
    $conference = scholar_load_record('generics', array('id' => $id, 'subtype' => 'conference'), scholar_admin_path('conference'));

    $query = db_query("SELECT * FROM {scholar_generics} WHERE parent_id = %d AND subtype = 'presentation' ORDER BY start_date, weight", $conference->id);

    $form = array(
        'weight' => array(
            '#tree' => true,
        ),
    );

    $weight_options = array();
    $delta = 10;
    for ($i = -$delta; $i <= $delta; ++$i) {
        $weight_options[$i] = $i;
    }

    $subgroups = array();
    $d = array('query' => 'destination=' . scholar_admin_path('conference/presentations/' . $conference->id));

    $tbody[] = array();
    $last_region = ''; // pierwszy region to ten bez daty
    while ($row = db_fetch_array($query)) {
        $form['weight'][$row['id']] = array(
            '#type' => 'hidden',
            '#default_value' => $row['weight'],
        );

        $subgroup = str_replace('-', '', substr($row['start_date'], 0, 10));

        if ($subgroup !== $last_region) {
            $rows[] = array(
                'data' => array(
                    array(
                        'data' => $subgroup,
                        'colspan' => 5,
                        'class' => 'region',
                    ),
                ),
                'class' => 'region',
            );
            $last_region = $subgroup;
        }

        if (strlen($subgroup)) {
            $subgroup = 'scholar-tbody-' . $subgroup;
        } else {
            $subgroup = 'scholar-tbody';
        }

        $subgroups[$subgroup] = true;

        $element = array(
            '#type' => 'select',
            '#attributes' => array('class' => 'tr-weight'),
            '#options' => $weight_options,
            '#parents' => array('weight', $row['id']),
            '#value' => $row['weight'],
            '#name' => 'weight[' . $row['id'] . ']',
            '#id' => 'weight-' . $row['id'],
        );

        $element['#type'] = 'hidden';
        
        $rows[] = array(
            'data' => array(
                check_plain($row['bib_authors']),
                check_plain($row['title']),
                theme_select($element),
                l(t('edit'),  scholar_admin_path('presentation/edit/' . $row['id']), $d),
                l(t('delete'), scholar_admin_path('presentation/delete/' . $row['id']), $d),
            ),
            'class' => 'draggable',
        );
    }

    $header = array(
        t('Authors'),
        t('Title'),
        t('Weight'),
        array('data' => t('Operations'), 'colspan' => 2),
    );

    // tabledrag totalnie nie dziala gdy jest wiecej niz jedno tbody
    drupal_add_tabledrag('scholar-conference-presentations', 'order', 'sibling', 'tr-weight');

    $form['#record'] = $conference;
    $form['properties'] = array(
        '#type' => 'fieldset',
        '#title' => t('Conference properties'),
        '#attributes' => array('class' => 'scholar'),
        '#collapsible' => true,
        '#collapsed' => true,
    );

    $location = trim($conference->locality);
    if (strcasecmp($location, 'internet')) {
        $country = scholar_countries($conference->country);
        if ($country) {
            $location .= ' (' . $country . ')';
        }
        $location = check_plain($location);
    } else {
        $location = '<em>internet</em>';
    }

    $form['properties'][] = array(
        '#type' => 'markup',
        '#value' => '<dl class="scholar">
<dt>' . t('Title') . '</dt><dd>' . check_plain($conference->title) . '</dd>
<dt>' . t('Start date') . '</dt><dd>' . scholar_format_date($conference->start_date) . '</dd>
<dt>' . t('End date') . '</dt><dd>' . scholar_format_date($conference->end_date) . '</dd>
<dt>' . t('Location') . '</dt><dd>' . $location . '</dd>
</dl>',
    );
    $form[] = array(
        '#type' => 'markup',
        '#value' => 
            '<div class="help">' . t('Here you can change the order of presentations in this conference. You can move presentations by dragging-and-dropping them to a new location.') . '</div>' .
            theme('table', $header, $rows, array('id' => 'scholar-conference-presentations', 'class' => 'region-locked')),
    );

    $form[] = array(
        '#type' => 'submit',
        '#value' => t('Save changes'),
    );

    scholar_add_tab(t('Add presentation'), scholar_admin_path('presentation/add'), $d['query'] . '&conference=' . $conference->id);
    scholar_add_tab(t('Edit'), scholar_admin_path('conference/edit/' . $conference->id));
    scholar_add_tab(t('List'), scholar_admin_path('conference'));

    return $form;
}

function scholar_conference_presentations_form_submit($form, &$form_state) // {{{
{
    if ($form['#record']) {
        $record = $form['#record'];
        $values = $form_state['values'];

        if (scholar_generic_update_children_weights($record->id, (array) $values['weight'])) {
            drupal_set_message(t('Presentation order updated successfully.'));
        }

        drupal_goto(scholar_admin_path('conference'));
    }
} // }}}

// vim: fdm=marker
