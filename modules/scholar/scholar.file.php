<?php

/**
 * Zwraca ścieżkę do katalogu z plikami zarządzanymi przez ten moduł,
 * lub gdy podano nazwę pliku ścieżkę do tego pliku wewnątrz wspomnianego
 * katalogu.
 *
 * @param string $filename      OPTIONAL nazwa pliku
 * @return string
 */
function scholar_file_path($filename = null) // {{{
{
    $path = rtrim(file_directory_path(), '\\/') . '/scholar/' . ltrim($filename, '/');
    return str_replace('\\', '/', $path);
} // }}}

/**
 * @return false|string
 */
function scholar_sanitize_filename($filename) // {{{
{
    $filename = scholar_ascii($filename);

    // W wyniku transliteracji do ASCII niektore znaki moga zostac
    // calkowicie usuniete (np. alfabety wschodnioazjatyckie).

    $filename = basename(str_replace('\\', '/', $filename));
    $filename = trim(preg_replace('/\s/', ' ', $filename));

    if (0 == strlen($filename)) {
        return false;
    }

    $pos = strrpos($filename, '.');
    if (empty($pos) || (strlen($filename) - 1 == $pos)) {
        // brak kropki lub brak nazwy pliku (jedyna kropka wystepuje
        // na poczatku), lub puste rozszerzenie (kropka na koncu)
        return false;
    }

    // Usun biale znaki z rozszerzenia. Rozszerzenie zawiera tutaj
    // przynajmniej jeden niepusty znak.
    $extension = str_replace(' ', '', substr($filename, $pos));

    // Usun spacje na koncu nazwy pliku, przed rozszerzeniem.
    // Wiemy, ze nazwa pliku zawiera przynajmniej jeden niepusty znak.
    $filename  = rtrim(substr($filename, 0, $pos)) . $extension;    

    // zastap potencjalnie problematyczne znaki podkresleniami
    $filename  = preg_replace('/[^ -_.a-z0-9]/i', '_', $filename);

    return $filename;
} // }}}

/**
 * @param object &$file
 * @param string $filename
 * @param string &$errmsg       OPTIONAL
 * @return bool
 */
function scholar_rename_file(&$file, $filename, &$errmsg = null)
{
    $errmsg   = null;
    $filename = scholar_sanitize_filename($filename);

    if ($filename == $file->filename) {
        // nowa nazwa pliku jest taka sama jak stara, nie ustawiaj bledu
        return false;
    }

    $filepath = scholar_file_path($filename);

    if (file_exists($filepath)) {
        $errmsg = t('A file named %filename already exists.', array('%filename' => $filename));
        return false;
    }

    if (@rename(scholar_file_path($file->filename), $filepath)) {
        $file->filename = $filename;
        db_query("UPDATE {scholar_files} SET filename = '%s' WHERE id = %d", $filename, $file->id);
        return true;
    }

    $errmsg = t('Unable to rename the file %from to %to.', array('%from' => $file->filename, '%to' => $filename));
    return false;
}

/**
 * @param int|array $file_id    albo numeryczny identyfikator pliku, albo
 *                              tablica z warunkami wyszukiwania
 * @param bool $redirect        czy zgłosić błąd i przekierować do listy
 *                              plików, jeżeli plik nie został znaleziony
 */
function scholar_fetch_file($file_id, $redirect = false) // {{{
{
    $cond = array();

    if (is_array($file_id)) {
        foreach ($file_id as $key => $value) {
            $cond[] = db_escape_table($key) . " = '" . db_escape_string($value) . "'";
        }
    } else {
        $cond[] = "id = " . intval($file_id);
    }

    $cond  = implode(" AND ", $cond);
    $query = db_query("SELECT * FROM {scholar_files} WHERE " . $cond);
    $row   = db_fetch_object($query);

    if (empty($row) && $redirect) {
        drupal_set_message(t('Invalid file id supplied (%id)', array('%id' => $file_id)), 'error');
        drupal_goto('scholar/files');
        exit;
    }

    return $row;
} // }}}

/**
 * Policz ile jest rekordów wiążących ten plik z węzłami.
 *
 * @param int $file_id          identyfikator pliku
 * @return int
 */
function scholar_file_count_attachments($file_id) // {{{
{
    $query = db_query("SELECT COUNT(*) AS cnt FROM {scholar_attachments} WHERE file_id = %d", $file->id);
    $row   = db_fetch_array($query);

    return intval($row['cnr']);
} // }}}

/**
 * Usuwa plik z bazy danych i dysku.
 *
 * @param object &$file         obiekt reprezentujący plik
 */
function scholar_delete_file(&$file) // {{{
{
    db_query("DELETE FROM {scholar_files} WHERE id = %d", $file->id);
    @unlink(scholar_file_path($file->filename));
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
 * Lista plików. Przeznaczona tylko dla okienek i ramek. Bezposredni
 * dostęp jest niewskazany.
 *
 * @return string
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

    if ($row = scholar_fetch_file(array('md5sum' => $md5))) {
        $errors[] = t('This file aready exists in the database (%filename)', array('%filename' => $row->filename)); 
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

    if ($filename = scholar_sanitize_filename($file->filename)) {
        $file->filename = $filename;
        $file->destination = dirname($file->destination) . '/' . $filename;
    } else {
        $errors[] = t('Invalid file name');
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

/**
 * Obsługa przesyłania pliku.
 */
function scholar_file_upload_form_submit() // {{{
{
    $validators = array(
        'scholar_file_validate_md5sum'    => array(),
        'scholar_file_validate_filename'  => array(),
        'scholar_file_validate_extension' => array(),
    );

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

        drupal_set_message(t('File uploaded successfully'));
        drupal_goto('scholar/files');
    }
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

    $pos       = strrpos($file->filename, '.');
    $filename  = substr($file->filename, 0, $pos);
    $extension = substr($file->filename, $pos);

    scholar_add_css();

    $uploader = user_load(intval($file->user_id));

    $form = array('#file' => $file);
    $form['properties'] = array(
        '#type' => 'fieldset',
        '#title' => t('Properties'),
        '#attributes' => array('class' => 'scholar'),
    );

    $url = url(scholar_file_path($file->filename), array('absolute' => true));
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

    // dodaj taby jezeli dostepny jest modul tabs
    if (function_exists('drupal_add_tab')) {
        drupal_add_tab(l(t('List'), 'scholar/files'));
        drupal_add_tab(l(t('Edit'), 'scholar/files/edit/' . $file->id), array('class' => 'active'));

        // zezwol na usuniecie plitu tylko wtedy, jezeli nie ma stron odwolujacych
        // sie do tego pliku
        if (0 == $refcount) {
            drupal_add_tab(l(t('Delete'), 'scholar/files/delete/' . $file->id));
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
        $src = $file->filename;
        $pos = strrpos($src, '.');
        $ext = substr($src, $pos);
        $dst = $form_state['values']['filename'] . $ext;

        if (scholar_rename_file($file, $dst, $error)) {
            drupal_set_message(t('File %from renamed successfully to %to', array('%from' => $src, '%to' => $file->filename)));
            return drupal_goto('scholar/files/edit/' . $file->id);
        }

        form_set_error('', $error);
    }

    // nie przekierowywuj, bo wystapily bledy formularza
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

    if ($file) {
        // sprawdzamy dokladnie faktyczna liczbe odwolan do tego pliku,
        // na wypadek gdyby refcount zawieralo niepoprawna wartosc

        if (scholar_file_count_attachments($file->id)) {
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

    drupal_goto('scholar/files');
} // }}}

