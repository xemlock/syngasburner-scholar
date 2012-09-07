<?php

function scholar_element_submit($options = array()) // {{{
{
    $element = array(
        '#prefix' => '<div class="scholar-buttons">',
        '#suffix' => '</div>',
        'submit' => array(
            '#type' => 'submit',
            '#value' => isset($options['title']) ? $options['title'] : t('Submit'),
            '#attributes' => array('class' => 'scholar-button'),
        ),
    );

    if (isset($options['cancel'])) {
        if (isset($_GET['destination'])) {
            $url = $_GET['destination'];
        } else {
            $url = $options['cancel'];
        }

        $element['cancel'] = array(
            '#type'  => 'markup',
            '#value' => l(t('Cancel'), $url, array('attributes' => array('class' => 'scholar-cancel'))),
        );
    }

    return $element;
} // }}}

// vim: fdm=marker
