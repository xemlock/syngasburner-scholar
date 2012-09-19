<?php

/**
 * Lista plików.
 *
 * @return string
 */
function scholar_pages_file_list() // {{{
{
    $header = array(
        array('data' => t('File name'), 'field' => 'filename', 'sort' => 'asc'),
        array('data' => t('Size'),      'field' => 'size'),
        array('data' => t('Operations'), 'colspan' => '2'),
    );

    $query = scholar_files_recordset(null, $header);
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['filename']),
            format_size($row['size']),
            scholar_oplink(t('edit'), 'files', 'edit/%d', $row['id']),
            scholar_oplink(t('delete'), 'files', 'delete/%d', $row['id']),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4)
        );
    }

    $help = t('<p>Below is a list of files managed exclusively by the Scholar module.</p>');

    return '<div class="help">' . $help . '</div>' . theme_scholar_table($header, $rows);
} // }}}

/**
 * Formularz do wgrywania plików.
 *
 * @return array
 */
function scholar_pages_file_upload_form() // {{{
{
    $form = array();
    $form['#attributes'] = array('enctype' => "multipart/form-data");

    $form['file'] = array(
        '#type'  => 'file',
        '#title' => t('Upload new file'),
        '#size'  => 36, // domyslne 60 jest zbyt duze
        '#description' => t(
            'The maximum upload size is %filesize. Only files with the following extensions may be uploaded: %extensions. ',
            array(
                '%extensions' => scholar_file_allowed_extensions(),
                '%filesize' => format_size(file_upload_max_size()),
            )
        ),
    );

    // pole dialog jest potrzebne, jezeli strona otwarta jest w IFRAME
    $form['dialog'] = array(
        '#type' => 'hidden',
        '#default_value' => intval($_REQUEST['dialog']),
    );

    // fragment adresu URL (po #) jest istotny jezeli strona jest
    // otwarta w okienku lub IFRAME, trzeba przekazac go dalej
    $form['fragment'] = array(
        '#type' => 'hidden',
    );
    drupal_add_js("\$(function() {\$('[name=\"fragment\"]').val(window.location.hash.substr(1)) })", 'inline');

    // Jezeli nie doda sie przycisku submit (nawet gdy formularz jest
    // w okienku lub IFRAME), nie zostanie wywolana funkcja obslugi 
    // przeslania formularza (*_submit)
    $form['submit'] = array(
        '#type'  => 'submit',
        '#value' => t('Upload file'),
    );

    return $form;
} // }}}

/**
 * Obsługa walidacji i zapisania pliku przesłanego za pomocą formularza
 * {@link scholar_pages_file_upload_form()}.
 */
function scholar_pages_file_upload_form_submit($form, &$form_state) // {{{
{
    $dialog = intval($form_state['values']['dialog']);
    $fragment = strval($form_state['values']['fragment']);

    if ($file = scholar_save_upload('file')) {
        if ($dialog) {
            // pliki, ktorych upload zostal zainicjowany za pomoca
            // formElements.files zostaja automatycznie dodane do
            // wybranych plikow
            drupal_add_js('(new Scholar.Data).set(' . drupal_to_js($fragment) . ',' . drupal_to_js($file) . ')', 'inline');

            return scholar_render(t('File uploaded successfully'), true);
        }
        
        drupal_set_message(t('File uploaded successfully'));
        drupal_goto(scholar_path('files'));
    }

    // poniewaz w tym miejscu nastapi przeladowanie strony, aby przekazac
    // dalej flage 'dialog' musimy zrobic reczne przeladowanie strony
    drupal_goto(scholar_path('files', 'upload'), $dialog ? 'dialog=1' : '', $fragment);
} // }}}

/**
 * Formularz edycji pliku, dodatkowo pokazujący właściwości pliku.
 *
 * @param array &$form_state
 * @param int $file_id          identyfikator pliku
 * @return array
 */
