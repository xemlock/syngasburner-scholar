<?php

function scholar_pages_system_index()
{
    $output = '';
    $output .= scholar_oplink(t('Database schema'), 'system', '/schema');
    $output .= '<br/>';
    $output .= scholar_oplink(t('File import'), 'system', '/file-import');
    $output .= '<br/>';
    $output .= scholar_oplink(t('Settings'), 'settings');
    return $output;
}

function scholar_pages_system_schema() // {{{
{
    $html = '';

    $tables = array();

    foreach (drupal_get_schema() as $name => $table) {
        if (strncmp('scholar_', $name, 8)) {
            continue;
        }
        $tables[$name] = $table;
    }

    ksort($tables);

    foreach ($tables as $name => $table) {
        $html .= db_prefix_tables(
            implode(";\n", db_create_table_sql($name, $table)) . ";\n"
        );
        $html .= "\n";
    }

    return '<pre><code class="sql">' . $html . '</code></pre>';
} // }}}

/**
 * Analizuje zawartość katalogu z plikami i dodaje pliki, których nie
 * ma w bazie danych. Duplikaty plików istniejących w bazie są ignorowane.
 *
 * @return string
 */
function scholar_pages_system_file_import() // {{{
{
    global $user;

    $thead = array(
        array('data' => t('Filename')),
        array('data' => t('Status')),
    );
    $tbody = array();

    // informacje o plikach umieszczonych w katalogu plikow
    $files = new scholar_cached_array('file_import');
    $dir = scholar_file_path();

    $added = 0;

    // jezeli podano nazwe pliku wymus odswiezenie informacji o nim
    if (isset($_GET['retry']) && isset($files[$_GET['retry']])) {
        unset($files[$_GET['retry']]);
        return scholar_goto($_GET['q']);
    }

    foreach (scandir($dir) as $entry) {
        $path = $dir . '/' . $entry;
        if (!is_file($path)) {
            continue;
        }

        if (isset($files[$entry]) && is_array($files[$entry])) {
            $file  = &$files[$entry];
            $valid = ($file['size']  == filesize($path))
                     && ($file['ctime'] == filectime($path))
                     && ($file['mtime'] == filemtime($path));
            if (!$valid) {
                // plik zostal podmieniony na inny, trzeba wyliczyc
                // jeszcze raz sume kontrolna MD5
                unset($files[$entry], $file);
            }
        }

        if (!isset($files[$entry])) {
            // na wypadek przekroczenia czasu, zaznacz plik jako przetwarzany,
            // ale nieukonczony
            $files[$entry] = false;
            // ta instrukcja moze nie dojsc do skutku, jezeli md5_file zabierze
            // za duzo czasu
            $files[$entry] = array(
                'md5sum' => md5_file($path),
                'size'   => filesize($path),
                'ctime'  => filectime($path),
                'mtime'  => filemtime($path),
            );
        }

        $file = &$files[$entry];

        $class = '';
        $tr = array(
            $entry, // 0: nazwa pliku
            '',     // 1: status importu
        );

        if (false === $file) {
            $class = 'error';
            $tr[1] = t('Error: Unable to analyze file. <a href="!retry">Click to retry</a>', array(
                '!retry' => url($_GET['q'], array('query' => array('retry' => $entry))),
            ));
        } else {
            $file_by_md5 = db_fetch_array(scholar_files_recordset(array('md5sum' => $file['md5sum'])));
            if ($file_by_md5) {
                if ($file_by_md5['filename'] == $entry) {
                    // zgodnosc nazw plikow i sumy MD5, plik jest dodany do bazy
                    continue;
                } else {
                    $class = 'error';
                    $tr[1] = t('Error: Duplicate of file !file', array(
                        '!file' => scholar_oplink($file_by_md5['filename'], 'files', '/edit/%d', $file_by_md5['id']),
                    ));
                }
            } else {
                // w bazie nie ma pliku o podanej sumie MD5, trzeba sprawdzic, czy istnieje
                // plik o takiej samej nazwie - jezeli tak, to baza rozsynchronizowala
                // sie w plikami na dysku.
                $file_by_name = db_fetch_array(scholar_files_recordset(array('filename' => $entry)));
                if ($file_by_name) {
                    // inny plik o tej nazwie jest w bazie danych
                    $class = 'error';
                    $tr[1] = t('Error: File of this name already exists in the database (file id: %id).', array('%id' => $file_by_name['id']));
                } else {
                    // nie ma w bazie trzeba dodac
                    $record = new stdClass;
                    $record->md5sum   = $file['md5sum'];
                    $record->filename = $entry;
                    $record->mimetype = file_get_mimetype($entry); // detect mimetype
                    $record->size     = $file['size'];
                    $record->user_id  = $user->uid;
                    $record->create_time = time();

                    if ($success = scholar_db_write_record('scholar_files', $record)) {
                        $class = 'success';
                        $tr[1] = t('Successfully added to database (file id: %id).', array('%id' => $record->id));
                        ++$added;
                    } else {
                        $class = 'error';
                        $tr[1] = t('Error: Unable to save file to the database.');
                    }
                }
            }
        }
        $tbody[] = array('data' => $tr, 'class' => $class);
    }

    if (empty($tbody)) {
        $tbody[] = array(
            array('data' => t('No more files to import'), 'colspan' => 2),
        );
    }

    if ($added) {
        drupal_set_message(format_plural($added, 'Successfully imported one file.', 'Successfully imported @count files.'));
    }

    $help = '<div class="help">'
          . t('Upload new files via FTP or SCP to %dir directory under Drupal installation and <a href="!url">reload</a> this page.', array('%dir' => rtrim($dir, '/'), '!url' => url($_GET['q'])))
          . '</div>';

    $output = $help . theme_scholar_table($thead, $tbody);

    return $output;
} // }}}

