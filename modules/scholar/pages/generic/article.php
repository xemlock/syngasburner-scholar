<?php

function scholar_generics_article_form(&$form_state, $record = null) // {{{
{
    // artykuly moga nalezec do ksiazki (wydawnictwa zwartego)
    $parents = scholar_generic_parent_options('journal');

    $form = scholar_generic_form(array(
        'title' => array(
            '#required'    => true,
        ),
        'start_date' => array(
            '#title'       => t('Year'),
            '#description' => t('Date of publication in format YYYY or YYYY-MM (four-digit year with optional month).'),
            '#maxlength'   => 7,
            '#required'    => true,
            '#element_validate' => array('scholar_form_validate_publication_date'),
        ),
        'authors' => array(
            '#required'    => true,
            '#description' => t('Remember about correct order, if there is more than one author or contributor.'),
        ),
        'parent_id' => empty($parents) ? false : array(
            '#title'       => t('Journal'),
            '#options'     => $parents,
        ),
        'bib_details' => array(
            '#description' => t('e.g. volume and issue number, page numbers etc.'), // np. numery tomu i wydania, numery stron
        ),
        'suppinfo',
        'url',
        'files',
        'events' => array( // np. info o wydaniu ksiazki, bez daty koncowej
            'end_date'     => false,
        ),
        'submit' => array(
            'title'        => $record ? t('Save changes') : t('Add article'),
            'cancel'       => scholar_path('generics.article'),
        ),
    ), $record);

    return $form;
} // }}}

function _scholar_generics_article_form_process_values(&$values) // {{{
{
    // data poczatku ma co najwyzej 7 znakow (YYYY-MM)
    $values['start_date'] = substr(trim($values['start_date']), 0, 7);

    // nie ma daty koncowej
    $values['end_date'] = null;
} // }}}

/**
 * @return array
 */
function _scholar_generics_article_list_row($row) // {{{
{
    if (empty($row)) {
        return array(
            array('data' => t('Date'),       'field' => 'start_date', 'sort' => 'desc'),
            array('data' => t('Authors'),    'field' => 'bib_authors'),
            array('data' => t('Title'),      'field' => 'title'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    return array(
        substr($row['start_date'], 0, intval($row['start_date_len'])),
        str_replace(' et al.', ' <em>et al.</em>', check_plain($row['bib_authors'])),
        check_plain($row['title']),
        scholar_oplink(t('edit'), 'generics.article', 'edit/%d', $row['id']),
        scholar_oplink(t('delete'), 'generics.article' , 'delete/%d', $row['id']),
    );
} // }}}

// vim: fdm=marker
