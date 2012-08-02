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
 * Lista plików.
 *
 * @return string
 */
function scholar_file_list() // {{{
{
    $header = array(
        array('data' => t('File name'), 'field' => 'filename', 'sort' => 'asc'),
        array('data' => t('Size'),      'field' => 'filesize'),
        array('data' => t('Refcount'),  'field' => 'refcount'),
        array('data' => t('Operations'), 'colspan' => '2')
    );

    $query = db_query("SELECT * FROM {scholar_files}" . tablesort_sql($header));
    $rows  = array();

    while ($row = db_fetch_array($query)) {
        $rows[] = array(
            check_plain($row['filename']),
            format_size($row['filesize']),
            check_plain($row['refcount']),
            l(t('view'), $row['filepath']),
            intval($row['refcount']) ? '' : l(t('delete'), "scholar/files/delete/{$row['id']}"),
        );
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 4)
        );
    }

    $help = t('<p>Below is a list of files managed exclusively by the Scholar module. Files with refcount value greater than 0 cannot be removed, as they are referenced by other records in the database.</p>');

    return '<div class="help">' . $help . '</div>' . theme('table', $header, $rows);
} // }}}

/*
 * Akcja przeznaczona tylko dla okienek i ramek. Bezposredni
 * dostęp jest niewskazany.
 */
function scholar_file_select() // {{{
{
    $files = array();

    if (db_table_exists('files')) {
        $query = db_query("SELECT * FROM {scholar_files} ORDER BY filename");
        while ($row = db_fetch_array($query)) {
            $files[$row['file_id']] = $row;
        }
    }
    $files[] = array('file_id' => 3, 'filename' => 'scholar_manual.pdf', 'filesize' => 910245);
    $files[] = array('file_id' => 5, 'filename' => 'scholar_overview.png', 'filesize' => 12382918);
    $files[] = array('file_id' => 8, 'filename' => 'scholar_license.txt', 'filesize' => 135027);

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
        var e = document.getElementById('item-' + v.file_id);
        if (e) e.style.display = vv.indexOf(prefix) != -1 ? '' : 'none';
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
Filtruj: <input type="text" onkeyup="filter()" id="name-filter"/><button type="button" onclick="filter('');">Wyczyść</button> <button type="button" onclick="window.close()">Zamknij</button>
Dwukrotne kliknięcie zaznacza element
<hr/>
<?php if ($files) { ?>
<ul id="items"><?php foreach ($files as $id => $file) { ?>
<li id="item-<?php echo $file['file_id'] ?>" ondblclick="select_item(this)"><?php echo $file['filename'] ?>
</li>
<?php } ?></ul>
<?php } else { ?>Nie ma plików<? } ?>
<?php

    return scholar_render(ob_get_clean(), true);
} // }}}

function scholar_file_upload_form()
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
}

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
function scholar_file_validate_extensions(&$file) // {{{
{
    $extensions = scholar_file_allowed_extensions();
    $regex = '/\.(' . preg_replace('/\s+/', '|', preg_quote($extensions)) . ')$/i';

    $errors = array();

    if (!preg_match($regex, $file->filename)) {
        $errors[] = t('Only files with the following extensions are allowed: %files-allowed.', array('%files-allowed' => $extensions));
    }

    return $errors;
} // }}}

function scholar_file_upload_form_submit() // {{{
{ 
    $validators = array(
        'scholar_file_validate_md5sum' => array(),
        'scholar_file_validate_extensions' => array(),
    );

    if ($file = file_save_upload('file', $validators, scholar_file_dir())) {
        $file->id = null;
        $file->upload_time = date('Y-m-d H:i:s', $file->timestamp);
        $file->user_id = $file->uid;

        drupal_write_record('scholar_files', $file);

        // trzeba usunac plik z tabeli files
        db_query("DELETE FROM {files} WHERE fid = '%d'", $file->fid);

        drupal_set_message(t('File uploaded successfully'));
        drupal_goto('scholar/files');
    }
} // }}}

/*function gallery_get_uploaded_file_name($name) { // {{{
  $tmpname = $_FILES['files']['tmp_name'][$name];
  $ext = gallery_get_ext($tmpname);
  $fname = md5($tmpname);
  $dir = gallery_image_dir();
  $i = 0;
  do {
    $file = $dir . sprintf("/%s%02d.%s", $fname, $i, $ext);
    $i++;
  } while (file_exists($file));

  return $file;
} // }}}*/
