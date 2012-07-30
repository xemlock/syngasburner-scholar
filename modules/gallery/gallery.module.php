<?php defined('DRUPAL_BOOTSTRAP_PATH') || exit;

// NIE ODPALAC menu_rebuild W MODULE !!!
// WALI TO CALE MENU!!!

// menu_cache_clear_all();
// menu_rebuild();

function gallery_help($path, $arg) {
}
function xxx() { 
  return 'xxx';
}

function trace($function) { return;
  $t = explode(' ', microtime());
  $t[1] -= mktime(date('H'), date('i'), 0);
  printf("<div style='font-family:monospace;font-size:8pt;'>%.3f: $function</div>\n", $t[0] + $t[1]); 
}

define('GALLERY_MENU_GALLERY',          'admin/content/gallery');
define('GALLERY_MENU_GALLERY_ADD',      'admin/content/gallery/add');
define('GALLERY_MENU_GALLERY_EDIT',     'admin/content/gallery/edit');
define('GALLERY_MENU_GALLERY_DELETE',   'admin/content/gallery/delete');
define('GALLERY_MENU_GALLERY_LIST',     'admin/content/gallery/list');
define('GALLERY_MENU_GALLERY_DETAILS',  'admin/content/gallery/details');
define('GALLERY_MENU_IMAGE',            'admin/content/gallery/image');
define('GALLERY_MENU_IMAGE_ADD',        'admin/content/gallery/image/add');
define('GALLERY_MENU_IMAGE_EDIT',       'admin/content/gallery/image/edit');
define('GALLERY_MENU_IMAGE_DELETE',     'admin/content/gallery/image/delete');
define('GALLERY_MENU_IMAGE_LIST',       'admin/content/gallery/image/list');
define('GALLERY_MENU_IMAGE_RESULTS',    'admin/content/gallery/image/search-results');

