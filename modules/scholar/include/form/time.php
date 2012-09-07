<?php

function form_type_scholar_element_time_value($element, $post = false) // {{{
{
    $hour   = '';
    $minute = '';

    if (false === $post && isset($element['#default_value'])) {
        if (is_string($element['#default_value'])) {
            $parts = explode(':', $element['#default_value']);
            $post = array(
                'hour'   => array_shift($parts),
                'minute' => array_shift($parts),
            );
        } else if (is_array($element['#default_value'])) {
            $post = $element['#default_value'];
        }
    }

    if ($post) {
        if (isset($post['hour'])) {
            $hour = (string) $post['hour'];
        }
        if (isset($post['minute'])) {
            $minute = (string) $post['minute'];
        }
    }

    // nadaj wartosc poszczegolnym czesciom tylko jezeli podano godziny i minuty,
    // w przeciwnym razie uznaj, ze pole nie ma nadanej wartosci
    if (strlen($minute) || strlen($hour)) {
        return array(
            'hour'   => $hour,
            'minute' => $minute,
            'time'   => $hour . ':' . $minute,
        );
    }

    return array();
} // }}}

/**
 * Process jest uruchamiany po _value. includes/form.inc _form_builder_handle_input_element()
 */
function form_type_scholar_element_time_process($element) // {{{
{
    static $format = null;

    if (null === $format) {
        $format = create_function('$x', 'return sprintf("%02d", $x);');
    }

    $minhour = 0;
    $maxhour = 23;

    if (isset($element['#minhour'])) {
        $minhour = max(0, $element['#minhour']);
    }

    if (isset($element['#maxhour'])) {
        $maxhour = max(0, min(23, $element['#maxhour']));
    }

    $hour = array('' => 'HH') + drupal_map_assoc(array_map($format, range($minhour, $maxhour)));
    $mins  = array('' => 'MM') + drupal_map_assoc(array_map($format, range(0, 59, 5)));

    $value = (array) $element['#value'];

    $element['hour'] = array(
        '#type'    => 'select',
        '#options' => $hour,
        '#value'   => isset($value['hour']) ? $value['hour'] : null,
    );

    $element['minute'] = array(
        '#type'    => 'select',
        '#options' => $mins,
        '#value'   => isset($value['minute']) ? $value['minute'] : null,
    );

    return $element;
} // }}}

function form_type_scholar_element_time_validate($element, &$form_state) // {{{
{
    $value = $element['#value'];

    if (count($value)) {
        $hour = isset($value['hour']) ? strval($value['hour']) : '';
        $mins  = isset($value['minute']) ? strval($value['minute']) : '';

        $valid_hour = strlen($hour) && ctype_digit($hour) && $hour < 24;
        $valid_mins  = strlen($mins) && ctype_digit($mins) && $mins < 60;

        if (!$valid_hour || !$valid_mins) {
            form_error($element, t('Specified time is invalid.'));
        }
    }
} // }}}

function theme_scholar_element_time($element) // {{{
{
    $value = scholar_theme_select($element['hour']) . ':'
           . scholar_theme_select($element['minute']);

    return theme_form_element($element, $value);
} // }}}


function form_type_scholar_element_timespan_process($element) // {{{
{
    $default_value = isset($element['#default_value']) ? (array) $element['#default_value'] : array();

    $element['#tree'] = true;
    $element['date']  = array(
        '#type'      => 'textfield',
        '#maxlength' => 10,
        '#size'      => 16,
        '#attributes' => array('class' => 'form-date'),
        '#default_value' => isset($default_value['date']) ? $default_value['date'] : null,
    );

    $minhour = isset($element['#minhour']) ? $element['#minhour'] : null;
    $maxhour = isset($element['#maxhour']) ? $element['#maxhour'] : null;

    $element['start'] = array(
        '#type' => 'scholar_element_time',
        '#minhour' => $minhour,
        '#maxhour' => $maxhour,
        '#default_value' => isset($default_value['start']) ? $default_value['start'] : null,
    );
    $element['end']   = array(
        '#type' => 'scholar_element_time',
        '#minhour' => $minhour,
        '#maxhour' => $maxhour,
        '#default_value' => isset($default_value['end']) ? $default_value['end'] : null,
    );
    return $element;
} // }}}

function form_type_scholar_element_timespan_value($element, $post = false) // {{{
{
    if (false === $post) {
        $post = isset($element['#default_value'])
              ? $element['#default_value']
              : array();
    }

    $post = (array) $post;

    // Kiepsko, ze w drupalu kontenery przetwarzane sa w kolejnosci pre-order
    // (najpierw wartosc kontenera, dopiero pozniej wartosci jego dzieci).
    // Funkcja wyliczajaca wartosc pol time bedzie wywolana dwa razy. Trudno.
    $date  = isset($post['date']) ? (string) $post['date'] : '';
    $start = isset($post['start'])
           ? form_type_scholar_element_time_value($element['start'], $post['start'])
           : null;
    $end   = isset($post['end'])
           ? form_type_scholar_element_time_value($element['end'], $post['end'])
           : null;

    if (empty($date) && empty($start) && empty($end)) {
        return array();
    }

    return array(
        'date'       => $date,
        'start_time' => $start ? $start['time'] : '',
        'end_time'   => $end ? $end['time'] : '',
    );
} // }}}

function form_type_scholar_element_timespan_validate($element, &$form_state) // {{{
{
    $value = $element['#value'];

    if ($value) {
        $date  = scholar_parse_date($value['date']);
        $start = scholar_parse_time($value['start_time']);
        $end   = scholar_parse_time($value['end_time']);

        if (empty($date)) {
            form_error($element['date'], t('@name: Invalid date supplied.', array('@name' => $element['#title'])));
        }

        if (empty($start)) {
            form_error($element['start'], t('@name: Invalid start time specified.', array('@name' => $element['#title'])));
        }

        if (empty($end)) {
            form_error($element['end'], t('@name: Invalid end time specified.', array('@name' => $element['#title'])));
        }

        if ($start && $end) {
            $diff  = 60 * $end['hour'] + $end['minute']
                   - 60 * $start['hour'] + $start['minute'];

            if ($date && $diff > 0) {
                return true;
            }

            form_error($element['end'], t('@name: Invalid time range specified.', array('@name' => $element['#title'])));
        }
    }
} // }}}

function theme_scholar_element_timespan($element) // {{{
{
    $output = scholar_theme_textfield($element['date'])
            . ' &nbsp; '
            . scholar_theme_select($element['start']['hour']) . ':' . scholar_theme_select($element['start']['minute'])
            . ' &ndash; '
            . scholar_theme_select($element['end']['hour']) . ':' . scholar_theme_select($element['end']['minute']);

    return theme_form_element($element, $output);
} // }}}

// vim: fdm=marker
