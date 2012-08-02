<?php

function scholar_file_dir($filename = null) // {{{
{
    $path = rtrim(file_directory_path(), '\\/') . '/scholar/' . ltrim($filename, '/');
    return str_replace('\\', '/', $path);
} // }}}


/**
 * Analizuje zawartość katalogu z plikami i uaktualnia dane
 * w bazie.
 */
function scholar_file_rebuild()
{
    

}

/**
 * @param int $file_id          identyfikator pliku
 * @param bool $redirect        czy zgłosić błąd i przekierować do listy
 *                              plików, jeżeli plik nie został znaleziony
 */
function scholar_fetch_file($file_id, $redirect = false)
{
    $query = db_query("SELECT * FROM {scholar_files} WHERE id = %d", $file_id);
    $row   = db_fetch_object($query);

    if (empty($row)) {
        drupal_set_message(t('Invalid file id supplied (%id)', array('%id' => $file_id)), 'error');
        drupal_goto('scholar/files');
        exit;
    }

    return $row;
}

/**
 * Usuwa plik z bazy danych i dysku.
 *
 * @param object &$file         obiekt reprezentujący plik
 */
function scholar_delete_file(&$file) // {{{
{
    db_query("DELETE FROM {scholar_files} WHERE id = %d", $file->id);
    @unlink(scholar_file_dir($file->filename));
} // }}}

