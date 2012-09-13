<?php

// Trzeba troche przekonwertowac strukture elementu, poniewaz
// ustawienie maxlength wymusza liczenie substringa z wartosci,
// co w przypadku tablicy wywala blad.
function form_type_scholar_element_langtext_process($element) // {{{
{
    if (isset($element['#maxlength'])) {
        $element['#element_maxlength'] = max(0, $element['#maxlength']);
        unset($element['#maxlength']);
    } else {
        $element['#element_maxlength'] = 0;
    }

    return $element;
} // }}}

function form_type_scholar_element_langtext_validate($element, &$form_state) // {{{
{
    // jezeli required musza byc ustawione wartosci dla wszystkich jezykow
    if ($element['#required']) {
        $values = $element['#value'];

        foreach (scholar_languages() as $language => $name) {
            $value = isset($values[$language]) ? strval($values[$language]) : '';

            if (0 == strlen($value) || ctype_space($value)) {
                $parents = $element['#parents'];
                $parents[] = $language;

                form_set_error(implode('][', $parents),
                    t('!name for language !language is required.', array('!name' => $element['#title'], '!language' => $name))
                );
            }
        }
    }
} // }}}

function form_type_scholar_element_langtext_value($element, $post = false) // {{{
{
    if (false === $post) {
        if ($element['#default_value']) {
            $post = (array) $element['#default_value'];
        }
    } else {
        $post = (array) $post;
    }

    $value = array();

    foreach (scholar_languages() as $language => $name) {
        $value[$language] = isset($post[$language]) ? $post[$language] : null;
    }

    return $value;
} // }}}

function theme_scholar_element_langtext($element) // {{{
{
    $output = '<div class="scholar-element-langtext"><table>';

    foreach (scholar_languages() as $language => $name) {
        $id = $element['#id'] . '-' . $language;

        $textfield = array(
            '#name'      => $element['#name'] . '[' . $language . ']',
            '#id'        => $id,
            '#value'     => $element['#default_value'][$language],
            '#maxlength' => $element['#element_maxlength'] ? $element['#element_maxlength'] : null,
        );

        $output .= '<tr>'
                .  '<td>' . scholar_language_label($language, $name . ':') . '</td>'
                .  '<td width="100%">' . theme_scholar_textfield($textfield) . '</td>'
                .  '<td><span class="scholar-character-countdown" data-id="' . $id . '" title="' . t('Number of characters remaining') . '"></span></td>'
                .  '</tr>';
    }

    $output .= '</table></div>';

    return scholar_theme_element($element, $output);
} // }}}

// vim: fdm=marker
