<?php

function gallery_weights($id, $value = null, $attributes = array()) { // {{{
  $attrs = '';
  foreach ($attributes as $key => $value) {
    $attrs .= " $key=\"$value\"";
  }
  $html .= "<select name=\"$id\" id=\"$id\" ". $attrs. ">";
  foreach (range(-50, 50) as $x) {
    $sel = !is_null($value) && ($x == $value) ? ' selected="selected"' : '';
    $html .= "<option value=\"$x\"$sel>$x</option>\n";
  }
  $html .= "</select>";
  return $html;
} // }}}

function gallery_save_weights() { // {{{
  if (is_array($_POST['image-weight'])) {
    foreach ($_POST['image-weight'] as $id => $weight) {
      db_query("UPDATE {image} SET weight = %d WHERE id = %d", $weight, $id);
    }
  }
  drupal_set_message(t('Changes were applied successfully.'));
  drupal_goto(GALLERY_MENU_GALLERY_DETAILS . (isset($_POST['id']) ? '/' . $_POST['id'] : ''));
} // }}}

function gallery_details($gallery_id = null) { // {{{
  if (isset($_POST['save-weights'])) return gallery_save_weights();
  
  gallery_add_css();

  $gallery_match = 'gallery_id ' . (is_null($gallery_id) ? 'IS NULL' : ('=' . intval($gallery_id)));
  $lang = Langs::lang();
  $title = null;
  
  $header = '';
  if (!is_null($gallery_id)) {
    $gallery = db_fetch_array(db_query("SELECT * FROM {gallery} g JOIN {gallery_data} d ON g.id = d.gallery_id WHERE gallery_id = %d AND lang = '%s'", $gallery_id, $lang));
    if ($gallery) {
      $title = $gallery['title'];
      $desc = trim($gallery['description']);
      if ($desc) {
        $header .= '<p>' . htmlspecialchars($desc) . '</p>';        
      }
    }
    
  }
  if (!$title) $title = 'no gallery';
  drupal_set_title(t('Gallery details: %gallery', array('%gallery' => $title)));
  $header .= <<<EOS
<style type="text/css">    
table#gallery-images-table {
  width: 100%;
}
table#gallery-images-table td.ident {
  width: 24px;
}
table#gallery-images-table td.gallery-image-thumb {
  width: 120px;
  text-align: center;
  padding: 4px;
}
table#gallery-images-table td.gallery-image-thumb div {
  padding: 8px;
  border: 1px solid #ccc;
  background: white;
}
table#gallery-images-table td.gallery-image-thumb img {
  margin: auto;
}
</style>
EOS;

  $empty = true;
  $query = db_query("SELECT i.id AS id, filename, title, weight FROM {image} i JOIN {image_data} d ON i.id = d.image_id WHERE $gallery_match AND lang = '$lang' ORDER BY weight");
  
  $rows = array();  

  $edit   = base_path() . GALLERY_MENU_IMAGE_EDIT;
  $delete = base_path() . GALLERY_MENU_IMAGE_DELETE;
  while ($row = db_fetch_array($query)) {
    $thumb = base_path() . gallery_thumb($row['filename'], array('max' => 100));
    $rows[] = array(
      'data' => array(
        array('data' => '', 'class' => 'ident'),
        array('data' => "<div><img src=\"$thumb\" alt=\"\"/></div>", 'class' => 'gallery-image-thumb'), 
        array('data' => htmlspecialchars($row['title']), 'width' => '100%'),
        gallery_weights('image-weight[' . $row['id'] . ']', $row['weight'], array('class' => 'gallery-image-weight')),
        "<a href=\"$edit/{$row['id']}\">" . t('edit') . "</a>",
        "<a href=\"$delete/{$row['id']}\">" . t('delete') . "</a>",
      ),
      'class' => 'draggable gallery-image-cell',
    );
  }

  $head = array(
    '', 
    t('Image'),
    t('Title'),
    t('Weight'),
    array('data' => t('Operations'), 'colspan' => 2),
  );
  $attributes = array(
    'id' => 'gallery-images-table',
    'cellpadding' => 2,
  );
  if (count($rows)) {
    // Drupal's tabledrag does not work properly with jQuery 1.3 !!!
    drupal_add_tabledrag('gallery-images-table', 'order', 'sibling', 'gallery-image-weight', null, null, true);
    return "<form action=\"\" method=\"post\">" .
           "<input type=\"hidden\" name=\"id\" value=\"$gallery_id\" />" .
           $header . theme_table($head, $rows, $attributes) .
           "<p><input type=\"submit\" name=\"save-weights\" value=\"".t('Save changes')."\"/></p>" .
           "</form>";
  } else {
    return $header . '<i>' . t('Gallery contains no images') . '</i>';
  }
} // }}}