function scholar_file_edit_form(&$form_state, $file_id)
{
    $file = scholar_fetch_file($file_id, true);

    $pos       = strrpos($file->filename, '.');
    $filename  = substr($file->filename, 0, $pos);
    $extension = substr($file->filename, $pos);

    scholar_add_css();

    $uploader = user_load(intval($file->user_id));

    $form = array();
    $form['properties'] = array(
        '#type' => 'fieldset',
        '#title' => t('Properties'),
        '#attributes' => array('class' => 'scholar'),
    );

    $url = url(scholar_file_dir($file->filename), array('absolute' => true));
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
        '#description' => t('All filenames must contain only ASCII characters. Non-letter characters other than dash and underscore will be replaced with the latter.'),
    );
    $form['rename']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Rename file'),
    );

    if ($refcount = intval($file->refcount)) {
        $header = array(
            array('data' => t('Title'),    'field' => 'title', 'sort' => 'asc'),
            array('data' => t('Language'), 'field' => 'language'),
        );

        $form['ref'] = array(
            '#type' => 'fieldset',
            '#title' => t('Referencing pages'),
            '#attributes' => array('class' => 'scholar'),
        );
        $query = db_query("SELECT * FROM {node} n JOIN {scholar_attachments} a ON n.nid = a.node_id WHERE a.file_id = %d" . tablesort_sql($header), $file->id);
        $rows  = array();

        $langs = Langs::languages();
        while ($row = db_fetch_array($query)) {
            $rows[] = array(
                'title'    => check_plain($row['title']),
                'language' => check_plain(isset($langs[$row['language']]) ? $langs[$row['language']] : t('Language neutral')),
            );
        }

        if (count($rows) != $refcount) {
            $rows[] = array(
                array(
                    'data' => format_plural($refcount,
                        'Expected %refcount file, but found %count. Database corruption detected.',
                        'Expected %refcount files, but found %count. Database corruption detected.',
                        array('%refcount' => $refcount, '%count' => count($rows))
                    ),
                    'colspan' => 2,
                ),
            );
        }

        $form['ref'][] = array(
            '#type' => 'markup',
            '#value' => theme('table', $header, $rows),
        );
    }

    drupal_add_tab(l(t('List'), 'scholar/files'));
    drupal_add_tab(l(t('Edit'), 'scholar/files/edit/' . $file->id), array('class' => 'active'));

    if (0 == $refcount) {
        drupal_add_tab(l(t('Delete'), 'scholar/files/delete/' . $file->id));
    }

    //p('Zażółć gęślą jaźń; Herrens bön, även Fader vår eller Vår Fader. ĚØŘ!');
    //p(scholar_ascii('Zażółć gęślą jaźń; Herrens bön, även Fader vår eller Vår Fader. ĚØŘ!'));

    return $form;
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
        $rows[] = array(
            check_plain($row['filename']),
            format_size($row['size']),
            l(t('edit'), "scholar/files/edit/{$row['id']}"),
            intval($row['refcount']) ? '' : l(t('delete'), "scholar/files/delete/{$row['id']}"),
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

/*
 * Akcja przeznaczona tylko dla okienek i ramek. Bezposredni
 * dostęp jest niewskazany.
 */
function scholar_file_select() // {{{
{
    $files = array();

    $query = db_query("SELECT * FROM {scholar_files} ORDER BY filename");
    while ($row = db_fetch_array($query)) {
        $files[] = $row;
    }

    ob_start();
?>
<script type="text/javascript">
var items = <?php echo drupal_to_js($files) ?>;
function filter() {
    var elem = document.getElementById('name-filter');
    if (arguments.length > 0) {
        elem.value = arguments[0];
    }

    var prefix = elem.value.toLowerCase();
    for (var i = 0; i < items.length; ++i) {
        var v = items[i];
        var vv = v.filename.toLowerCase();
        var e = document.getElementById('item-' + v.id);
        if (e) e.style.display = vv.indexOf(prefix) != -1 ? '' : 'none';
        else console.log('nie ma ajtema');
    }
}
var caller = window.opener ? window.opener : (window.parent != window ? window.parent : null);
var hash = window.location.hash.substring(2);
var callerStorage = caller ? caller[hash] : null;

if (callerStorage) callerStorage.receiver({
    notifyDelete: function(file_id) {
        if (document) { // jezeli okienko jest otwarte
        var e = document.getElementById('item-' + file_id);
        if (e) {
            e.innerHTML = e.innerHTML.replace(/ \(SELECTED\)/, '');
        }
        }
    }
});
window.onload = function() {
    if (!callerStorage) return;
    var c = document.getElementById('items').childNodes;
    for (var i = 0; i < c.length; ++i) {
        if (c[i].tagName != 'LI') continue;
        if (callerStorage.has(c[i].id.replace(/^item-/, ''))) {
            c[i].innerHTML += ' (SELECTED)';
        }
    }
}
function select_item(elem) {
    if (!callerStorage) return;
    var id = elem.id.replace(/^item-/, '');
    if (callerStorage.has(id)) {
            alert('Already selected!');
            return;    
    }
    callerStorage.add(id);
    elem.innerHTML += ' (SELECTED)';
}
</script>
<style type="text/css">
#items li {
  cursor:pointer;
-webkit-touch-callout: none;
-webkit-user-select: none;
-khtml-user-select: none;
-moz-user-select: none;
-ms-user-select: none;
user-select: none;
}
#items li:hover {
  background: yellow;
}
</style>
    Filtruj: <input type="text" onkeyup="filter()" id="name-filter" placeholder="<?php echo 'Search file'; ?>"/><button type="button" onclick="filter('');">Wyczyść</button> <button type="button" onclick="window.close()">Zamknij</button>
Dwukrotne kliknięcie zaznacza element
<hr/>
<?php if ($files) { ?>
<ul id="items"><?php foreach ($files as $file) { ?>
<li id="item-<?php echo $file['id'] ?>" ondblclick="select_item(this)"><?php echo $file['filename'] ?>
</li>
<?php } ?></ul>
<?php } else { ?>Nie ma plików<? } ?>
<?php

    return scholar_render(ob_get_clean(), true);
} // }}}

/**
 * Zwraca listę rozszerzeń plików, które mogą zostać przesłane.
 *
 * @return string               rozszerzenia plików oddzielone spacjami
 */
function scholar_file_allowed_extensions() // {{{
{
    return 'bib jpg gif png pdf ps zip';
} // }}}

/**
 * Sprawdza czy w bazie danych nie ma pliku o takiej samej sumie MD5.
 * Jeżeli nie ma do obiektu reprezentującego plik zostanie zapisana
 * obliczona suma MD5.
 *
 * @param object &$file         obiekt reprezentujący plik
 * @return array                lista błędów walidacji
 */
