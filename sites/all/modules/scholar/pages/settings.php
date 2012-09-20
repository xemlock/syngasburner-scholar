<?php

function scholar_pages_settings_form(&$form_state) // {{{
{
    $image = array(
        '#type'  => 'scholar_element_vtable_row',
        '#title' => t('Images'),
        '#description' => t('Configure preface images'),
        scholar_setting_name('image_width') => array(
            '#title'         => t('Image width'),
            '#type'          => 'textfield',
            '#required'      => true,
            '#description'   => t('Width of images shown in the preface of auto-generated pages. Minimum width is 150px.'),
            '#maxlength'     => 3,
            '#size'          => 24,
            '#field_suffix'  => 'px',
            '#default_value' => scholar_setting('image_width'),
        ),
        scholar_setting_name('image_lightbox') => array(
            '#title'         => 'Lightbox',
            '#type'          => 'radios',
            '#required'      => true,
            '#description'   => t('When enabled, preface images will be automatically processed by Lightbox (if available).'),
            '#default_value' => scholar_setting('image_lightbox'),
            '#options'       => array(1 => t('Enabled'), 0 => t('Disabled')),
        ),
    );

    $dateformat = array(
        '#type'  => 'scholar_element_vtable_row',
        '#title' => t('Date formats'),
        '#description' => t('Configure how dates are displayed'),
        array(
            '#type' => 'markup',
            '#value' => "<p>Date formats use the same notation as the <a href=\"http://www.php.net/manual/en/function.date.php\" target=\"_blank\">PHP date function</a>. However only subset of placeholders is supported.</p>",
        ),
    );

    foreach (scholar_languages() as $language => $name) {
        $fieldset = scholar_form_fieldset(array(
            '#title' => scholar_language_label($language, $name),
            '#collapsible' => true,
            '#collapsed' => $language != scholar_language(),
        ));

        $fieldset[scholar_setting_name('format_date', $language)] = array(
            '#title' => 'Single date',
            '#type' => 'textfield',
            '#size' => 24,
            '#required' => true,
            '#description' => t('Format for displaying a single date.'),
            '#default_value' => scholar_setting('format_date', $language),
        );

        $format = scholar_setting('format_daterange_same_month', $language);
        $fieldset[scholar_setting_name('format_daterange_same_month', $language)] = array(
            '#tree' => true,
            scholar_form_tablerow_open(array(
                '#prefix' => theme_scholar_label(t('Same month date range'), true),
            )),
            'start_date' => array(
                '#type' => 'textfield',
                '#required' => true,
                '#size' => 24,
                '#maxlength' => 16,
                '#theme' => 'scholar_textfield',
                '#attributes' => array('title' => t('Start date format')),
                '#title' => t('Same month start date format'), // Format daty początkowej pojedynczego miesiąca
                '#default_value' => $format['start_date'],
            ),
            scholar_form_tablerow_next(),
            array('#type' => 'markup', '#value' => '&ndash;'),
            scholar_form_tablerow_next(),
            'end_date' => array(
                '#type' => 'textfield',
                '#required' => true,
                '#size' => 24,
                '#maxlength' => 16,
                '#theme' => 'scholar_textfield',
                '#attributes' => array('title' => t('End date format')),
                '#title' => t('Same month end date format'),
                '#default_value' => $format['end_date'],
            ),
            scholar_form_tablerow_next(),
            array('#type' => 'markup', '#value' => '<!-- format preview -->'),
            scholar_form_tablerow_close(array(
                '#suffix' => theme_scholar_description(t('Format for displaying a date range contained within a single month.')),
            )),
        );

        $format = scholar_setting('format_daterange_same_year', $language);
        $fieldset[scholar_setting_name('format_daterange_same_year', $language)] = array(
            '#tree' => true,
            scholar_form_tablerow_open(array(
                '#prefix' => theme_scholar_label(t('Same year date range'), true),
            )),
            'start_date' => array(
                '#type' => 'textfield',
                '#required' => true,
                '#size' => 24,
                '#maxlength' => 16,
                '#theme' => 'scholar_textfield',
                '#attributes' => array('title' => t('Start date format')),
                '#title' => t('Same year start date format'), // Format daty początkowej tego samego roku
                '#default_value' => $format['start_date'],
            ),
            scholar_form_tablerow_next(),
            array('#type' => 'markup', '#value' => '&ndash;'),
            scholar_form_tablerow_next(),
            'end_date' => array(
                '#type' => 'textfield',
                '#required' => true,
                '#size' => 24,
                '#maxlength' => 16,
                '#theme' => 'scholar_textfield',
                '#attributes' => array('title' => t('End date format')),
                '#title' => t('Same year end date format'),
                '#default_value' => $format['end_date'],
            ),
            scholar_form_tablerow_next(),
            array('#type' => 'markup', '#value' => '<!-- format preview -->'),
            scholar_form_tablerow_close(array(
                '#suffix' => theme_scholar_description(t('Format for displaying a date range contained within a single year, across different months.')),
            )),
        );

        $dateformat[] = $fieldset;
    }

    $formats = array();
    foreach (filter_formats() as $format) {
        $formats[$format->format] = $format->name;
    }
    $nodes = array(
        '#type'  => 'scholar_element_vtable_row',
        '#title' => t('Nodes'),
        '#description' => t('Configure how pages are displayed'),
        scholar_setting_name('node_format') => array(
            '#title'   => t('Node format'),
            '#type'    => 'select',
            '#options' => $formats,
            '#description' => t('Input format of auto-generated nodes. For best results, use a format with no filters enabled.'),
            '#default_value' => scholar_setting('node_format'),
        ),
        scholar_setting_name('node_cache') => array(
            '#title'   => t('Rendering cache'),
            '#type'    => 'radios',
            '#options' => array(1 => t('Enabled'), 0 => t('Disabled')),
            '#description' => t('When enabled auto-generated node content will be cached. For performance reasons cache should be enabled in production environments.'),
            '#default_value' => scholar_setting('node_cache'),
        ),
    );

    $vtable = array(
        '#type' => 'scholar_element_vtable',
        'images' => $image,
        'dateformat' => $dateformat,
        'nodes' => $nodes,
    );

    $form = array();
    $form['vtable'] = $vtable;

    $form = system_settings_form($form);
    array_unshift($form['#submit'], 'scholar_pages_settings_form_submit');

    $form['buttons']['#prefix'] = '<div class="scholar-buttons">';
    $form['buttons']['#suffix'] = '</div>';

    // TODO preview
    $form[] = array(
        '#type' => 'markup',
        '#value' => '',
    );

    return $form;
} // }}}

