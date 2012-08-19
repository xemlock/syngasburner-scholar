<?php

/**
 * Analizuje zawartość katalogu z plikami i dodaje pliki, których nie
 * ma w bazie danych. Duplikaty plików istniejących w bazie są ignorowane.
 */
function scholar_file_import()
{
    

}

/**
 * Lista plików.
 *
 * @return string
 */
function scholar_file_list() // {{{
{
    $header = array(
        array('data' => t('File name'), 'field' => 'filename', 'sort' => 'asc'),
        array('data' => t('Size'),      'field' => 'size'),
        array('data' => t('Operations'), 'colspan' => '2')
    );

    $query = db_query("SELECT * FROM {scholar_files}" . tablesort_sql($header));
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        $refcount = intval($row['refcount']);

        $rows[] = array(
            check_plain($row['filename']),
            format_size($row['size']),
            l(t('edit'), scholar_admin_path('file/edit/' . $row['id'])),
            $refcount ? '' : l(t('delete'), scholar_admin_path('file/delete/' . $row['id'])),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4)
        );
    }

    $help = t('<p>Below is a list of files managed exclusively by the Scholar module. Files referenced by other records in the database cannot be removed.</p>');

    return '<div class="help">' . $help . '</div>' . theme('table', $header, $rows);
} // }}}

/**
 * Dostarcza rekordy plików do wybieralnej listy.
 *
 * @param array &$options OPTIONAL
 * @return array
 */
function scholar_file_itempicker(&$options = null) // {{{
{
    $options = array(
        'filterKey'    => 'filename',
        'template'     => '{ filename }',
        'emptyMessage' => t('No files found')
    );
    $files = array();

    $query = db_query("SELECT * FROM {scholar_files} ORDER BY filename");
    while ($row = db_fetch_array($query)) {
        $files[] = array(
            'id'       => $row['id'],
            'filename' => $row['filename'],
            'size'     => $row['size'],
        );
    }

    return $files;
} // }}}


/**
 * Formularz do wgrywania plików.
 *
 * @return array
 */