function scholar_pages_file_edit_form(&$form_state, $file_id) // {{{
{
    $file = scholar_fetch_file($file_id, scholar_path('files'));

    // Zakladamy, ze w file->filename jest nazwa pliku w czystym ASCII,
    // stad uzycie standardowych funkcji do operowania na stringach.
    $pos       = strrpos($file->filename, '.');
    $filename  = substr($file->filename, 0, $pos);
    $extension = substr($file->filename, $pos);

    $uploader = user_load(intval($file->user_id));

    $form = array('#file' => $file);
    $form['properties'] = array(
        '#type' => 'fieldset',
        '#title' => t('File properties'),
        '#attributes' => array('class' => 'scholar'),
    );

    global $base_url;
    $url = $base_url . '/' . scholar_file_path($file->filename);

    $form['properties'][] = array(
        '#type' => 'markup',
        '#value' => theme_scholar_dl(array(
            t('Size'),         format_size($file->size),
            t('MIME type'),    check_plain($file->mimetype),
            t('MD5 checksum'), check_plain($file->md5sum),
            t('File URL'),     l($url, $url, array('attributes' => array('target' => '_blank'))),
            t('Uploaded'),     t('!time, by !user', array(
                '!time' => date('Y-m-d H:i:s', $file->create_time),
                '!user' => '<em>' . ($uploader ? l($uploader->name, 'user/' . $uploader->uid) : t('unknown user')) . '</em>'
            )),
        )),
    );

    $form['rename'] = array(
        '#type' => 'fieldset',
        '#title' => t('Rename file'),
        '#attributes' => array('class' => 'scholar'),
    );
    $form['rename']['filename'] = array(
        '#type' => 'textfield',
        '#title' => t('File name'),
        '#required' => true,
        '#default_value' => $filename,
        '#field_suffix'  => $extension,
        '#description' => t('Accented characters will be automatically transliterated into their ASCII counterparts. Non-letter characters other than space, dot and dash will be replaced with the underscore character.'), // znaki narodowe
    );
    $form['rename']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Rename file'),
    );

    // wyswietl liste stron odwolujacych sie do tego pliku
    $header = array(
        array('data' => t('Title'),    'field' => 'title', 'sort' => 'asc'),
        array('data' => t('Language'), 'field' => 'language'),
        array('data' => t('Row type'), 'field' => 'row_type'),
    );

    $rows  = array();
    $langs = scholar_languages();

    foreach (scholar_file_fetch_dependent_rows($file, $header) as $row) {
        $rows[] = array(
            check_plain($row['title']),
            check_plain(scholar_languages($row['language'])),
            check_plain($row['row_type']),
        );
    }

    if ($rows) {
        $form['ref'] = array(
            '#type' => 'fieldset',
            '#title' => t('Dependent database records'),
            '#attributes' => array('class' => 'scholar'),
        );

        $form['ref'][] = array(
            '#type' => 'markup',
            '#value' => theme_scholar_table($header, $rows),
        );
    }

    // dodaj taby (dziala jezeli dostepny jest modul tabs)
    scholar_add_tab(t('list'), scholar_path('files'));
    scholar_add_tab(t('edit'), scholar_path('files', 'edit/%d', $file->id));
    scholar_add_tab(t('delete'), scholar_path('files', 'delete/%d', $file->id));

    return $form;
} // }}}

/**
 * Obsługa zmiany nazwy pliku.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_pages_file_edit_form_submit($form, &$form_state) // {{{
{
    if ($file = $form['#file']) {
        // Zakladamy, ze w file->filename jest nazwa pliku w czystym ASCII,
        // stad uzycie standardowych funkcji do operowania na stringach.
        $src = $file->filename;
        $pos = strrpos($src, '.');
        $ext = substr($src, $pos);

        // Dodaj do nazwy pliku oryginalne rozszerzenie
        $dst = $form_state['values']['filename'] . $ext;

        if (scholar_rename_file($file, $dst, $error)) {
            drupal_set_message(t('File %from renamed successfully to %to', array('%from' => $src, '%to' => $file->filename)));
            return drupal_goto(scholar_path('files', 'edit/%d', $file->id));
        }

        form_set_error('', $error);
    }

    // nie przekierowywuj, poniewaz wystapily bledy formularza
    $form['#redirect'] = false;
    $form_state['redirect'] = false;
} // }}}

/**
 * Strona z prośbą o potwierdzenie usunięcia pliku o podanym identyfikatorze.
 *
 * @param array &$form_state
 * @param int $file_id          identyfikator pliku
 */
function scholar_pages_file_delete_form(&$form_state, $file_id) // {{{
{
    $file = scholar_fetch_file($file_id, scholar_path('files'));
    $refcount = scholar_file_refcount($file->id);

    $form = array('#file' => $file);
    $form = confirm_form($form,
        t('Are you sure you want to delete file %filename?', array('%filename' => $file->filename)),
        scholar_path('files'),
        format_plural($refcount,
            'There is one page referencing this file. This action cannot be undone.',
            'There are %refcount pages referencing this file. This action cannot be undone.',
            array('%refcount' => $refcount)
        ),
        t('Delete'),
        t('Cancel')
    );

    return $form;
} // }}}

/**
 * Wywołuje funkcję usuwającą plik z dysku.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_pages_file_delete_form_submit($form, &$form_state) // {{{
{
    if ($file = $form['#file']) {
        scholar_delete_file($file);
        drupal_set_message(t('File %filename deleted successfully.', array('%filename' => $file->filename)));
    }

    drupal_goto(scholar_path('files'));
} // }}}

/**
 * Dostarcza rekordy plików do wybieralnej listy.
 *
 * @param array &$options OPTIONAL
 * @return array
 */
function scholar_pages_file_itempicker(&$options = null) // {{{
{
    $options = array(
        'filterKey'    => 'filename',
        'template'     => '{ filename }',
        'emptyMessage' => t('No files found')
    );

    $files = array();
    $query = scholar_files_recordset(null, 'filename');

    while ($row = db_fetch_array($query)) {
        $files[] = array(
            'id'       => $row['id'],
            'filename' => $row['filename'],
            'size'     => $row['size'],
        );
    }

    return $files;
} // }}}

// vim: fdm=marker