function gallery_list() { // {{{
  $lang = Langs::lang();

  $sql = "SELECT g.id AS id, created, title, description FROM {gallery} g JOIN {gallery_data} d ON g.id = d.gallery_id WHERE lang = '%s' ORDER BY created DESC";
  $query = db_query($sql, $lang);
  ob_start();
  while ($row = db_fetch_array($query)) {
    $details = db_fetch_array(db_query("SELECT COUNT(*) AS image_count, SUM(filesize) AS total_size FROM {image} WHERE gallery_id = %d", $row['id']));
    // select gallery cover-image
    $cover = gallery_get_cover($row['id']);
    $thumb = gallery_thumb(@$cover['filename']);
    if (!$thumb) $thumb = drupal_get_path('module', 'gallery') . '/blank.png';
?>
<table style="width:600px;" cellpadding="4">
 <tr>
  <td rowspan="5" style="width:120px;"><img style="margin:auto;display:block;" src="<?php echo base_path() . $thumb ?>" /></td>
  <td style="width:120px;"><b>Title</b></td>
  <td><?php echo $row['title'] ?></td>
 </tr>
 <tr>
  <td><b>Description</b></td>
  <td><?php echo $row['description'] ?></td>
 </tr>
 <tr>
  <td><b>Number of images</b></td>
  <td><?php echo $details['image_count'] ?></td>
 </tr>
 <tr>
  <td><b>Total size</b></td>
  <td><?php echo format_size((int) $details['total_size']) ?></td>
 </tr>
 <tr>
  <td colspan="2" align="right">
    <a href="<?php echo base_path() . GALLERY_MENU_GALLERY_EDIT . '/' . $row['id'] ?>">Edit</a> | 
    <a href="<?php echo base_path() . GALLERY_MENU_GALLERY_DETAILS . '/' . $row['id'] ?>">View images</a>
  </td>
 </tr>
</table>
<?php
  }
  return ob_get_clean();
} // }}}
  
function gallery_meta_form($gallery = null) { // {{{
  $form = array();

  if ($gallery) {
    $form['gallery_id'] = array(
      '#type' => 'hidden',
      '#value' => $gallery['id'],
    );
  }

  $def_lang = Langs::lang();
  foreach (Langs::languages() as $code => $name) {
    $meta = array();
    if ($gallery) {
      $meta = db_fetch_array(db_query("SELECT * FROM {gallery_data} WHERE gallery_id = %d AND lang = '%s'", $gallery['id'], $code));    
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
      '#description' => sprintf(t('Add a name for your gallery in %s'), $name),
      '#required' => true,
      '#default_value' => @$meta['title'],
    );
    $form[$code][$code.'-description'] = array(
      '#type' => 'textarea',
      '#title' => t("Description ($name)"),
      '#rows' => 2,
      '#cols' => 80,
      '#description' => sprintf(t('Add a description for your gallery in %s'), $name), 
      '#default_value' => @$meta['description'],
    );
  }

  return $form;
} // }}}

function gallery_form_submit($form, &$form_state, $id = null) { // {{{
  if (!$id) {
    $edit = false;
    db_query("INSERT INTO {gallery} (created) VALUES ('%s')", date('Y-m-d H:i:s'));
    $id = db_last_insert_id('gallery', 'id');
  } else {
    $edit = true;
    db_query("DELETE FROM {gallery_data} WHERE gallery_id = %d", $id);
  }

  foreach (Langs::languages() as $code => $name) {
    $title = @$form_state['values'][$code . '-title'];
    $desc = @$form_state['values'][$code . '-description'];
    db_query("INSERT INTO {gallery_data} (gallery_id, lang, title, description) VALUES (%d, '%s', '%s', '%s')",
      $id, $code, $title, $desc);
  }

  if ($edit) {
    drupal_set_message(t('Gallery updated successfully'));
  } else {
    drupal_set_message(t('Gallery created successfully'));
  }

  drupal_goto(GALLERY_MENU_GALLERY_LIST);
} // }}}

function gallery_add_form() { // {{{
  $form = gallery_meta_form();
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save changes'),
  );
  $form['#submit'][] = 'gallery_form_submit';
  return $form;
} // }}}

function gallery_edit_form_submit($form, &$form_state) { // {{{
  $gallery_id = (int) $form_state['values']['gallery_id'];
  if ($form_state['clicked_button']['#value'] == $form_state['values']['delete']) {
    return drupal_goto(GALLERY_MENU_GALLERY_DELETE . '/' . $gallery_id);
  }

  return gallery_form_submit($form, $form_state, $form_state['values']['gallery_id']);
} // }}}

function gallery_edit_form(&$form_state, $gallery_id) { // {{{
  $gallery = db_fetch_array(db_query("SELECT * FROM {gallery} WHERE id = %d", $gallery_id));
  if (!$gallery) {
    drupal_set_message(t('Invalid gallery id supplied (%id)', array('%id' => $gallery_id)), 'error');
    return drupal_goto(GALLERY_MENU_GALLERY_LIST);
  }
  
  $form = gallery_meta_form($gallery);
  $form['update'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
  );
  $form['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
  );
  $form['#submit'][] = 'gallery_edit_form_submit';
  return $form;
} // }}}

function gallery_delete_submit($form, &$form_state) { // {{{
  $gallery_id = (int) $form_state['values']['gallery_id'];

  // detach images
  db_query("UPDATE {image} SET gallery_id = NULL WHERE gallery_id = %d", $gallery_id);
  // remove gallery data
  db_query("DELETE FROM {gallery_node} WHERE gallery_id = %d", $gallery_id);
  db_query("DELETE FROM {gallery_data} WHERE gallery_id = %d", $gallery_id);
  db_query("DELETE FROM {gallery} WHERE id = %d", $gallery_id);
  drupal_set_message(t('Gallery has been deleted.'));

  drupal_goto(GALLERY_MENU_GALLERY_LIST);
} // }}}

function gallery_delete_form(&$form_state, $gallery_id) { // {{{
  $gallery_id = (int) $gallery_id;

  $form = array();
  $form['gallery_id'] = array('#type' => 'hidden', '#value' => $gallery_id);
  $output = confirm_form($form,
                t('Are you sure you want to delete gallery (%gallery)?', array('%gallery' => $gallery_id)),
                GALLERY_MENU_GALLERY_EDIT . '/' . $gallery_id,
                t('This action cannot be undone.'),
                t('Delete'),
                t('Cancel'));
  $output['#submit'][] = 'gallery_delete_submit';
  return $output;
} // }}}

// vim: fdm=marker