function scholar_pages_settings_form_validate($form, &$form_state) // {{{
{
    // rekursywnie usun biale znaki otaczajace wartosci tekstowe
    $values = scholar_trim($form_state['values']);

    // skonwertuj do liczb calkowitych, bez dalszej walidacji:
    // - ustawienie dla Lightboksa
    // - ustawienia dla wezlow (id formatu, uruchomienie cache'u)
    $int_settings = array(
        'image_width', 'image_lightbox', 'node_format', 'node_cache',
    );

    foreach ($int_settings as $setting) {
        $setting_name = scholar_setting_name($setting);
        $values[$setting_name] = intval($values[$setting_name]);

        switch ($setting) {
            case 'image_width':
                if ($values[$setting_name] < 150) {
                    form_error($form['vtable']['images'][$setting_name], t('Image width must be an integer value greater than or equal to 150px.'));
                }
                break;
        }
    }

    $form_state['values'] = $values;
} // }}}

function scholar_pages_settings_form_submit($form, &$form_state) // {{{
{
    // kazda zmiana ustawien uniewaznia rendering
    scholar_invalidate_rendering();

    // zaktualizuj format danych dla wezlow typu scholar
    scholar_set_node_format($form_state['values'][scholar_setting_name('node_format')]);
} // }}}

function scholar_pages_settings_dateformat()
{
    // DATEFORMAT PREVIEW
}

// vim: fdm=marker
