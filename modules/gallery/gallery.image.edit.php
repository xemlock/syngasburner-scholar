<?php

function gallery_image_edit($image_id) {
  $image_id = (int) $image_id;
  $image = db_fetch_array(
              db_query('SELECT * FROM {image} WHERE id = %d', $image_id));
  if (!$image) {
    drupal_set_message(t('Invalid image id supplied (%image)', 
                       array('%image' => $image_id)), 'error');
    return '';
  }

  $html = '';
  ob_start();
?>
<table style="width:440px;">
  <tr>
    <td rowspan="4"><a href="<?php echo base_path() . gallery_image_dir() . '/' . $image['filename'] ?>"><img src="<?php echo base_path() . gallery_thumb($image['filename']) ?>" alt="" /></a></td>
    <td><b>MIME type:</b></td>
    <td><?php echo $image['mimetype'] ?></td>
  </tr>
  <tr>
    <td><b>File size:</b></td>
    <td><?php echo format_size($image['filesize']) ?></td>
  </tr>
  <tr>
    <td><b>Dimensions:</b></td>
    <td><?php echo $image['width'] ?> x <?php echo $image['height'] ?> px</td>
  </tr>
  <tr>
    <td><b>Created:</b></td>
    <td><?php echo $image['created'] ?></td>
  </tr>
</table>
<?php
  $html .= ob_get_clean();
  $html .= drupal_get_form('gallery_image_edit_form', $image);
  return $html;
}

function gallery_image_edit_form(&$form_state, $image) { // {{{
  $form = array();
  $form['image_id'] = array(
    '#type' => 'hidden',
    '#value' => $image['id'],
  );
  $form['gallery_id'] = gallery_galleries_select($image);

  $form['weight'] = array(
    '#type' => 'select',
    '#options' => gallery_image_weight_options(),
    '#default_value' => 0,
    '#title' => t('Image weight'),
    '#description' => t('Image position in the gallery.'),
  );
    
  $form += gallery_image_meta_form($image);
  $form['update'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
  );
  $form['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
  );
  $form['#submit'][] = 'gallery_form_edit_submit';
  return $form;
} // }}}

function gallery_form_edit_submit($form, &$form_state) { // {{{
  $image_id = (int) $form_state['values']['image_id'];

  if ($form_state['clicked_button']['#value'] == $form_state['values']['delete']) {
    return drupal_goto(GALLERY_MENU_IMAGE_DELETE . '/' . $image_id);
  }
  $image = db_fetch_array(db_query("SELECT gallery_id FROM {image} WHERE id = %d", $image_id));

  if (!$image) { 
    return; // unlikely to happen
  }

  $old_gallery_id = (int) $image['gallery_id'];
  $gallery_id = (int) $form_state['values']['gallery_id'];

  $weight = (int) @$form_state['values']['weight'];
  if ($image['weight'] != $weight) {
    db_query("UPDATE {image} SET weight = %d WHERE id = %d", $weight, $image_id);
  }

  if ($old_gallery_id != $gallery_id) {
    if ($old_gallery_id != 0) { // detach from old gallery
      db_query("UPDATE {image} SET gallery_id = NULL, weight = 0 WHERE id = %d", $image_id);
    }
    if ($gallery_id != 0) { // attach to new gallery
      db_query("UPDATE {image} SET gallery_id = %d, weight = %d WHERE id = %d", $gallery_id, GALLERY_WEIGHT_MAX, $image_id);
    }
  }

  db_query("DELETE FROM {image_data} WHERE image_id = %d", $image_id);
  foreach (Langs::languages() as $lang => $null) {
    $title = trim(@$form_state['values'][$lang . '-title']);
    $desc  = trim(@$form_state['values'][$lang . '-description']);
    db_query("INSERT INTO {image_data} (image_id, lang, title, description) VALUES ('%s', '%s', '%s', '%s')",
      $image_id, $lang, $title, $desc
    );
  }
  drupal_set_message(t('Image data updated successfully.'));
  drupal_goto(GALLERY_MENU_IMAGE_EDIT . '/' . $image_id);
} // }}}

// vim: fdm=marker
