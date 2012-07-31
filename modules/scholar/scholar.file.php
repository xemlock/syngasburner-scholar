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
