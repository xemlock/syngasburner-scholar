<?php

function scholar_generics_journal_form(&$form_state, $record = null) // {{{
{
    if ($record) {
        $record->start_date = strlen($record->start_date) 
            ? intval(substr($record->start_date, 0, 4))
            : '';
    }

    $categories = scholar_category_options('generics', 'journal');
    $form = scholar_generic_form(array(
        'title' => array(
            '#required'    => true,
        ),
        'start_date' => array(
            '#title'       => t('Year'), // Rok wydania
            '#maxlength'   => 4,
            '#description' => 'Pozostaw puste jeżeli jest to seria wydawnicza lub czasopismo. Wpisz jeżeli jest to książka lub inne wydawnictwo zwarte.',
        ),
        'bib_details' => array(
            '#description' => t('Information about editors, series, publisher etc.'),
        ),
        'authors' => array(
            '#title' => t('Authors'),
            '#description' => 'Wypełnij jeżeli książka. W przypadku pracy zbiorowej informacje o redaktorach umieść w polu \'szczegóły\'.',
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
        'submit' => array(
            'title' => $record ? t('Save changes') : t('Add journal'),
            'cancel' => scholar_path('generics.journal'),
        ),
    ), $record);

    return $form;
} // }}}

function _scholar_generics_journal_form_process_values(&$values) // {{{
{
    $start_date = trim($values['start_date']);

    if (strlen($start_date)) {
        $start_date = sprintf("%04d", $values['start_date']) . '-01-01 00:00:00';
    }

    $values['start_date'] = $start_date;
    $values['end_date']   = null;
} // }}}

function _scholar_generics_journal_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Year'),       'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Authors'),    'field' => 'bib_authors'),
            array('data' => t('Title'),      'field' => 'title'),
            array('data' => t('Operations'), 'colspan' => '3'),
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
        scholar_oplink(t('edit'), 'generics.journal', 'edit/%d', $row['id']),
        scholar_oplink($row['child_count'] ? t('articles (!count)', array('!count' => $row['child_count'])) : t('articles'), 'generics.journal', 'children/%d/article', $row['id']),
        scholar_oplink(t('delete'), 'generics.journal', 'delete/%d', $row['id']),
    );
} // }}}

    // jezeli podano date wydania, wtedy czasopismo staje sie ksiazka,
    // dla artykulow w czasopismach informacje o konkretnych wydaniach
// nalezy podawac w bib_details

function _scholar_generics_journal_children_article_row($row = null) // {{{
{
    if (empty($row)) {
        return array(
            scholar_tabledrag_handle(), // uchwyt tabledraga
            t('Year'),
            t('Title'),
            t('Weight'),
            array('data' => t('Operations'), 'colspan' => 2),
        );
    }

    return array(
        scholar_tabledrag_handle(),
        substr($row['start_date'], 0, (int) $row['start_date_len']),
        _scholar_generics_theme_bib_authors($row['bib_authors'], ': ') . check_plain($row['title']),
        '@weight',
        scholar_oplink(t('edit'), 'generics.article', 'edit/%d', $row['id']),
        scholar_oplink(t('delete'), 'generics.article', 'delete/%d', $row['id']),
    );
} // }}}

function scholar_generics_journal_children_article_form(&$form_state, $record) // {{{
{
    // reguly sortowania - nie ma ograniczen, daty artykulow sa ignorowane,
    // brane sa pod uwage tylko jesli nie ma rekordu nadrzednego
    // sortowanie w raportach:
    //     CASE WHEN parent_start_date IS NULL THEN start_date ELSE parent_start_date END DESC), weight ASC
    $form = array(
        '#record' => $record,

    );

    $children = scholar_generic_load_children($record->id, 'article', 'weight');

    scholar_generics_weight_form($form, $children,
        '_scholar_generics_journal_children_article_row');

    _scholar_generics_journal_tabs($record);

    drupal_set_title(t('Journal'));

    return $form;
} // }}}

function _scholar_generics_journal_tabs($record) // {{{
{
    if ($record) {
        $query = 'destination=' . $_GET['q'] . '&parent_id=' . $record->id;
        scholar_add_tab(t('Edit'), scholar_path('generics.journal', 'edit/%d', $record->id), $query);
        scholar_add_tab(t('Add article'), scholar_path('generics.article', 'add'), $query);
        scholar_add_tab(t('Articles'), scholar_path('generics.journal', 'children/%d/article', $record->id));
        scholar_add_tab(t('Back to journal list'), scholar_path('generics.journal'));
    }
} // }}}

// vim: fdm=marker
