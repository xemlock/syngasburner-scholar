<?php

/**
 * @param array $element
 * @param mixed $post
 *     podtablica z wartościami dla tego elementu
 * @return array
 *     wartość checkboksa kontrolujacego ten kontener
 */
function form_type_scholar_checkboxed_container_value($element, $post = false) // {{{
{
    // nazwa pola w tablicy $post przechowujacej status zaznaczenia
    // checkboksa kontrolujacego ten kontener
    $checkbox_name = $element['#checkbox_name'];

    // stan zaznaczenia checkboksa gdy przeslano formularza jest pobrany
    // z wartosci klucza o nazwie podanej w #checkbox_name, albo pochodzi
    // z #default_value
    if ($post) {
        $value = isset($post[$checkbox_name]) && $post[$checkbox_name];
    } else {
        $value = isset($element['#default_value']) ? (bool) $element['#default_value'] : false;
    }

    // funkcja musi zwrocic tablice, zeby dzieci kontenera mogly wpisac
    // do niej swoje wartosci
    return array(
        $checkbox_name => intval($value)
    );
} // }}}

/**
 * Funkcja renderująca kontener.
 *
 * @param array $element
 * @return string
 */
function theme_scholar_checkboxed_container($element) // {{{
{
    // nazwa klucza odpowiadajacego wartosci zaznaczenia checkboksa
    $checkbox_name = $element['#checkbox_name'];

    // ustaw stan zaznaczenia checkboksa na podstawie wartosci znajdujacej
    // sie pod kluczem podanym w #checkbox_name
    $checked = isset($element['#value'][$checkbox_name]) && $element['#value'][$checkbox_name];

    $parents = $element['#parents'];
    if ($parents) {
        $name = array_shift($parents) 
              . ($parents ? '[' . implode('][', $parents) . ']' : '')
              . '[' .$checkbox_name . ']';
    } else {
        $name = $checkbox_name;
    }

    $output = '<div class="scholar-container" id="' . $element['#id'] . '-wrapper">'
            . '<div class="scholar-container-heading">'
            . '<label><input type="checkbox" name="' . $name .'" id="'.$element['#id'].'" value="1" onchange="$(\'#'.$element['#id'].'-wrapper > .scholar-container-content\')[this.checked ? \'show\' : \'hide\']()"' . ($checked ? ' checked="checked"' : ''). '/>' . $element['#title'] . '</label>'
            . '</div>'
            . '<div class="scholar-container-content">'
            . $element['#children']
            . '</div>'
            . '</div>';

    $output .= '<script type="text/javascript">/*<![CDATA[*/$(function(){
        if (!$("#'.$element['#id'].'").is(":checked")) {
            $("#'.$element['#id'].'-wrapper > .scholar-container-content").hide();
        }
})/*]]>*/</script>';

    return $output;
} // }}}

