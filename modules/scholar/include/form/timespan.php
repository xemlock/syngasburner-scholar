<?php

function _2d($x) {
  return sprintf("%02d", $x);
}

function form_type_scholar_element_time_process($element)
{
    $mins  = drupal_map_assoc(range(0, 59, 5), '_2d');
    $hours = drupal_map_assoc(range(0, 23), '_2d');

    $element['hours'] = array(
        '#type'    => 'select',
        '#options' => $hours,        
    );

    $element['minutes'] = array(
        '#type'    => 'select',
        '#options' => $mins,
    );

    return $element;
}

function form_type_scholar_element_time_validate($element, &$form_state) // {{{
{
    $value = (array) $element['#value'];

    $hours = isset($value['hours']) ? strval($value['hours']) : '';
    $mins  = isset($value['minutes']) ? strval($value['minutes']) : '';

    $valid_hours = strlen($hours) && ctype_digit($hours) && $hours < 24;
    $valid_mins  = strlen($mins) && ctype_digit($mins) && $mins < 60;

    if ($valid_hours && $valid_mins) {
        return true;
    }

    if (!$valid_hours) {
        form_error($element['hours'], t('Invalid hours value.'));
    }
    if (!$valid_mins) {
        form_error($element['minutes'], t('Invalid minutes value.'));
    }

    form_error($element, t('Specified time is invalid.'));

    return false;
} // }}}

function theme_scholar_element_time($element) // {{{
{
    $value = scholar_theme_select($element['hours']) . ':'
           . scholar_theme_select($element['minutes']);

    return theme_form_element($element, $value);
} // }}}

function form_type_scholar_element_timespan_process($element) // {{{
{
    $element['#tree'] = true;
    $element['start'] = array('#type' => 'scholar_element_time');
    $element['end']   = array('#type' => 'scholar_element_time');
    return $element;
} // }}}

function form_type_scholar_element_timespan_validate($element, &$form_state) // {{{
{
    // albo wszystkie sa zerami, albo koniec jest pozniejszy niz poczatek
    $start_value = (array) $element['start']['#value'];
    $end_value   = (array) $element['end']['#value'];

    $start = 60 * $start_value['hours'] + $start_value['minutes'];
    $end   = 60 * $end_value['hours'] + $end_value['minutes'];

    // koniec przedzialu musi byc ostro wiekszy od poczatku,
    // chyba, ze oba sa zerami
    if (($start < $end) || (0 == $start && 0 == $end)) {
        return true;
    }

    form_error($element, t('@name: The specified time range is invalid.', array('@name' => $element['#title'])));
    return false;
} // }}}

function theme_scholar_element_timespan($element) // {{{
{
    $output = '';
    $output .= scholar_theme_select($element['start']['hours']) . ':' . scholar_theme_select($element['start']['minutes'])
            .  ' &ndash; '
            .  scholar_theme_select($element['end']['hours']) . ':' . scholar_theme_select($element['end']['minutes']);
    
    return theme_form_element($element, $output);
} // }}}

// vim: fdm=marker
