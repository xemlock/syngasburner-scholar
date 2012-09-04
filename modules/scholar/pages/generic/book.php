<?php

function scholar_generics_book_form(&$form_state, $record = null) // {{{
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
        '#value' => scholar_list_path('generics.book'),
    );

    return $form;
} // }}}

function _scholar_generics_book_form_process_values(&$values) // {{{
{
    $start_date = trim($values['start_date']);

    if (strlen($start_date)) {
        $start_date = sprintf("%04d", $values['start_date']) . '-01-01 00:00:00';
    }

    $values['start_date'] = $start_date;
    $values['end_date']   = null;
} // }}}

function _scholar_generics_book_list_spec($row = null) // {{{
{
    if (null === $row) {
        return array(
            array('data' => t('Year'),       'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Authors'),    'field' => 'bib_authors'),
            array('data' => t('Title'),      'field' => 'title'),
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
        l(t('edit'),  scholar_admin_path('book/edit/' . $row['id'])),
        l(t('delete'), scholar_admin_path('book/delete/' . $row['id'])),
    );
} // }}}

