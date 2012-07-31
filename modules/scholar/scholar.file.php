<?php

function scholar_file_dir($filename = null) // {{{
{
    $path = conf_path() . '/files/scholar/' . ltrim($filename, '/');
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
 * Akcja przeznaczona tylko dla okienek i ramek. Bezposredni
 * dostęp jest niewskazany.
 */
function scholar_file_list()
{
    $files = array();

    if (db_table_exists('files')) {
        $query = db_query("SELECT * FROM {files} ORDER BY filename");
        while ($row = db_fetch_array($query)) {
            $files[$row['fid']] = $row;
        }
    }

    $html = print_r($files, 1);

    $html .= '<script type="text/javascript">window.console&&console.log(window); if (window !== window.parent) document.body.innerHTML += \'POZDROWIENIA OD POTOMKA\';</script><a href="#!" onclick="window.open(\''.url('scholar/files').'\', \'_blank\');return false">Open</a>';

    return scholar_render($html, true);
}

function scholar_file_select()
{}

function scholar_file_upload()
{}

function scholar_file_edit()
{}

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
