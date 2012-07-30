<?php

require_once 'includes/image.gd.inc';

function gallery_image_get_dimensions(&$form_state, $info) { // {{{
  $width = (int) trim($form_state['values']['width']);
  $height = (int) trim($form_state['values']['height']);
  $keep_ratio = (int) trim($form_state['values']['ratio']);

  if ($height + $width) {
    if ($height * $width) { // both height and width given
      if ($keep_ratio) {
        $height = $info['height'] * $width / $info['width'];
      }
    } elseif ($height) { // only height was given
      if ($keep_ratio) {
        $width = $info['width'] * $height / $info['height'];
      } else {
        $width = $info['width'];
      }
    } else { // only width was given
      if ($keep_ratio) {
        $height = $info['height'] * $width / $info['width'];
      } else {
        $height = $info['height'];
      }
    }
  } else {
    $width = $info['width'];
    $height = $info['height'];
  }

  return array($width, $height);
} // }}}

function gallery_image_resize($filename, $width, $height) { // {{{
  if (image_gd_resize($filename, $filename . '.tmp', $width, $height)) {
    @unlink($filename);
    return @rename($filename . '.tmp', $filename);
  }
  return false;
} // }}}

function gallery_get_uploaded_file_name($name) { // {{{
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
} // }}}

function gallery_form_add_submit($form, &$form_state) { // {{{
  $file = gallery_get_uploaded_file_name('file_upload');
  $tmp_name = $_FILES['files']['tmp_name']['file_upload'];

  if (@move_uploaded_file($tmp_name, $file)) {
    $info = image_get_info($file);
    list($width, $height) = gallery_image_get_dimensions($form_state, $info);
    if ($width != $info['width'] || $height != $info['height']) {
      if (gallery_image_resize($file, $width, $height)) {
        $info['width'] = $width;
        $info['height'] = $height;
        $info['file_size'] = filesize($file);
      }
    }
    // generate default thumb
    gallery_thumb($file);

    $weight = (int) $form_state['values']['weight'];
    db_query("INSERT INTO {image} (mimetype, filename, filesize, width, height, created, weight) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', %d)",
      $info['mime_type'], basename($file), $info['file_size'], $info['width'], $info['height'], date('Y-m-d H:i:s'), $weight
    );
    $image_id = db_last_insert_id('image', 'id');
    
    $gallery_id = (int) $form_state['values']['gallery_id'];
    if ($gallery_id) {
      // attach to gallery
      db_query("UPDATE {image} SET gallery_id = %d WHERE id = %d", $gallery_id, $image_id);
    }

    list($name, $ext) = gallery_pathinfo(basename($_FILES['files']['name']['file_upload']));
    foreach (Langs::languages() as $lang => $null) {
      $title = trim(@$form_state['values'][$lang . '-title']);
      if ($title == '') $title = $name;

      $desc  = trim(@$form_state['values'][$lang . '-description']);
      db_query("INSERT INTO {image_data} (image_id, lang, title, description) VALUES ('%s', '%s', '%s', '%s')",
        $image_id, $lang, $title, $desc
      );
    }

    drupal_set_message(sprintf(t('Image uploaded successfully. View image properties <a href="%s">here</a>.'), 
              url(GALLERY_MENU_IMAGE_EDIT) . '/' .$image_id));
    return drupal_goto(GALLERY_MENU_IMAGE_LIST);
  } else {
    drupal_set_message(t('Image upload failed'));
  }
} // }}}

function gallery_form_add_validate($form, &$form_state) { // {{{
  trace(__FUNCTION__);

  foreach ($form_state['values'] as $name => $value) {
    $value = trim($value);
    switch ($name) {
      case 'file_upload':
        if (empty($_FILES['files']['name']['file_upload'])) {
          form_set_error($name, t('Image file field is required.'));
        }
        elseif (!isset($_FILES['files']['tmp_name']['file_upload']) || !file_exists($_FILES['files']['tmp_name']['file_upload'])) {
          form_set_error($name, t('Error while uploading file.'));
        }
        elseif (!image_get_info($_FILES['files']['tmp_name']['file_upload'])) {
          form_set_error($name, t('Uploaded file is not an image.'));
        }
        elseif (!gallery_check_imgtype('file_upload')) {
          form_set_error($name, t('Only .jpg, .gif and .png image files are accepted.'));
        }
        break;

      case 'width':
      case 'height':
        if (strlen($value) && (!preg_match('/^[0-9]{1,3}$/', $value) || $value <= 0)) {
          form_set_error($name, t('Size should be an integer between 1 and 999 or leave it empty if you don\'t want to scale your image.'));
        }
        break;
    }
  }
} // }}}

function gallery_image_add_form(&$form_state, $gallery_id = null) { // {{{
  trace(__FUNCTION__);

  $form['#attributes']['enctype'] = 'multipart/form-data';
  $form['file_upload'] = array(
    '#type' => 'file',
    '#title' => t('Image file'),
    '#description' => t('Browse your computer for image file'),
    '#required' => true,
    '#value' => 1,
    '#size' => 40,
  );
  $form['gallery_id'] = gallery_galleries_select(null, $gallery_id);
  $form['weight'] = array(
    '#type' => 'select',
    '#options' => gallery_image_weight_options(),
    '#title' => t('Image weight'),
    '#default_value' => 0,
    '#description' => t('Image position in the gallery.'),
  );
  
  // scaling
  $form['scale'] = array(
    '#type' => 'fieldset',
    '#title' => t('Scale image'),
    '#collapsible' => true,
    '#collapsed' => true,
  );
  $form['scale']['width'] = array(
    '#type' => 'textfield',
    '#title' => t("New width (px)"),
    '#description' => t('Leave empty to retain original width.'),
  );
  $form['scale']['height'] = array(
    '#type' => 'textfield',
    '#title' => t("New height (px)"),
    '#description' => t('Leave empty to retain original height.'),
  );
  $form['scale']['ratio'] = array(
    '#type' => 'checkbox',
    '#title' => t("Keep ratio"),
    '#description' => t('Scale image proportionally using first non-empty dimension given.'),
    '#default_value' => true,
  );

  $form += gallery_image_meta_form();
  
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Upload'),
  );
  $form['#validate'][] = 'gallery_form_add_validate';
  $form['#submit'][] = 'gallery_form_add_submit';

  return $form;
} // }}}

// vim: fdm=marker
