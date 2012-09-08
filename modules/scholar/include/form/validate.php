<?php

function scholar_form_validate_url($element, &$form_state) // {{{
{
    $value = (string) $element['#value'];

    if (strlen($value) && !scholar_validate_url($value)) {
       form_error($element, t('Please enter a valid absolute URL. Only HTTP and FTP protocols are allowed.')); 
    }
} // }}}

function scholar_form_validate_date($element, &$form_state) // {{{
{
    $value = (string) $element['#value'];

    if (strlen($value) && !scholar_parse_date($value)) {
        form_error($element, t('Please enter a valid date.'));
    }
} // }}}

function scholar_form_validate_publication_date($element, &$form_state) // {{{
{
    $value = (string) $element['#value'];

    if (strlen($value) && !scholar_validate_publication_date($value)) {
        form_error($element, t('Please enter a valid publication date.'));
    }
} // }}}

/**
 * @param array $element1
 * @param array $element2
 * @return false|array
 *     jeżeli walidacja się powiedzie zwrócona zostaje tablica dwuelementowa
 *     zawierająca odpowiednio datę początku i datę końca w formacie YYYY-MM-DD>
 */
function scholar_form_validate_date_range($element1, $element2)
{
    $value1 = (string) $element1['#value'];
    $value2 = (string) $element2['#value'];

    $have1 = strlen($value1);
    $have2 = strlen($value2);

    // dokonaj walidacji tylko jezeli podano przynajmniej jedna z dat
    if ($have1 || $have2) {
        if (!$have1 || !($date1 = scholar_parse_date($value1))) {
            // podano date konca, nie podano daty poczatku
            // lub podana data poczatku jest niepoprawna
            form_error($element1, t('Please enter a valid start date.'));
            return false;
        }

        if (!$have2 || !($date2 = scholar_parse_date($value2))) {
            // podano date poczatku, nie podano daty konca
            // lub podana data konca jest niepoprawna
            form_error($element2, t('Please enter a valid end date.'));
            return false;
        }

        // podano poprawne daty konca i poczatku, trzeba sprawdzic, czy
        // poczatek jest mniejszy rowny dacie konca.
        // Dopelniamy daty do pelnego formatu YYYY-MM-DD
        $time1 = $date1['iso'] . substr('0001-01-01', strlen($date1['iso']));
        $time2 = $date2['iso'] . substr('0001-01-01', strlen($date2['iso']));

        if (strcmp($time1, $time2) > 0) {
            // poniewaz data poczatku jest poprawna data, zaznacz jako niepoprawny
            // element zawierajacy date konca
            form_error($element2, t('Invalid date range specified. Start date must be earlier than end date.'));
            return false;
        }

        return array($time1, $time2);
    }

    return false;
}

// vim: ft=php
