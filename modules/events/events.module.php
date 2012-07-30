<?php defined('DRUPAL_BOOTSTRAP_PATH') || exit;

function setPageArguments(&$items) { // {{{
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
} // }}}

define('EVENTS_MENU',         'events');

define('EVENTS_ADMIN_MENU',         'admin/content/events');
define('EVENTS_ADMIN_MENU_LIST',    'admin/content/events/list');
define('EVENTS_ADMIN_MENU_ADD',     'admin/content/events/add');
define('EVENTS_ADMIN_MENU_EDIT',    'admin/content/events/edit');
define('EVENTS_ADMIN_MENU_DELETE',  'admin/content/events/delete');
define('EVENTS_ADMIN_MENU_CONFIG',  'admin/settings/events');

function events_menu() { // {{{
  $items[EVENTS_MENU] = array(
    'title' => 'Events',
    'page callback' => 'events_main',
    'access callback' => true,
    'type' => MENU_LOCAL_TASK,
  );

  $items[EVENTS_ADMIN_MENU] = array(
    'title' => 'Events',
    'description' => 'View, edit, and delete events information.',
    'page callback' => 'events_list',
    'access arguments' => array('admin events'),
  );
  $items[EVENTS_ADMIN_MENU_LIST] = array(
    'title' => 'Events list',
    'type' => MENU_DEFAULT_LOCAL_TASK,
  );
  $items[EVENTS_ADMIN_MENU_ADD] = array(
    'title' => t('Add event'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('events_add_form'),
    'type' => MENU_LOCAL_TASK,
    'access arguments' => array('admin events'),
  );
  $items[EVENTS_ADMIN_MENU_EDIT . '/%'] = array(
    'title' => t('Edit event'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('events_edit_form'),
    'type' => MENU_CALLBACK,
    'access arguments' => array('admin events'),
    'parent' => EVENTS_ADMIN_MENU,
  );
  $items[EVENTS_ADMIN_MENU_DELETE . '/%'] = array(
    'title' => t('Delete event'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('events_delete_form'),
    'type' => MENU_CALLBACK,
    'access arguments' => array('admin events'),
    'parent' => EVENTS_ADMIN_MENU,
  );

  $items[EVENTS_ADMIN_MENU_CONFIG] = array(
    'title' => t('Events'),
    'description' => 'Configure paths to event\'s lists.',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('events_config_form'),
    'type' => MENU_NORMAL_ITEM,
    'access arguments' => array('admin events'),
  );

  setPageArguments($items);
  
  return $items;
} // }}}

function events_config_form_submit($form, &$form_state) {
  foreach (Langs::languages() as $code => $name) {
    variable_set('events_current_' . $code, $form_state['values'][$code.'-current']);
    variable_set('events_archive_' . $code, $form_state['values'][$code.'-archive']);

    variable_set('events_format_day_' . $code, $form_state['values'][$code.'-day']);
    variable_set('events_format_month_' . $code, $form_state['values'][$code.'-month']);
    variable_set('events_format_year_' . $code, $form_state['values'][$code.'-year']);
  }
  variable_set('events_format_ansi', (int) $form_state['values']['format-ansi']);
  
  drupal_set_message(t('Events settings saved successfully'));
  drupal_goto(EVENTS_ADMIN_MENU_CONFIG);
}

define('FORMAT_DAY',   1); // single date
define('FORMAT_MONTH', 2); // common month
define('FORMAT_YEAR',  4); // common year

function events_config_form() {
  $form = array();
  $def_lang = Langs::default_lang();
  $form[] = array(
    '#type' => 'markup',
    '#value' => '<h3>Paths configuration</h3>',
  );
  foreach (Langs::languages() as $code => $name) {
    $form[$code] = array(
      '#type' => 'fieldset',
      '#title' => $name . ' (<img src="'.base_path().'i/flags/'.$code.'.png" alt="'.$name.'" style="display:inline;" />)',
      '#collapsible' => true,
      '#collapsed' => $code != $def_lang,
    );
    $form[$code][$code.'-current'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to upcoming events list'),
      '#required' => $require_title,
      '#default_value' => variable_get('events_current_' . $code, ''),
      '#required' => true,
    );
    $form[$code][$code.'-archive'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to events archive'),
      '#required' => $require_title,
      '#default_value' => variable_get('events_archive_' . $code, ''),
      '#required' => true,
    );
  }
  // date formats
  $form[] = array(
    '#type' => 'markup',
    '#value' => '<h3>Date format settings</h3><p>Settings use the same notation as the <a href="http://www.php.net/manual/en/function.date.php" target="_blank">PHP date function</a>. Formats for each of date span dates are recognized using " - " separator.</p>',
  );
  foreach (Langs::languages() as $code => $name) {
    $form['format-' . $code] = array(
      '#type' => 'fieldset',
      '#title' => $name . ' (<img src="'.base_path().'i/flags/'.$code.'.png" alt="'.$name.'" style="display:inline;" />)',
      '#collapsible' => true,
      '#collapsed' => $code != $def_lang,
    );
    $form['format-' . $code][$code.'-day'] = array(
      '#type' => 'textfield',
      '#title' => t('Single date'),
      '#required' => $require_title,
      '#default_value' => get_format($code, FORMAT_DAY),
      '#required' => true,
    );
    $form['format-' . $code][$code.'-month'] = array(
      '#type' => 'textfield',
      '#title' => t('Datespan within the same month'),
      '#description' => t('Date span format given as "From - To".'),
      '#required' => $require_title,
      '#default_value' => get_format($code, FORMAT_MONTH),
      '#required' => true,
    );
    $form['format-' . $code][$code.'-year'] = array(
      '#type' => 'textfield',
      '#title' => t('Date span within the same year'),
      '#description' => t('Date span format given as "From - To".'),
      '#required' => $require_title,
      '#default_value' => get_format($code, FORMAT_YEAR),
      '#required' => true,
    );
  }
  // date format used in events list visible for administrator
  $form['format-ansi'] = array(
    '#title' => t('Use ANSI SQL date format in events list visible for admin'),
    '#description' => t('If set to "No" date format for current language will be used.'),
    '#type' => 'select',
    '#options' => array(t('No'), t('Yes')),
    '#default_value' => variable_get('events_format_ansi', 0),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save changes'),
  );
  $form['#submit'][] = 'events_config_form_submit';
  return $form;
}


function events_get_sql($lang = null, $mode = null) { // {{{
  $now = date('Y-m-d') . ' 00:00:00';
  switch ($mode) {
    case 'current':
      return "SELECT * FROM {events} WHERE lang = '$lang' AND ((end_date IS NULL AND start_date >= '$now') OR (end_date IS NOT NULL AND end_date >= '$now')) ORDER BY start_date";
    case 'archive':
      return "SELECT * FROM {events} WHERE lang = '$lang' AND ((end_date IS NULL AND start_date < '$now') OR (end_date IS NOT NULL AND end_date < '$now')) ORDER BY start_date DESC";
  }
} // }}}


function events_t($lang, $text) { // {{{
  $dict = array(
    'pl' => array(
              'Upcoming events' => 'Najbliższe wydarzenia',
              'Events archive' => 'Archiwalne wydarzenia',
              'No events' => 'Brak wydarzeń',
              'edit' => 'edycja',
              'Archive' => 'Archiwum',
              'More' => 'Więcej',
            ),
  );
  if (isset($dict[$lang][$text])) return $dict[$lang][$text];
  return $text;
} // }}}

function events_node() { // {{{
  $lang = Langs::lang();
  $sql = events_get_sql($lang, 'current') . ' LIMIT 3';
  $events = array();
  $res = db_query($sql);
  while ($row = db_fetch_array($res)) {
    $events[] = array(
      'id' => $row['id'],
      'date' => events_format_date($lang, $row['start_date'], $row['end_date']),
      'title' => $row['title'],
    );
  }
  return $events;
} // }}}

function get_format($lang, $type) { // {{{
  switch ($type) {
    case FORMAT_DAY:
      $var = 'day';
      $fmt = 'Y-m-d';
      break;

    case FORMAT_MONTH:
      $var = 'month';
      $fmt = 'Y-m d - d';
      break;

    case FORMAT_YEAR:
      $var = 'year';
      $fmt = 'Y m-d - m-d';
      break;
  }
  return variable_get('events_format_' . $var .  '_' . $lang, $fmt);
} // }}}

function events_format_date($lang, $from, $to = null) { // {{{
  if (is_null($from)) return '';

  sscanf($from, "%d-%d-%d", $y, $m, $d);
  $tfrom = mktime(0, 0, 0, $m, $d, $y);
  if (!is_null($to)) {
    sscanf($to, "%d-%d-%d", $ty, $tm, $td);
    $tto = mktime(0, 0, 0, $tm, $td, $ty);
    if ($ty != $y) {
      // different year
      $fmt = get_format($lang, FORMAT_DAY);
      return date($fmt, $tfrom) . ' - ' . date($fmt, $tto);
    } elseif ($tm != $m) {
      // same year, different month
      $fmt = explode(' - ', get_format($lang, FORMAT_YEAR));      
      return date($fmt[0], $tfrom) . ' - ' . date(@$fmt[1], $tto);
    } elseif ($td != $d) {
      $fmt = explode(' - ', get_format($lang, FORMAT_MONTH));
      return date($fmt[0], $tfrom) . ' - ' . date(@$fmt[1], $tto);
    }
  }
  $fmt = get_format($lang, FORMAT_DAY);
  return date($fmt, $tfrom);
} // }}}

function events_main($lang = null, $mode = null) { // {{{
  global $user;

  if (!in_array($mode, array('current', 'archive'))) {
    $mode = 'current';
  }
  if (!in_array($lang, array_keys(Langs::languages()))) {
    $lang = Langs::default_lang();
  }
  drupal_set_title(events_t($lang, $mode == 'current' ? 'Upcoming events' : 'Events archive'));
  $sql = events_get_sql($lang, $mode);

  $res = db_query($sql);
  $empty = true;
  ob_start();
  $blank = url('i/blank.gif');
  while ($row = db_fetch_array($res)) {
    $empty = false;

    echo '<div class="event-info"><h3>' . 
         events_format_date($lang, $row['start_date'], $row['end_date']) . 
         ': ' . htmlspecialchars($row['title']);
         
    if ($user->uid) {
      echo "  <a href=\"" . url(EVENTS_ADMIN_MENU_EDIT, array('absolute' => true)) . "/{$row['id']}\" style=\"font-weight:normal;font-size:small;\">" . events_t($lang, 'edit'). '</a>';
    }
    echo '</h3>';
    if ($row['image_id']) {
      $image = gallery_get_image($row['image_id'], true);
      $thumb = gallery_thumb($image, array('max' => 100));   
      if ($thumb) {
        $thumb = url($thumb);
        $href = url(gallery_image_dir() . '/' . $image['filename']);
        
        $title = htmlspecialchars($image['title']);
        $desc = htmlspecialchars($image['description']);
        echo '<div class="image-frame" style="margin-right:10px;">';
        echo "<a href=\"{$href}\" rel=\"lightbox\" title=\"{$title}|{$desc}\" style=\"background-image:url({$thumb})\">";
        echo "<img src=\"{$blank}\" alt=\"\" />";
        echo "<img src=\"{$thumb}\" alt=\"\" style=\"display:none;\" />";
        echo "</a>";
        echo '</div>';
      }
    }
    echo '<p style="padding-top:4px;">' . $row['body'] . '</p>';
    if ($row['node_path']) {
      $url = url($row['node_path']);
      echo '<p><br/><a href="'.$url.'">' . t('More...') . '</a></p>';
    }    
    echo '</div>';
    echo '<div class="clear"></div>';
  }
  if ($empty) {
    ob_end_clean();
    return '<i>' . events_t($lang, 'No events') . ' </i>';
  }
  return '<div id="events">' . ob_get_clean() . '</div>';
} // }}}

function events_list() { // {{{
  $res = db_query("SELECT * FROM {events} ORDER BY start_date DESC");
  $lang = Langs::lang();
  $empty = true;
  ob_start();
  $i = 1;
  while ($row = db_fetch_array($res)) {
    $from = substr($row['start_date'], 0, 10);
    $to = $row['end_date'] ? substr($row['end_date'], 0, 10) : null;
    $ansi = variable_get('events_format_ansi', 0);
    if (!$ansi) {
      $from = events_format_date($lang, $from);
      $to = $to ? events_format_date($lang, $to) : null;
    }    
    $empty = false;
    echo '<tr class="' . ($i % 2 ? 'odd' : 'even'). '">';
    echo '<td style="white-space:nowrap;">' . $from . ($to ? ' - ' . $to : '') . '</td>';
    echo '<td>' . htmlspecialchars($row['title']) . '</td>';
    echo '<td><a href="' . url(EVENTS_ADMIN_MENU_EDIT, array('absolute' => true)) . '/' . $row['id']. '">' . t('edit') . '</a></td>';
    echo '<td><a href="' . url(EVENTS_ADMIN_MENU_DELETE, array('absolute' => true)) . '/' . $row['id']. '">' . t('delete') . '</a></td>';
    echo '</tr>';
    echo "\n";
    $i++;
  }

  $info = '<table style="border:none;" cellspacing="4" cellpadding="4">' . 
          '<tbody style="border:none"><tr><td valign="top">Events list available to web users are located in:</td>' . 
          '<td><ul style="margin:0;padding:0;list-style:none;">' . 
          '<li><code>/events</code> - upcoming events, default language ('. Langs::default_lang() .')</li>'.
          '<li><code>/events/{lang}</code> - upcoming events in given language</li>'.
          '<li><code>/events/{lang}/archive</code> - events archive in given language</li>'.
          '</ul></td></tr></tbody></table><br/>';
  
  if ($empty) {
    ob_end_clean();
    return $info . '<i>No events</i>';
  }

  $header = '<thead class="tableHeader-processed">' . 
            '<tr>' .
            '<th>' . t('Date') . '</th>' .
            '<th>' . t('Title') . '</th>' .
            '<th colspan="2">' . t('Operations') . '</th>' .
            '</tr>' . 
            '</thead>' .
            "\n";
  return $info . '<table class="sticky-enabled sticky-table" cellspacing="4" cellpadding="4">' . $header . '<tbody>' . ob_get_clean() . '</tbody></table>';
} // }}}

function events_check_date(&$form_state, $field) { // {{{
  $form_state['values'][$field] = trim(@$form_state['values'][$field]);
  $date = $form_state['values'][$field];
  if (strlen($date) == 0) return;

  sscanf($date, "%d-%d-%d", $year, $month, $day);
  if (!checkdate($month, $day, $year)) {
    form_set_error($field, t('Invalid date supplied.'));
  }
} // }}}

function events_form_validate($form, &$form_state) { // {{{
  events_check_date($form_state, 'start_date');
  events_check_date($form_state, 'end_date');

  // validate date range
  if (strlen($form_state['values']['start_date']) && strlen($form_state['values']['end_date']) &&
      ($form_state['values']['start_date'] > $form_state['values']['end_date'])) {
    form_set_error('end_date', t('Invalid date range specified.'));
  }

  $path = $form_state['values']['node_path'] = trim($form_state['values']['node_path']);
  /* to niezupelnie dziala tak jak powinno :/
  if (strlen($path)) {   
    $item = menu_get_item($path);
    if (!$item || !$item['access']) {
      form_set_error('node_path', t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $path)));
    }
  }
  */
} // }}}

function db_quote($value = null) { // {{{
  global $db_url;

  if (is_null($value)) return 'NULL';
  if (is_numeric($value)) return $value;
  
  $db = array_shift(explode(':', $db_url));
  switch ($db) {
    case 'mysql':
    case 'mysqli':
      return "'" . str_replace("'", "\\'", $value) . "'";
    case 'pgsql':
      return "'" . str_replace("'", "''", $value) . "'";
  }

  die ("Unsupported database type: " . $db);
} // }}}

function events_prepare_values(&$form_state, $mode = 'add') { // {{{
  $start_date = $form_state['values']['start_date'];
  $end_date   = $form_state['values']['end_date'];
  if ($end_date == '' || $start_date == $end_date) {
    $end_date = null;
  }
  $values = array(
    'title'   => $form_state['values']['title'],
    'start_date' => $start_date,
    'end_date'   => $end_date,
    'lang' => $form_state['values']['lang'],
    'body' => $form_state['values']['body'],
    'node_path' => $form_state['values']['node_path'],
    'image_id' => $form_state['values']['image_id'],
  );
  if ($mode == 'add') {
    $values['created'] = date('Y-m-d h:i:s');
  }

  return $values;
} // }}}

function events_add_form_submit($form, &$form_state) { // {{{
  $values = events_prepare_values($form_state);
  $sql = "INSERT INTO {events} (" . implode(",", array_keys($values)) . 
         ") VALUES (" . implode(",", array_map('db_quote', $values)) . ")";
  db_query($sql);
  drupal_set_message(t('Event added successfully.'));
  drupal_goto(EVENTS_ADMIN_MENU_LIST);
} // }}}

function events_edit_form_submit($form, &$form_state) { // {{{
  $id = $form_state['values']['id'];

  if ($form_state['clicked_button']['#value'] == $form_state['values']['delete']) {
    return drupal_goto(EVENTS_ADMIN_MENU_DELETE . '/' . $id);
  }

  $values = events_prepare_values($form_state, 'edit');
  $qs = "";
  foreach ($values as $field => $value) {
    if (strlen($qs)) $qs .= ",";
    $qs .= " $field = " . db_quote($value);  
  }
  $sql = "UPDATE {events} SET $qs WHERE id = " . db_quote($id);

  db_query($sql);
  drupal_set_message(t('Event updated successfully.'));
  drupal_goto(EVENTS_ADMIN_MENU_LIST);
} // }}}

function events_form($event = null) { // {{{
  if ($event) {
    $form['id'] = array(
      '#type' => 'hidden',
      '#value' => $event['id'],
    );    
  }
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => @$event['title'],
    '#required' => true,
  );
  $form['start_date'] = array(
    '#type' => 'textfield',
    '#title' => t('Start date'),
    '#default_value' => substr(@$event['start_date'], 0, 10),
    '#maxlength' => 10,
    '#required' => true,
    '#description' => t('Date format: YYYY-MM-DD'),
  );
  $form['end_date'] = array(
    '#type' => 'textfield',
    '#title' => t('End date'),
    '#default_value' => substr(@$event['end_date'], 0, 10),
    '#maxlength' => 10,
    '#description' => t('Date format: YYYY-MM-DD. Leave empty if it is the same as start date.'),
  );
  $form['lang'] = array(
    '#type' => 'select',
    '#options' => Langs::languages(),
    '#title' => t('Language'),
    '#default_value' => isset($event['lang']) ? $event['lang'] : Langs::default_lang(),
    '#description' => t('Language of event\'s description.'),
    '#required' => true,
  );
  $form['node_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Node path'),
    '#description' => t('Relative path to existing node with detailed information about this event.'),
    '#default_value' => @$event['node_path'],
  );
  gallery_image_selector($form, 'image_id', @$event['image_id']);

  $form['body'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => @$event['body'],
    '#required' => true,
  );

  $form['format'] = filter_form();

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save changes'),
  );

  if ($event) {    
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
    );
  }

  $form['#validate'][] = 'events_form_validate';
  $form['#submit'][] = 'events_' . ($event ? 'edit' : 'add') . '_form_submit';

  drupal_add_js(drupal_get_path('module', 'events') . '/ui/ui.core.js');
  drupal_add_js(drupal_get_path('module', 'events') . '/ui/ui.datepicker.js');
  drupal_add_js(drupal_get_path('module', 'events') . '/ui/ui.draggable.js');
  drupal_add_js(drupal_get_path('module', 'events') . '/ui/ui.resizable.js');
  drupal_add_js(drupal_get_path('module', 'events') . '/ui/i18n/ui.datepicker-pl.js');

  drupal_add_js(drupal_get_path('module', 'events') . '/events.js');

  drupal_add_css(drupal_get_path('module', 'events') . '/themes/base/ui.all.css');
  drupal_add_css(drupal_get_path('module', 'events') . '/events.css');
  
  return $form;
} // }}}