function scholar_file_validate_md5sum(&$file) // {{{
{
    $errors = array();

    $md5 = md5_file($file->filepath);

    $query = db_query("SELECT * FROM {scholar_files} WHERE md5sum = '%s'", $md5);
    $row   = db_fetch_array($query);

    if ($row) {
        $errors[] = t('This file aready exists in the database (%filename).', array('%filename' => $row['filename'])); 
    }

    if (empty($errors)) {
        $file->md5sum = $md5;
    }

    return $errors;
} // }}}

/**
 * Sprawdza poprawność rozszerzenia pliku.
 *
 * @param object &$file         obiekt reprezentujący plik
 * @return array                lista błędów walidacji
 */
function scholar_file_validate_extension(&$file) // {{{
{
    $extensions = scholar_file_allowed_extensions();
    $regex = '/\.(' . preg_replace('/\s+/', '|', preg_quote($extensions)) . ')$/i';

    $errors = array();

    if (!preg_match($regex, $file->filename)) {
        $errors[] = t('Only files with the following extensions are allowed: %files-allowed.', array('%files-allowed' => $extensions));
    }

    return $errors;
} // }}}

/**
 * Waliduje nazwę pliku zastępując w niej potencjalnie problematyczne 
 * znaki podkreśleniami. Po wykonaniu tej operacji zmienione zostają
 * wartości pól 'filename' i 'destination' obiektu.
 *
 * @param object &$file         obiekt reprezentujący plik
 * @return array                lista błędów walidacji
 */
function scholar_file_validate_filename(&$file) // {{{
{
    $errors = array();

    $basename = basename($file->filename);
    $pos      = strrpos($basename, '.');
    $filename = scholar_ascii(substr($basename, 0, $pos));

    // w tym miejscu jezeli nie ma rozszerzenia filename zawiera 
    // pusty string, ktory nie przejdzie walidacji

    if (0 == strlen($filename)) {
        $errors[] = t('Filename must not be empty');
    } else {
        $filename = preg_replace('/[^-_a-z0-9]/i', '_', $filename);
        $file->filename = $filename . substr($basename, $pos);

        // zaktualizuj docelowe polozenie pliku
        $file->destination = dirname($file->destination) . '/' . $file->filename;
    }

    return $errors;
} // }}}

/**
 * Definicja formularza do wgrywania plików.
 *
 * @return array
 */
function scholar_file_upload_form() // {{{
{
    drupal_set_title(t('Upload file'));

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
    $form['submit'] = array(
        '#type'  => 'submit',
        '#value' => t('Upload file'),
    );

    return $form;
} // }}}

function scholar_file_upload_form_submit() // {{{
{ 
    $validators = array(
        'scholar_file_validate_md5sum'    => array(),
        'scholar_file_validate_extension' => array(),
        'scholar_file_validate_filename'  => array(),
    );

    if ($file = file_save_upload('file', $validators, scholar_file_dir())) {
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

        drupal_set_message(t('File uploaded successfully'));
        drupal_goto('scholar/files');
    }
} // }}}

/**
 * Strona z prośbą o potwierdzenie usunięcia pliku o podanym identyfikatorze.
 *
 * @param array &$form_state
 * @param int $file_id
 */
function scholar_file_delete_form(&$form_state, $file_id) // {{{
{
    $file = scholar_fetch_file($file_id, true);

    $form = array('#file' => $file);
    $form = confirm_form($form,
        t('Are you sure you want to delete file (%filename)?', array('%filename' => $file->filename)),
        'scholar/files',
        t('This action cannot be undone.'),
        t('Delete'),
        t('Cancel')
    );

    scholar_add_css();

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

    if ($file && ($refcount = intval($file->refcount))) {
        form_set_error('', 
            format_plural($refcount,
                'There is one page referencing this file. File cannot be deleted.',
                'There are %refcount pages referencing this file. File cannot be deleted.',
                array('%refcount' => $refcount)
            )
        );
    }
} // }}}

/**
 * Wywołuje funkcję usuwającą plik z dysku.
 */
function scholar_file_delete_form_submit($form, &$form_state) // {{{
{
    if ($file = $form['#file']) {
        scholar_delete_file($file);
        drupal_set_message(t('File deleted successfully (%filename)', array('%filename' => $file->filename)));
    }

    drupal_goto('scholar/files');
} // }}}

