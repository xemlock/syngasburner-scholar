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
 * Przekształca podaną nazwę pliku na czysty ASCII, wykonuje walidację nazwy
 * pliku oraz rozszerzenia oraz usuwaja potencjalnie problematyczne znaki.
 *
 * @return false|string         false jeżeli podana nazwa pliku nie może
 *                              zostać przekształcona do bezpiecznej postaci
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

    // zastap potencjalnie problematyczne znaki podkresleniami, uwaga
    // na pozycje myslnika w wyrazeniu regularnym, musi byc zaraz za ^
    $filename  = preg_replace('/[^-_. a-z0-9]/i', '_', $filename);

    return $filename;
} // }}}

/**
 * Zmienia nazwę pliku.
 *
 * @param object &$file         obiekt reprezentujący plik
 * @param string $filename      nowa nazwa pliku
 * @param string &$errmsg       OPTIONAL komunikat o błędzie
 * @return bool                 false jeżeli zmiana nazwy pliku nie powiodła
 *                              się, $errmsg zawiera ewentualny komunikat 
 *                              o błędzie
 */
function scholar_rename_file(&$file, $filename, &$errmsg = null) // {{{
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
} // }}}

/**
 * Pobiera z bazy danych rekord pliku.
 *
 * @param int|array $file_id    albo numeryczny identyfikator pliku, albo
 *                              tablica z warunkami wyszukiwania
 * @param bool $redirect        czy zgłosić błąd i przekierować do listy
 *                              plików, jeżeli plik nie został znaleziony
 * @return object
 */
function scholar_fetch_file($file_id, $redirect = false) // {{{
{
    if (is_array($file_id)) {
        $cond = scholar_db_where($file_id);
    } else {
        $cond = "id = " . intval($file_id);
    }

    $query = db_query("SELECT * FROM {scholar_files} WHERE " . $cond);
    $row   = db_fetch_object($query);

    if (empty($row) && $redirect) {
        drupal_set_message(t('Invalid file id supplied (%id)', array('%id' => $file_id)), 'error');
        drupal_goto(scholar_admin_path('file'));
        exit;
    }

    return $row;
} // }}}

/**
 * Pobiera listę załączników dla obiektu o podanym identyfikatorze
 * znajdujęcego się w podanej tabeli. Jeżeli podano język, zwrócone 
 * zostaną tylko załączniki dla danego języka.
 * @param int $object_id
 * @param string $table_name
 * @return array
 */
function scholar_fetch_files($object_id, $table_name, $language = null) // {{{
{
    $conds = array(
        'table_name' => $table_name,
        'object_id'  => $object_id,
    );

    if (null !== $language) {
        $language = strval($language);
        $conds['language'] = $language;
    }

    $where = scholar_db_where($conds);
    $query = db_query("SELECT * FROM {scholar_attachments} a JOIN {scholar_files} f ON a.file_id = f.id WHERE " . $where . " ORDER BY language, weight");
    $rows = array();

    while ($row = db_fetch_array($query)) {
        $rows[$row['language']][] = $row;
    }

    if (null !== $language) {
        return isset($rows[$language]) ? $rows[$language] : array();
    }

    return $rows;
} // }}}

/**
 * Ustawia załączniki dla obiektu z podanej tabeli, wszystkie poprzednie
 * powiązania tego obiektu z załącznikami zostaną usunięte.
 * @param int $object_id
 * @param string $table_name
 * @param array $attachments Taka jak wartosć z elementu scholar_attachment_manager, czyli tablica [language][file_id] => (id, label)
 * @return int liczba dodanych rekordów
 */
function scholar_save_files($object_id, $table_name, $attachments) // {{{
{
    // wez pod uwage tylko identyfikatory istniejacych plikow, w tym celu
    // dokonaj ekstrakcji identyfikatorow plikow
    $ids = array();

    foreach ($attachments as $language => $files) {
        foreach ($files as $file) {
            $ids[intval($file['id'])] = false;
        }
    }

    // zaznacz, ktore z wyekstrahowanych identyfikatorow plikow sa poprawne
    $where = scholar_db_where(array('id' => array_keys($ids)));
    $query = db_query("SELECT id FROM {scholar_files} WHERE " . $where);

    while ($row = db_fetch_array($query)) {
        $ids[$row['id']] = true;
    }

    // usun aktualne dowiazania, zeby nie kolidowaly z nowo wstawionymi
    db_query("DELETE FROM {scholar_attachments} WHERE table_name = '%s' AND object_id = %d", $table_name, $object_id);

    // przechowuje aktualny rekord do przekazania funkcji drupal_write_record
    $record = new stdClass;
    $count = 0;

    foreach ($attachments as $language => $files) {
        $saved = array();

        foreach ($files as $file) {
            $file_id = intval($file['id']);

            // Zapisz tylko powiazania z poprawnymi identyfikatorami plikow.
            // Pilnuj, zeby nie bylo zduplikowanych plikow w obrebie jezyka,
            // bo baza danych zglosi duplikat klucza glownego.
            if (!$ids[$file_id] || isset($saved[$file_id])) {
                continue;
            }

            $record->file_id    = $file_id;
            $record->table_name = $table_name;
            $record->object_id  = $object_id;
            $record->label      = $file['label'];
            $record->language   = $language;
            $record->weight     = $file['weight'];

            if (drupal_write_record('scholar_attachments', $record)) {
                $saved[$file_id] = true;
            }
        }

        $count += count($saved);
    }

    return $count;
} // }}}