function events_add_form() { // {{{
  drupal_set_title(t('Add event'));
  return events_form();  
} // }}}

function events_get_event($id) { // {{{
  return db_fetch_array(
           db_query('SELECT * FROM {events} WHERE id = %d', $id)
         );
} // }}}

function events_edit_form(&$form_state, $id) { // {{{
  $event = events_get_event($id);
  if (!$event) {
    drupal_set_message(t('Invalid event id supplied (%id)', array('%id' => $id)), 'error');
    drupal_set_title(t('Add event'));
    $event = null;
  }

  return events_form($event);
} // }}}

function events_delete_form_submit($form, &$form_state) { // {{{
  db_query("DELETE FROM {events} WHERE id = %d", $form_state['values']['id']);
  drupal_set_message(t('Event deleted successfully.'));
  drupal_goto(EVENTS_ADMIN_MENU_LIST);
} // }}}

function events_delete_form(&$form_state, $id) { // {{{
  $event = events_get_event($id);
  if (!$event) {
    drupal_set_message(t('Invalid event id supplied (%id)', array('%id' => $id)), 'error');
    return '';
  }
  
  $form = array();
  $form['id'] = array('#type' => 'hidden', '#value' => $id);
  $output = confirm_form($form,
                t('Are you sure you want to delete event (%event)?', array('%event' => $event['title'])),
                EVENTS_ADMIN_MENU_EDIT . '/' . $id,
                t('This action cannot be undone.'),
                t('Delete'),
                t('Cancel'));
  $output['#submit'][] = __FUNCTION__ . '_submit';
  return $output;
} // }}}

// vim: ft=php





  
