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
}

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
