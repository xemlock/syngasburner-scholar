<?php

function scholar_settings_form(&$form_state)
{
    $form = array();

    $form[scholar_setting_name('image_width')] = array(
        '#title' => 'Image width',
        '#type'        => 'textfield',
        '#field_suffix' => 'px',
        '#maxlength'   => 3,
        '#description' => t('Width of images shown in the preface of auto-generated pages.'),
        '#size' => 24,
        '#required' => true,
    );

    $form[] = array(
        '#type' => 'markup',
        '#value' => "<p>Date formats use the same notation as the <a href=\"http://www.php.net/manual/en/function.date.php\" target=\"_blank\">PHP date function</a>.</p>",
    );

    foreach (scholar_languages() as $language => $name) {
        $fieldset = array(
            '#type' => 'fieldset',
            '#title' => scholar_language_label($language, $name),
            '#collapsible' => true,
            '#collapsed' => $language != scholar_language(),
        );

        $fieldset[scholar_setting_name('format_date', $language)] = array(
            '#title' => 'Single date',
            '#type' => 'textfield',
            '#size' => 24,
            '#required' => true,
            '#description' => t('Format for displaying a single date.'),
            '#default_value' => scholar_setting_format_date($language),
        );

        $fieldset[scholar_setting_name('format_daterange_same_month', $language)] = array(
            '#tree' => true,
            scholar_form_tablerow_open(array(
                '#prefix' => theme_scholar_label(t('Same month date range'), true),
            )),
            'start_date' => array(
                '#type' => 'textfield',
                '#required' => true,
                '#size' => 24,
                '#theme' => 'scholar_textfield',
                '#attributes' => array('title' => t('Start date format')),
                '#title' => t('Same month start date format'), // Format daty początkowej pojedynczego miesiąca
            ),
            scholar_form_tablerow_next(),
            array('#type' => 'markup', '#value' => '&ndash;'),
            scholar_form_tablerow_next(),
            'end_date' => array(
                '#type' => 'textfield',
                '#required' => true,
                '#size' => 24,
                '#theme' => 'scholar_textfield',
                '#attributes' => array('title' => t('End date format')),
                '#title' => t('Same month end date format'),
            ),
            scholar_form_tablerow_next(),
            array('#type' => 'markup', '#value' => '<!-- format preview -->'),
            scholar_form_tablerow_close(array(
                '#suffix' => theme_scholar_description(t('Format for displaying a date range contained within a single month.')),
            )),
        );

        $fieldset[scholar_setting_name('format_daterange_same_year', $language)] = array(
            '#tree' => true,
            scholar_form_tablerow_open(array(
                '#prefix' => theme_scholar_label(t('Same year date range'), true),
            )),
            'start_date' => array(
                '#type' => 'textfield',
                '#required' => true,
                '#size' => 24,
                '#theme' => 'scholar_textfield',
                '#attributes' => array('title' => t('Start date format')),
                '#title' => t('Same year start date format'), // Format daty początkowej tego samego roku
            ),
            scholar_form_tablerow_next(),
            array('#type' => 'markup', '#value' => '&ndash;'),
            scholar_form_tablerow_next(),
            'end_date' => array(
                '#type' => 'textfield',
                '#required' => true,
                '#size' => 24,
                '#theme' => 'scholar_textfield',
                '#attributes' => array('title' => t('End date format')),
                '#title' => t('Same year end date format'),
            ),
            scholar_form_tablerow_next(),
            array('#type' => 'markup', '#value' => '<!-- format preview -->'),
            scholar_form_tablerow_close(array(
                '#suffix' => theme_scholar_description(t('Format for displaying a date range contained within a single year, across different months.')),
            )),
        );

        $form[] = $fieldset;
    }

    $form = system_settings_form($form);
    array_unshift($form['#submit'], 'scholar_settings_form_submit');

    $form['buttons']['#prefix'] = '<div class="scholar-buttons">';
    $form['buttons']['#suffix'] = '</div>';

    return $form;
}

function scholar_settings_form_validate($form, &$form_state)
{
    $values = &$form_state['values'];

    if (!ctype_digit($values['scholar_img_width'])) {
        form_error($form['scholar_img_width'], t('Image width must be a positive integer value.'));
    } else {
        $values['scholar_img_width'] = intval($values['scholar_img_width']);
    }
p($values);
}

function scholar_settings_form_submit($form, &$form_state)
{

}

function scholar_settings_dateformat()
{
    
}

// vim: fdm=marker
