<?php

function scholar_settings_form(&$form_state)
{
    $form = array();

    $form['scholar_img_width'] = array(
        '#title' => 'Default image width',
        '#type'        => 'textfield',
        '#maxlength'   => 3,
        '#description' => t('Default width of an image shown in the preface of an auto-generated page.'),
        '#default_value' => 120,
    );

    $form['scholar_date_single'] = array(
        '#title' => 'Single date format',
        '#type' => 'textfield',
    );
    $form['scholar_date_same_month_from'] = array(
        '#title' => 'From',
        '#type' => 'textfield',
    );
    $form['scholar_date_same_month_to'] = array(
        '#title' => 'To',
        '#type' => 'textfield',
    );
    $form['scholar_date_same_year_from'] = array(
        '#title' => 'From',
        '#type' => 'textfield',
    );
    $form['scholar_date_same_year_to'] = array(
        '#title' => 'To',
        '#type' => 'textfield',
    );

    $form = system_settings_form($form);

    $form['buttons']['#prefix'] = '<div class="scholar-buttons">';
    $form['buttons']['#suffix'] = '</div>';

    return $form;
}

function scholar_settings_form_validate($form, &$form_state)
{
    if (!ctype_digit($form_state['values']['scholar_img_width'])) {
        form_error($form['scholar_img_width'], t('Image width must be a positive integer value.'));
    }
}

// vim: fdm=marker