function scholar_file_upload_form() // {{{
{
    $form = array();
    $form['#attributes'] = array('enctype' => "multipart/form-data");

    $form['file'] = array(
        '#type'  => 'file',
        '#title' => t('Upload new file'),
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
 * {@link scholar_file_upload_form()}.
 */
function scholar_file_upload_form_submit($form, &$form_state) // {{{
{
    $validators = array(
        'scholar_file_validate_md5sum'    => array(),
        'scholar_file_validate_filename'  => array(),
        'scholar_file_validate_extension' => array(),
    );

    $dialog = intval($form_state['values']['dialog']);
    $fragment = strval($form_state['values']['fragment']);

    if ($file = file_save_upload('file', $validators, scholar_file_path())) {
        // Przygotuj pola odpowiadajace kolumnom tabeli scholar_files.
        // filename po walidacji zawiera bazowa sciezke (ASCII) do wgranego 
        // pliku, czyli dokladnie to co jest potrzebne.
        $file->id       = null;
        $file->mimetype = $file->filemime;
        $file->size     = $file->filesize;
        $file->refcount = 0;
        $file->user_id  = $file->uid;
        $file->upload_time = date('Y-m-d H:i:s', $file->timestamp);

        drupal_write_record('scholar_files', $file);

        // trzeba usunac plik z tabeli files
        db_query("DELETE FROM {files} WHERE fid = '%d'", $file->fid);

        if ($dialog) {
            // pliki, ktorych upload zostal zainicjowany za pomoca
            // attachmentManagera zostaja automatycznie dodane do
            // wybranych plikow
            drupal_add_js('(new Scholar.Data).set(' . drupal_to_js($fragment) . ',' . drupal_to_js($file) . ')', 'inline');

            return scholar_render(t('File uploaded successfully'), true);
        }
        
        drupal_set_message(t('File uploaded successfully'));
        drupal_goto(scholar_admin_path('file'));
    }

    // poniewaz w tym miejscu nastapi przeladowanie strony, aby przekazac
    // dalej flage 'dialog' musimy zrobic reczne przeladowanie strony
    drupal_goto(scholar_admin_path('file/upload'), $dialog ? 'dialog=1' : null, $fragment);
} // }}}

/**
 * Formularz edycji pliku, dodatkowo pokazujący właściwości pliku.
 *
 * @param array &$form_state
 * @param int $file_id          identyfikator pliku
 * @return array
 */
function scholar_file_edit_form(&$form_state, $file_id)
{
    $file = scholar_fetch_file($file_id, true);

    // Zakladamy, ze w file->filename jest nazwa pliku w czystym ASCII,
    // stad uzycie standardowych funkcji do operowania na stringach.
    $pos       = strrpos($file->filename, '.');
    $filename  = substr($file->filename, 0, $pos);
    $extension = substr($file->filename, $pos);

    $uploader = user_load(intval($file->user_id));

    $form = array('#file' => $file);
    $form['properties'] = array(
        '#type' => 'fieldset',
        '#title' => t('Properties'),
        '#attributes' => array('class' => 'scholar'),
    );

    global $base_url;
    $url = $base_url . '/' . scholar_file_path($file->filename);

    $form['properties'][] = array(
        '#type' => 'markup',
        '#value' => '<dl class="scholar">
<dt>Size</dt><dd>' . format_size($file->size) . '</dd>
<dt>MIME type</dt><dd>' . check_plain($file->mimetype) . '</dd>
<dt>MD5 checksum</dt><dd>' . check_plain($file->md5sum) . '</dd>
<dt>File URL</dt><dd>' . l($url, $url, array('attributes' => array('target' => '_blank'))) . '</dd>
<dt>Uploaded</dt><dd>' . check_plain($file->upload_time). ', by <em>' . ($uploader ? l($uploader->name, 'user/' . $uploader->uid) : 'unknown user') . '</em></dd>
</dl>',
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
    $refcount = intval($file->refcount);

    $header = array(
        array('data' => t('Title'),    'field' => 'title', 'sort' => 'asc'),
        array('data' => t('Language'), 'field' => 'language'),
        array('data' => t('Row type'), 'field' => 'row_type'),
    );

    $form['ref'] = array(
        '#type' => 'fieldset',
        '#title' => t('Dependent database records'),
        '#attributes' => array('class' => 'scholar'),
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

    if ($rows || $refcount) {
        if (count($rows) != $refcount) {
            $text = '<div class="error">' .
                format_plural($refcount,
                    'Expected %refcount file, but found %count. Database corruption detected.',
                    'Expected %refcount files, but found %count. Database corruption detected.',
                    array('%refcount' => $refcount, '%count' => count($rows))
                ) .
                '</div>';

        } else {
            $text = '';
        }

        $form['ref'][] = array(
            '#type' => 'markup',
            '#value' => theme('table', $header, $rows) . $text,
        );
    }

    // dodaj taby jezeli dostepny jest modul tabs
    if (function_exists('drupal_add_tab')) {
        drupal_add_tab(t('List'), scholar_admin_path('file'));
        drupal_add_tab(t('Edit'), scholar_admin_path('file/edit/' . $file->id), array('class' => 'active'));

        // zezwol na usuniecie plitu tylko wtedy, jezeli nie ma stron 
        // odwolujacych sie do tego pliku
        if (empty($rows)) {
            drupal_add_tab(t('Delete'), scholar_admin_path('file/delete/' . $file->id));
        }
    }

    return $form;
}

/**
 * Obsługa zmiany nazwy pliku.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_file_edit_form_submit($form, &$form_state) // {{{
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
            return drupal_goto(scholar_admin_path('file/edit/' . $file->id));
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
function scholar_file_delete_form(&$form_state, $file_id) // {{{
{
    $file = scholar_fetch_file($file_id, true);

    $form = array('#file' => $file);
    $form = confirm_form($form,
        t('Are you sure you want to delete file (%filename)?', array('%filename' => $file->filename)),
        scholar_admin_path('file'),
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    return $form;
} // }}}

/**
 * Sprawdza, czy plik podany w formularzu może zostać usunięty.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_file_delete_form_validate($form, &$form_state) // {{{
{
    $file = $form['#file'];

    if ($file) {
        // sprawdzamy dokladnie faktyczna liczbe odwolan do tego pliku,
        // na wypadek gdyby refcount zawieralo niepoprawna wartosc

        if (scholar_file_refcount($file->id)) {
            form_set_error('', 
                format_plural($refcount,
                    'There is one page referencing this file. File cannot be deleted.',
                    'There are %refcount pages referencing this file. File cannot be deleted.',
                    array('%refcount' => $refcount)
                )
            );
        }
    }
} // }}}

/**
 * Wywołuje funkcję usuwającą plik z dysku.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_file_delete_form_submit($form, &$form_state) // {{{
{
    if ($file = $form['#file']) {
        scholar_delete_file($file);
        drupal_set_message(t('File deleted successfully (%filename)', array('%filename' => $file->filename)));
    }

    drupal_goto(scholar_admin_path('file'));
} // }}}

// vim: fdm=marker
