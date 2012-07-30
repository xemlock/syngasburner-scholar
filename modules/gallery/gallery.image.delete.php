<?php

function gallery_image_delete_submit($form, &$form_state) { // {{{
  $image_id = (int) $form_state['values']['image_id'];
  $image = db_fetch_array(
              db_query('SELECT * FROM {image} WHERE id = %d', $image_id));  
  if ($image) {
    $file = gallery_image_dir() . '/' . $image['filename'];

    if (@unlink($file)) {
      // delete all thumbnails
      list($name, $ext) = gallery_pathinfo($image['filename']);
      $thumbs = glob(gallery_thumb_dir() . '/' . $name . '.*.' . $ext);
      foreach ($thumbs as $thumb) { @unlink($thumb); }

      // delete image data
      db_query("DELETE FROM {image_data} WHERE image_id = %d", $image_id);
      db_query("DELETE FROM {image} WHERE id = %d", $image_id);
      
      drupal_set_message(t('Image has been deleted.'));
    } else {
      drupal_set_message(t('Unable to delete image.'), 'error');
    }
  } else {
    drupal_set_message(t('Invalid image id supplied'), 'error');  
  }
  drupal_goto(GALLERY_MENU_IMAGE_LIST);
} // }}}

function gallery_image_delete_form(&$form_state, $image_id) { // {{{
  $image_id = (int) $image_id;

  $form = array();
  $form['image_id'] = array('#type' => 'hidden', '#value' => $image_id);
  $output = confirm_form($form,
                t('Are you sure you want to delete image (%image)?', array('%image' => $image_id)),
                GALLERY_MENU_IMAGE_EDIT . '/' . $image_id,
                t('This action cannot be undone.'),
                t('Delete'),
                t('Cancel'));
  $output['#submit'][] = 'gallery_image_delete_submit';
  return $output;
} // }}}

// vim: fdm=marker
