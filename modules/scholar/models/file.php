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
 * @return false|string
 *     false jeżeli podana nazwa pliku nie może zostać przekształcona do
 *     bezpiecznej postaci
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
 * @return bool
 *    false jeżeli zmiana nazwy pliku nie powiodła się, $errmsg zawiera
 *    ewentualny komunikat o błędzie
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
 * @param int|array $file_id
 *     albo numeryczny identyfikator pliku, albo tablica z warunkami
 *     wyszukiwania
 * @param string $redirect
 *     jeżeli podano, ścieżka do której nastąpi przekierowanie, jeżeli plik
 *     nie został znaleziony
 * @return object
 */
function scholar_fetch_file($file_id, $redirect = null) // {{{
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
        drupal_goto($redirect);
        exit;
    }

    return $row;
} // }}}

/**
 * Pobiera listę załączników dla obiektu o podanym identyfikatorze
 * znajdujęcego się w podanej tabeli. Jeżeli podano język, zwrócone 
 * zostaną tylko załączniki dla danego języka.
 *
 * @param int $row_id
 * @param string $table_name
 * @return array
 */
function scholar_fetch_files($row_id, $table_name, $language = null) // {{{
{
    $conds = array(
        'table_name' => $table_name,
        'row_id'     => $row_id,
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
 *
 * @param int $row_id
 * @param string $table_name
 * @param array $attachments
 *     taka jak wartosć z {@see scholar_element_files}, czyli tablica 
 *     [language][file_id] => (id, label)
 * @return int liczba dodanych rekordów
 */
function scholar_save_files($row_id, $table_name, $attachments) // {{{
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
    db_query("DELETE FROM {scholar_attachments} WHERE table_name = '%s' AND row_id = %d", $table_name, $row_id);

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
            $record->row_id  = $row_id;
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
 * Usuwa powiązania plików z rekordem wybranej tabeli. Funkcja usuwa jedynie
 * powiązania, nie usuwa plików.
 *
 * @param int $row_id
 * @param string $table_name
 */
function scholar_delete_files($row_id, $table_name) // {{{
{
    db_query("DELETE FROM {scholar_attachments} WHERE table_name = '%s' AND row_id = %d", $table_name, $row_id);
} // }}}

/**
 * Liczy ile jest rekordów powiązanych z tym plikiem.
 *
 * @param object &$file
 *     obiekt reprezentujący plik
 * @return int
 */
function scholar_file_refcount(&$file) // {{{
{
    $query = db_query("SELECT COUNT(*) AS cnt FROM {scholar_attachments} WHERE file_id = %d", $file->id);
    $row   = db_fetch_array($query);

    return intval($row['cnr']);
} // }}}

/**
 * Pobiera z bazy danych listę rekordów odwołujących się do tego pliku.
 *
 * @param object &$file
 *     obiekt reprezentujący plik
 * @param array $header
 *     tablica kolumn tabeli w postaci opisanej w theme_table(). Dopuszczalne
 *     nazwy kolumn: row_type (konkatenacja table_name.subtype), table_name,
 *     row_id, title, label, language
 * @return array
 */
function scholar_file_fetch_dependent_rows(&$file, $header = null) // {{{
{
    $sqlsort = scholar_tablesort_sql($header, array('row_type', 'table_name', 'row_id', 'title', 'label', 'language'));

    $query = db_query("SELECT 'people' AS row_type, table_name, NULL AS subtype, row_id, CONCAT(first_name, ' ', last_name) AS title, label, language FROM {scholar_people} p JOIN {scholar_attachments} a ON a.table_name = 'people' AND a.row_id = p.id WHERE a.file_id = %d UNION ALL SELECT CONCAT('generics.', subtype) AS row_type, table_name, subtype, row_id, title, label, language FROM {scholar_generics} o JOIN {scholar_attachments} a ON a.table_name = 'generics' AND a.row_id = o.id WHERE a.file_id = %d" . $sqlsort, $file->id, $file->id);

    $rows = array();
    while ($row = db_fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
} // }}}

/**
 * Usuwa plik wraz z powiązaniami z bazy danych oraz z dysku.
 *
 * @param object &$file
 *     obiekt reprezentujący plik
 */
function scholar_delete_file(&$file) // {{{
{
    db_query("DELETE FROM {scholar_attachments} WHERE file_id = %d", $file->id);
    db_query("DELETE FROM {scholar_files} WHERE id = %d", $file->id);
    @unlink(scholar_file_path($file->filename));

    $file->id = null;
} // }}}

/**
 * Zwraca listę rozszerzeń plików, które mogą zostać przesłane.
 *
 * @return string
 *     rozszerzenia plików oddzielone spacjami
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
 * @param object &$file
 *     obiekt reprezentujący plik
 * @return array
 *     lista błędów walidacji
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
 * @param object &$file
 *     obiekt reprezentujący plik
 * @return array
 *     lista błędów walidacji
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
 * @param object &$file
 *     obiekt reprezentujący plik
 * @return array
 *     lista błędów walidacji
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

// vim: fdm=marker