/**
 * Liczy ile jest rekordów wiążących ten plik z rekordami tabel scholar_people
 * i scholar_objects.
 *
 * @param object &$file         obiekt reprezentujący plik
 * @return int
 */
function scholar_file_refcount(&$file) // {{{
{
    $query = db_query("SELECT COUNT(*) AS cnt FROM {scholar_attachments} WHERE file_id = %d", $file->id);
    $row   = db_fetch_array($query);

    return intval($row['cnr']);
} // }}}

/**
 * Pobiera z bazy danych listę rekordów z tabel scholar_people 
 * i scholar_objects odwołujących się do tego pliku.
 *
 * @param object &$file         obiekt reprezentujący plik
 * @param array $header         tablica kolumn tabeli w postaci opisanej
 *                              w theme_table(). Dopuszczalne nazwy kolumn:
 *                              row_type (konkatenacja table_name.subtype),
 *                              table_name, object_id, title, label, language
 * @return array
 */
function scholar_file_fetch_dependent_rows(&$file, $header = null) // {{{
{
    $sqlsort = scholar_tablesort_sql($header, array('row_type', 'table_name', 'object_id', 'title', 'label', 'language'));

    $query = db_query("SELECT 'people' AS row_type, table_name, NULL AS subtype, object_id, CONCAT(first_name, ' ', last_name) AS title, label, language FROM {scholar_people} p JOIN {scholar_attachments} a ON a.table_name = 'people' AND a.object_id = p.id WHERE a.file_id = %d UNION ALL SELECT CONCAT('generics.', subtype) AS row_type, table_name, subtype, object_id, title, label, language FROM {scholar_generics} o JOIN {scholar_attachments} a ON a.table_name = 'generics' AND a.object_id = o.id WHERE a.file_id = %d" . $sqlsort, $file->id, $file->id);

    $rows = array();
    while ($row = db_fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
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

/*
 * Lista plików z możliwością wyboru. Przeznaczona tylko dla okienek i ramek. 
 * Bezposredni dostęp jest niewskazany.
 *
 * @return string
 */
function scholar_file_select() // {{{
{
    scholar_add_js();
    scholar_add_css();

    $files = array();

    $query = db_query("SELECT * FROM {scholar_files} ORDER BY filename");
    while ($row = db_fetch_array($query)) {
        $files[] = $row;
    }

    ob_start();
?>
<script type="text/javascript">$(function() {
new Scholar.ItemPicker('#items', '{ filename }', <?php echo drupal_to_js($files) ?>, {
    filterSelector: '#name-filter',
    filterKey: 'filename',
    filterReset: '#reset-filter',
    showOnInit: false,
    emptyMessage: 'No files found'
});
});
</script>
<style type="text/css">
#items li.selected {
  font-weight: bold;
}
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
    Filtruj: <input type="text" id="name-filter" placeholder="<?php echo 'Search file'; ?>"/><button type="button" id="reset-filter">Wyczyść</button>
Dwukrotne kliknięcie zaznacza element
<hr/>
<div id="items"></div>
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
        $errors[] = t('This file aready exists in the database as %filename', array('%filename' => $row->filename)); 
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
    // W zasadzie jest to kopia file_validate_extensions(), z tym, ze
    // sprawdzanie poprawnosci rozszerzenia dotyczy takze plikow wyslanych
    // przez admina (uid = 1).

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
        // Z pol 'filename' i 'destination' korzysta file_save_upload()
        // podczas zapisu przeslanego pliku.
        $file->filename = $filename;
        $file->destination = dirname($file->destination) . '/' . $filename;
    } else {
        $errors[] = t('Invalid file name');
    }

    return $errors;
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
            scholar_add_js();
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

    scholar_add_css();

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

    if ($rows) {
        if (count($rows) != $refcount) {
            $rows[] = array(
                array(
                    'data' => format_plural($refcount,
                        'Expected %refcount file, but found %count. Database corruption detected.',
                        'Expected %refcount files, but found %count. Database corruption detected.',
                        array('%refcount' => $refcount, '%count' => count($rows))
                    ),
                    'colspan' => 3,
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
        drupal_add_tab(t('List'), scholar_admin_path('file'));
        drupal_add_tab(t('Edit'), scholar_admin_path('file/edit/' . $file->id), array('class' => 'active'));

        // zezwol na usuniecie plitu tylko wtedy, jezeli nie ma stron 
        // odwolujacych sie do tego pliku
        if (0 == $refcount) {
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