function gallery_menu() {
  trace(__FUNCTION__);

  $items[GALLERY_MENU_GALLERY] = array(
    'title' => 'Galleries',
    'description' => 'Manage galleries and images.',
    'access arguments' => array('use gallery'),
    'page callback' => 'gallery_list',
    'file' => 'gallery.gallery.php',   
  );
  $items[GALLERY_MENU_GALLERY_LIST] = array(
    'title' => t('Gallery list'),
    //'access arguments' => array('use gallery'),
    //'page callback' => 'gallery_list',
    'type' => MENU_DEFAULT_LOCAL_TASK,
    //'file' => 'gallery.gallery.php',
  );
  $items[GALLERY_MENU_GALLERY_ADD] = array(
    'title' => t('Add gallery'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('gallery_add_form'),
    'type' => MENU_LOCAL_TASK,
    'access arguments' => array('use gallery'),
    'file' => 'gallery.gallery.php',
  );
  $items[GALLERY_MENU_GALLERY_EDIT . '/%'] = array(
    'title' => t('Edit gallery'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('gallery_edit_form'),
    'type' => MENU_CALLBACK,
    'access arguments' => array('use gallery'),
    'file' => 'gallery.gallery.php',
    'parent' => GALLERY_MENU_GALLERY,
  );
  $items[GALLERY_MENU_GALLERY_DELETE . '/%'] = array(
    'title' => t('Delete gallery'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('gallery_delete_form'),
    'type' => MENU_CALLBACK,
    'access arguments' => array('use gallery'),
    'file' => 'gallery.gallery.php',
    'parent' => GALLERY_MENU_GALLERY,
  );

  $items[GALLERY_MENU_GALLERY_DETAILS] = array(
    'title' => t('Gallery details'),
    'page callback' => 'gallery_details',
    'type' => MENU_CALLBACK,
    'access arguments' => array('use gallery'),
    'file' => 'gallery.gallery.php',
  );
  $items[GALLERY_MENU_GALLERY_DETAILS . '/%'] = $items[GALLERY_MENU_GALLERY_DETAILS];
  
  
  $items[GALLERY_MENU_IMAGE] = array(
    'title' => t('Images'),
    'description' => 'Manage uploaded images.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('gallery_image_add_form'),
    'type' => MENU_LOCAL_TASK,
    'access arguments' => array('use gallery'),
    'file' => 'gallery.image.add.php',
  );
  $items[GALLERY_MENU_IMAGE_ADD] = array(
    'title' => t('Add image'),
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'parent' => GALLERY_MENU_IMAGE,
  );
  $items[GALLERY_MENU_IMAGE_LIST] = array(
    'title' => t('Image list'),
    'page callback' => 'gallery_image_list',
    'access arguments' => array('use gallery'),
    'type' => MENU_LOCAL_TASK,
    'file' => 'gallery.image.list.php',
    'parent' => GALLERY_MENU_IMAGE,
  );
  $items[GALLERY_MENU_IMAGE_RESULTS] = array(
    'page callback' => 'gallery_search_results',
    'access arguments' => array('use gallery'),
    'type' => MENU_CALLBACK,
    'file' => 'gallery.image.list.php',
    'parent' => GALLERY_MENU_IMAGE,
  );
  
  $items[GALLERY_MENU_IMAGE_EDIT . '/%'] = array(
    'title' => t('Edit image'),
    'page callback' => 'gallery_image_edit',
    'access arguments' => array('use gallery'),
    'type' => MENU_CALLBACK,
    'file' => 'gallery.image.edit.php',
    'parent' => GALLERY_MENU_IMAGE,
  );
  $items[GALLERY_MENU_IMAGE_DELETE . '/%'] = array(
    'title' => t('Delete image'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('gallery_image_delete_form'),
    'access arguments' => array('use gallery'),
    'type' => MENU_CALLBACK,
    'file' => 'gallery.image.delete.php',
    'parent' => GALLERY_MENU_IMAGE,
  );

  // add arg numbers for paths containing '%'
  foreach ($items as $link => $item) {
    if (strpos($link, '%') === false) continue;
    $path = explode('/', $link);
    if (!isset($items[$link]['page arguments'])) {
      $items[$link]['page arguments'] = array();
    }
    for ($i = 0; $i < count($path); ++$i) {
      if (strpos($path[$i], '%') === false) continue;      
      $items[$link]['page arguments'][] = $i;
    }
  }
  
  return $items;
}

// jezeli dostajemy blad ze w l:348 includes/menu.inc
// powinien byc valid callback, to trzeba wymusic menu_rebuild();

// wyniki theme i theme_registry_alter sa cacheowane!!!
/*function gallery_theme() {
} */

function gallery_get_cover($gallery_id) { // {{{
  // get cover image for gallery
  return db_fetch_array(db_query("SELECT * FROM {image} WHERE gallery_id = %d ORDER BY weight LIMIT 1", $gallery_id));
} // }}}

function gallery_theme_registry_alter(&$theme_registry) { // {{{
  trace(__FUNCTION__);
  $theme_hook = 'page'; // hook name
  // get the path to this module
  $modulepath = drupal_get_path('module', 'gallery');
  // add the module path on top in the array of paths
  array_unshift($theme_registry[$theme_hook]['theme paths'], $modulepath);
} // }}}

function gallery_node_get_attachments($node) { // {{{
  if (!isset($node->gallery_load) || !$node->gallery_load) return;

  $title = '';
  $desc = '';
  $files = array();

  $lang = Langs::lang();
  $gallery = db_fetch_array(db_query("SELECT gallery_id, title, description FROM {gallery_data} WHERE gallery_id = %d AND lang = '%s'",
                  $node->gallery_id, $lang
             ));
  if ($gallery) {
    $title = $gallery['title'];
    $desc = $gallery['description'];
    $query = db_query("SELECT * FROM {image} i JOIN {image_data} d ON i.id = d.image_id WHERE i.gallery_id = %d AND d.lang = '%s' ORDER BY weight",
                  $gallery['gallery_id'], $lang);
    while ($row = db_fetch_array($query)) {
      $files[] = $row;
    }
  }
  return array(
           'title' => $title,
           'description' => $desc,
           'files' => $files,
         );
} // }}}

function gallery_preprocess_page(&$vars) { // {{{
  if (!strncmp($_GET['q'], GALLERY_MENU_GALLERY, strlen(GALLERY_MENU_GALLERY))) {
    $items = gallery_menu();
    $keys = array(GALLERY_MENU_GALLERY_ADD, GALLERY_MENU_GALLERY_LIST, GALLERY_MENU_IMAGE_ADD, GALLERY_MENU_IMAGE_LIST);
    $vars['tabs'] = '';
    foreach ($keys as $key) {
      $href = base_path() . $key;
      $title = @$items[$key]['title'];
      $class = $_GET['q'] == $key ? 'active' : '';
      $vars['tabs'] .= "<li class=\"$class\"><a href=\"$href\">$title</a></li>\n";
    }
  }
} // }}}

// node
function gallery_nodeapi($node, $op) { // {{{
  switch ($op) {
    case 'delete':
      // remove gallery-node link when removing node
      db_query("DELETE FROM {gallery_node} WHERE node_id = %d", $node->nid);
      break;

    case 'update':      
      db_query("DELETE FROM {gallery_node} WHERE node_id = %d", $node->nid);
      // intentional missing 'break'

    case 'insert':
      if ((int) $node->gallery_id) {
        db_query("INSERT INTO {gallery_node} (gallery_id, node_id, layout) VALUES (%d, %d, '%s')", 
            $node->gallery_id, $node->nid, $node->gallery_layout);
      }
      break;

    case 'load':
      $gallery_node = db_fetch_array(db_query("SELECT gallery_id, layout FROM {gallery_node} WHERE node_id = %d", $node->nid));
      $gallery_cover = gallery_get_cover($gallery_node['gallery_id']);
      $node->gallery_id = (string) @$gallery_node['gallery_id'];
      $node->gallery_cover = (string) @$gallery_cover['id'];
      $node->gallery_layout = (string) @$gallery_node['layout'];
      $node->gallery_load = false;
      break;

    case 'view':
      // load gallery only when viewing node
      $node->gallery_load = true;
      break;
  }
} // }}}

// node form alter
function gallery_form_alter(&$form, &$form_state, $form_id) { // {{{
  global $user;

  $node = $form['#node'];
  $node_types = node_get_types('names');
  $node_type = in_array($node->type, array_keys($node_types));
  
  if ($node_type && preg_match('/node_form$/i', $form_id)) {
    $form['body_field']['gallery_attachment'] = array(
      '#type' => 'fieldset',
      '#title' => t('Gallery attachment'),
      '#collapsible' => true,
      '#collapsed' => true,
    );
    $form['body_field']['gallery_attachment']['gallery_id'] = array(
      '#type' => 'select',
      '#title' => t('Gallery'),
      '#description' => t('Attach gallery to this node. Only one gallery can be attached.'),
      '#options' => gallery_galleries_options(),
      '#default_value' => $node->gallery_id,
    );
    $form['body_field']['gallery_attachment']['gallery_layout'] = array(
      '#type' => 'select',
      '#title' => t('Layout'),
      '#description' => t('Choose which layout should be applied when displaying attached gallery on page.'),
      '#options' => array('vertical' => t('vertical'), 'horizontal' => t('horizontal')),
      '#default_value' => $node->gallery_layout,
    );
  }
} // }}}

function gallery_get_ext($file) { // {{{
  $ext = '';
  if ($fh = @fopen($file, 'r')) {
    $head = fread($fh, 32);
    fclose($fh);
    if (!strncmp($head, "\xff\xd8", 2)) {
      $ext = "jpg";
    } else if(!strncmp($head, "\x42\x4d", 2)) {
      $ext = "bmp";
    } else if(!strncmp($head, "\x47\x49\x46\x38\x37\x61", 6)) {
      $ext = "gif"; // GIF87a
    } else if(!strncmp($head, "\x47\x49\x46\x38\x39\x61", 6)) {
      $ext = "gif"; // GIF89a
    } else if(!strncmp($head, "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a", 8)) {
      $ext = "png"; 
    }
  }
  return $ext;
} // }}}

function gallery_check_imgtype($name) { // {{{  
  $path = @$_FILES['files']['tmp_name'][$name];
  return in_array(gallery_get_ext($path), array('jpg', 'gif', 'png'));
} // }}}

function gallery_image_dir() {
  return conf_path() . '/gallery/images';
}

function gallery_thumb_dir() {
  return conf_path() . '/gallery/thumbs';
}

function gallery_image_path($image_id, $ext = '') {
  $ext = trim($ext);
  if ($ext != '') $ext = '.' . $ext;
  return gallery_image_dir() . sprintf('/%08x', $image_id) . $ext;
}

function toJSON($array, $escape_scalar = true) { // {{{
  if (!is_array($array)) {
    return '"' . js_escape($escape_scalar ? htmlspecialchars($array) : $array) . '"';
  }
  $object = false;
  foreach (array_keys($array) as $key) {
    if (!is_int($key)) { 
      $object = true; 
      break;
    }
  }
  $json = $object ? '{' : '[';
  $first = true;
  foreach ($array as $key => $value) {
    if ($first) $first = false;
    else $json .= ",";
    if ($object) $json .= '"' . js_escape($key) . '":';
    $json .= toJSON($value);
  }
  $json .= $object ? '}' : ']';
  return $json;
} // }}}

function js_escape($string) {
  return strtr($string, array("\"" => "\\\"", "\n" => "\\n", "\r" => "\\r", "\t" => "\\t"));
}

function gallery_image_js_string() {
  return js_escape(gallery_image_html_string());
}

function gallery_add_css() {
  static $added = false;
  if ($added) return;
  drupal_add_css(drupal_get_path('module', 'gallery') . '/gallery.css');
  $added = true;
}

function gallery_image_html_string($image = null) {
  $blank  = base_path() . 'i/blank.gif';
  $edit   = base_path() . GALLERY_MENU_IMAGE_EDIT;
  $delete = base_path() . GALLERY_MENU_IMAGE_DELETE;

  $format = <<<END_FORMAT
<div class="gallery-image" id="image-%id">
  <div class="gallery-image-thumb" style="background-image: url(%thumb)">
    <a href="$edit/%id"><img src="$blank" alt="" title="%title" /></a>
  </div>
  <div class="gallery-image-title">%title</div>
  <div class="gallery-image-admin">
    <a href="$edit/%id">Edit</a> | <a href="$delete/%id">Delete</a>
  </div>
</div>
END_FORMAT;
  $format = str_replace("\r\n", "\n", $format);
  if ($image) {
    $vars = array(
      '%title' => htmlspecialchars($image['title']),
      '%thumb' => $image['thumb'],
      '%id'    => $image['id'],
    );
    foreach ($vars as $k => $v) {
      $format = str_replace($k, $v, $format);
    }
  }
  return $format;
}

function gallery_image_html($image) { // {{{
  // keys: filename, id, title
  $thumb = gallery_thumb($image['filename']);
  if (!$thumb) return;

  $image['thumb'] = base_path() . $thumb;
  return gallery_image_html_string($image);
} // }}}

function gallery_pathinfo($path) { // {{{
  $name = basename($path);
  $ext = '';
  if (($p = strrpos($name, '.')) !== false) {
    $ext = substr($name, $p + 1);
    $name = substr($name, 0, $p);  
  }
  return array($name, $ext);
} // }}}

function gallery_get_image($id, $meta = false) { // {{{
  $image = db_fetch_array(db_query("SELECT * FROM {image} WHERE id = %d", $id));
  if ($meta) {
    $lang = Langs::lang();
    $data = db_fetch_array(db_query("SELECT * FROM {image_data} WHERE lang = '%s' AND image_id = %d", $lang, $id));
    if (is_array($data)) {
      $image += $data;
    }
  }
  return $image;
} // }}}

function gallery_thumb($image, $settings = array()) { // {{{
  // pobierz sciezke do pliku jezeli podano tylko id obrazu
  if (is_int($image) || ctype_digit($image)) {
    $row = db_fetch_array(db_query("SELECT filename FROM {image} WHERE id = %d", $image));  
    if (!$row) return;
    $filename = $row['filename'];
  } elseif (is_array($image)) {
    // przekazany pobrany rekord z tabeli
    $filename = $image['filename'];
  } else {
    $filename = $image;
  }

  // walidacja danych wejsciowych
  if (!is_array($settings)) $settings = array('max' => $settings);
  foreach ($settings as $key => $value) {
    $value = (int) $value;
    if ($value == 0) unset($settings[$key]);
    else $settings[$key] = $value;
  }
  if (empty($settings)) $settings = array('max' => 120);

  // sprawdzamy czy istnieje thumb, jezeli tak, to zwracamy jego sciezke
  list($name, $ext) = gallery_pathinfo($filename);

  if (isset($settings['max']))        $sfx = 'm' . $settings['max'];
  elseif (isset($settings['width']))  $sfx = 'w' . $settings['width'];
  elseif (isset($settings['height'])) $sfx = 'h' . $settings['height'];
  else return;

  $dest = sprintf("%s/%s.%s.%s", gallery_thumb_dir(), $name, $sfx, $ext);
  if (file_exists($dest)) return $dest;

  $source = gallery_image_dir() . '/' . $filename;
  $info = image_get_info($source);
  if (!$info) return;

  // get attributes of the source image
  $old_width  = $info['width'];
  $old_height = $info['height'];

  if (isset($settings['max'])) {
    $max = $settings['max'];
    if (max($old_width, $old_height) >= $max) {
      // determine thumb dimensions
      if($old_width < $old_height) {
        $new_height = $max;
        $new_width  = $new_height * $old_width / $old_height;
      } else {
        $new_width  = $max;
        $new_height = $new_width * $old_height / $old_width;
      }
    } else {
      // leave dimensions intact
      $new_height = $old_height;
      $new_width  = $old_width;
    }
  
  } else {
    if (isset($settings['width'])) { // width
      $new_width = $settings['width'];
      $new_height = $new_width * $old_height / $old_width;
    } 
    elseif (isset($settings['height'])) {
      $new_height = $settings['height'];
      $new_width  = $new_height * $old_width / $old_height;
    } else return;
  }

  if ($new_width * $new_height == 0) return;
  if (image_gd_resize($source, $dest, $new_width, $new_height)) {
    return $dest;
  }
} // }}}

function gallery_galleries_options() { // {{{
  $lang = Langs::lang();
  $gals = array(0 => '');
  $query = db_query("SELECT gallery_id, title FROM {gallery_data} WHERE lang = '%s' ORDER BY title", $lang);
  while ($row = db_fetch_array($query)) {
    $gals[$row['gallery_id']] = $row['title'];
  }
  return $gals;  
} // }}}

function gallery_galleries_select($image = null, $gallery_id = null) { // {{{
  $input = array(
    '#type' => 'select',
    '#title' => t('Gallery'),
    '#description' => t('Choose gallery to which image will belong.'),
    '#options' => gallery_galleries_options(),
  );
  if ($image) {
    $input['#default_value'] = (int) $image['gallery_id'];
  } elseif (isset($gallery_id)) {
    $input['#default_value'] = (int) $gallery_id;  
  }
  return $input;
} // }}}

function gallery_image_meta_form($image = null) { // {{{
  $langs = Langs::languages();
  $def_lang = Langs::lang();

  // title is not required when adding new image - 
  // it will be initialized with basename of image file
  $require_title = is_null($image) ? false : true;
  
  $form = array();
  foreach ($langs as $code => $name) {
    $meta = array();
    if ($image) {
      $meta = db_fetch_array(db_query("SELECT * FROM {image_data} WHERE image_id = %d AND lang = '%s'", $image['id'], $code));    
    }
    $form[$code] = array(
      '#type' => 'fieldset',
      '#title' => $name . ' (<img src="'.base_path().'i/flags/'.$code.'.png" alt="'.$name.'" style="display:inline;" />)',
      '#collapsible' => true,
      '#collapsed' => $code != $def_lang,
    );
    $form[$code][$code.'-title'] = array(    
      '#type' => 'textfield',
      '#title' => t("Title ($name)"),
      '#description' => sprintf(t('Add a title for your image in %s'), $name),
      '#required' => $require_title,
      '#default_value' => @$meta['title'],
    );
    $form[$code][$code.'-description'] = array(
      '#type' => 'textarea',
      '#title' => t("Description ($name)"),
      '#rows' => 2,
      '#cols' => 80,
      '#description' => sprintf(t('Add a description for your image in %s'), $name), 
      '#default_value' => @$meta['description'],
    );
  }

  // TODO gallery id
  return $form;
} // }}}

define('GALLERY_WEIGHT_MIN', -50);
define('GALLERY_WEIGHT_MAX', 50);

// TODO to jest chyba nieuzywane!
function gallery_image_weight_options() {
  $opts = array();
  foreach (range(GALLERY_WEIGHT_MIN, GALLERY_WEIGHT_MAX) as $weight) {
    $opts[$weight] = $weight;
  }
  return $opts;
}

$gallery_image_selectors = array();

function gallery_image_selector_validate($form, &$form_state) { // {{{
  global $gallery_image_selectors;
  foreach ($gallery_image_selectors as $name => $field) {
    $image_id = $form_state['values'][$name];
    if (strlen($form_state['values'][$name])) {
      $image_id = $form_state['values'][$name] = intval($form_state['values'][$name]);
      $thumb = gallery_thumb($image_id);
      if (!$thumb) {
        form_set_error($name, t('Invalid image id: %image', array('%image' => $image_id)));
      }
    } else {
      $form_state['values'][$name] = null;
    }
  }
} // }}}

function gallery_image_selector(&$form, $name, $default_value = null) { // {{{
  global $gallery_image_selectors;
  static $js_added = false;
  if (!$js_added) {
    gallery_add_css();

// kod js do otwierania okienka wyboru obrazkow
    $url = url('admin/content/gallery/image/list');
    $select = t('Select image');
    drupal_add_js(<<<JS_END
  window.__imageCallbackTitle = '$select';
  window.__imageCallback = function(id, thumb) {
    if (wndImageSelector) wndImageSelector.close();
    jQuery('input[name=$name]').each(function() { this.value = id });
    document.getElementById('image-thumb-$name').style.backgroundImage = 'url(' + thumb + ')';
  }
  var wndImageSelector;
  function gallery_unselect(id) {
    jQuery('input[name=$name]').each(function() { this.value = '' });
    document.getElementById('image-thumb-$name').style.backgroundImage = '';  
  }
  function gallery_select_image(id) {
    var url = '$url';    
    wndImageSelector = window.open(url, 'image-selector', 'menubar=1,resizable=1,width=640,height=480,scrollbars=1');
  }
JS_END
    , 'inline');
    $js_added = true;
  }
// ale current value!!!
  $blank = url('i/blank.gif');
  $thumb = '';
  if (isset($_REQUEST[$name])) {
    $default_value = $_REQUEST[$name];
  }
  if (!is_null($default_value)) {
    $thumb = gallery_thumb($default_value);    
    if ($thumb) {
      $thumb = "background-image:url(" . url($thumb) . ")";
    }
  }

drupal_add_js(<<<JS_END
  jQuery(function() {
    jQuery('input[name=$name]').each(function() {
      this.type = 'hidden';
      jQuery(this).siblings('.description').each(function() {
        this.style.display = 'none';
      });
      jQuery(this).siblings('.field-suffix').each(function() {
        this.innerHTML = '<div class="gallery-image-thumb" id="image-thumb-$name" style="float:left;margin-right:10px;$thumb">' +
                         '<img alt="" src="$blank"/>' +
                         '</div>' + this.innerHTML + '<div class="clear"></div>';
      });
    });
  });
JS_END
  , 'inline');

  $form[$name]['#title']         = t('Image');
  $form[$name]['#description']   = t('Numerical identifier of an image connected to this event.');
  $form[$name]['#type']          = 'textfield';
  $form[$name]['#default_value'] = $default_value;
  $form[$name]['#field_suffix']  = '<span>' .
    '<a href="javascript:void(null);" onclick="gallery_select_image(\''.$name.'\')">' . t('Select image') . '</a>' .
    ' | ' .
    '<a href="javascript:void(null);" onclick="gallery_unselect(\''.$name.'\')">' . t('Clear image') . '</a>' .
    '</span>';

  $form['#validate'][] = 'gallery_image_selector_validate';
  $gallery_image_selectors[$name] = &$form[$name];
} // }}}

// vim: ft=php

