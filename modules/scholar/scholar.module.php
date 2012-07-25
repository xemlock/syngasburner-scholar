<?php

function scholar_form_alter(&$form, &$form_state, $form_id)
{
    // Nie dopuszczaj do bezposredniej modyfikacji wezlow
    // aktualizowanych automatycznie przez modul scholar.
    // Podobnie z wykorzystawymi eventami.
    $node = $form['#node'];
    echo '<pre>', __FUNCTION__, ': ', $form_id, '</pre>';
}

function scholar_nodeapi($node, $op)
{
    if ($op == 'load') {
//        echo '<pre>', $op, ': ', print_r($node, 1), '</pre>';
    }
}
