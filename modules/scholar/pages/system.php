<?php

function scholar_pages_system_index()
{
    $output = '';
    $output .= scholar_oplink(t('Database schema'), 'system.schema');
    $output .= '<br/>';
    $output .= scholar_oplink(t('Settings'), 'settings');
    $output .= '<br/>';
    $output .= scholar_oplink(t('Filesystem'), 'system.files');
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

class scholar_filesystem_scanner
{
    protected $_files = array();
    protected $_dirty = false;
    protected $_ls = array();
    protected $_dir;

    public function __construct()
    {
        register_shutdown_function(array($this, 'shutdown'));
        if ($data = cache_get(__CLASS__)) {
            $this->_files = (array) $data->data;
        }

        $this->_dir = scholar_file_path();
        foreach (scandir($this->_dir) as $entry) {
            if (!is_file($this->_dir . '/' . $entry)) {
                continue;
            }
            $this->_ls[$entry] = true;
        }
    }

    public function scan()
    {
        foreach ($this->_ls as $entry => $ignore) {
            if (isset($this->_files[$entry])) {
                continue;
            }
            $path = $this->_dir . '/' . $entry;
            $this->_dirty = true;

            // oznacz jako przetwarzane, jezeli nie uda sie to trudno
            $this->_files[$entry] = false;

            $md5 = md5_file($path);
            $this->_files[$entry] = array(
                'md5sum' => $md5,
                'mtime'  => filemtime($path),
                'ctime'  => filectime($path),
                'size'   => filesize($path),
            );
        }
    }

    public function clear($entry = null)
    {
        if (null === $entry) {
            if ($this->_files) {
                $this->_files = array();
                $this->_dirty = true;
            }
        } else {
            if (isset($this->_files[$entry])) {
                unset($this->_files[$entry]);
                $this->_dirty = true;
            }
        }
    }

    // Podczas przekroczenia limitu czasu zostaja wywolane funkcje zamykające.
    // http://www.php.net/manual/en/function.set-time-limit.php#69957
    public function shutdown()
    {
        if ($this->_dirty) {
            ksort($this->_files);
            cache_set(__CLASS__, $this->_files);
        }
    }

    public function status()
    {
        if (empty($this->_ls)) {
            return 1;
        }

        $count = 0;
        foreach ($this->_ls as $entry => $ignore) {
            if (isset($this->_files[$entry]) && is_array($this->_files[$entry])) {
                ++$count;
            }
        }
        return $count / count($this->_ls);
    }

    public function getFiles()
    {
        return $this->_files;
    }
}

function scholar_pages_system_files() // {{{
{
    /*$s = new scholar_filesystem_scanner;
    if (isset($_GET['clear'])) {
        $s->clear($_GET['clear']);
    }
    $s->scan();
    p($s->status());
    p($s->getFiles());
     */

    // shutdown zapis cache'u
    if ($data = cache_get('scholar_file_import')) {
        $cache = (array) $data->data;
    } else {
        $cache = array();
    }

    $dir = scholar_file_path();
    $table = array();
    foreach (scandir($dir) as $entry) {
        $path = $dir . '/' . $entry;
        if (!is_file($path)) {
            continue;
        }

        if (isset($cache[$entry])) {
            if (($cache[$entry]['size'] != filesize($path))
                || ($cache[$entry]['ctime'] != filectime($path))
                || ($cache[$entry]['mtime'] != filemtime($path))
            ) {
                unset($cache[$entry]);
            }
        }

        if (!isset($cache[$entry])) {
            $cache[$entry] = array(
                'md5sum' => md5_file($path),
                'size'   => filesize($path),
                'ctime'  => filectime($path),
                'mtime'  => filemtime($path),
            );
        }

        $result = array();
        $res = db_fetch_array(scholar_files_recordset(array('md5sum' => $cache[$entry]['md5sum'])));
        if ($res) {
            if ($res['filename'] == $entry) {
                $result['added on'] = date('Y-m-d H:i:s', $res['create_time']);
            } else {
                $result['duplicate of'] = $res['filename'];
            }
        } else {
            // nie ma w bazie trzeba dodac
            $result['pliku nie ma w bazie'] = $cache[$entry]['md5sum'];

            global $user;
            $file = new stdClass;
            $file->md5sum   = $cache[$entry]['md5sum'];
            $file->filename = $entry;
            $file->id       = null;
            $file->mimetype = file_get_mimetype($entry); // detect mimetype
            $file->size     = $cache[$entry]['size'];
            $file->user_id  = $user->uid;
            $file->create_time = time();

            if ($success = scholar_db_write_record('scholar_files', $file)) {
                $result['dodano do bazy id:'] = $file->id;
            } else {
                $result['nie udalo sie dodac...'] = '';
            }
        }

        $table[$entry] = $result;
    }
    cache_set('scholar_file_import', $cache);
    p($table);

    /*
    $res = ();
    $files = array();

    while ($row = db_fetch_array($res)) {
        $filename = $row['filename'];
        $row['file_exists'] = is_file($dir . '/' . $filename);
        
    
    }*/
} // }}}

