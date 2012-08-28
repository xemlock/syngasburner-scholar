<?php

/**
 * Tworzy strukturę elementu edycji powiązanych wydarzeń. Modyfikacji struktury
 * można dokonać przekazując odpowiednie wartości we właściwości #fields
 * elementu.
 *
 * #fields to opcjonalna tablica z dodatkową specyfikacją pól elementu, w której
 *     kluczami są nazwy predefiniowanych pól. 
 *     Podając jako wartość false pole o nazwie równej kluczowi nie zostanie
 *     dodane do wynikowego formularza. Jeżeli podano tablicę, zostanie ona
 *     scalona z predefiniowaną tablicą definiującą pole. Jeżeli podano wartość
 *     typu string zostanie ona ustawiona jako tytuł pola. Wartości innych
 *     typów nie będą miały wpływu na kształt formularza.
 * @return array
 *     tablica reprezentująca strukturę elementu
 */
function form_type_scholar_element_events_process($element) // {{{
{
    // predefiniowane pola formularza edycji eventow, podajac w tablicy
    // $fields wartosc false pole nie zostanie dodane do formularza
    $fields = array(
        'start_date' => array(
            '#type'          => 'textfield',
            '#title'         => t('Start date'),
            '#maxlength'     => 10,
            '#description'   => t('Date format: YYYY-MM-DD.'),
        ),
        'end_date' => array(
            '#type'          => 'textfield',
            '#title'         => t('End date'),
            '#maxlength'     => 10,
            '#description'   => t('Date format: YYYY-MM-DD. Leave empty if it is the same as the start date.'),
        ),
        'title' => array(
            '#type'          => 'textfield',
            '#title'         => t('Title'),
            '#maxlength'     => 255,
            '#description'   => t('If not given title of referenced record will be used.'),
        ),
        'body' => array(
            '#type'          => 'scholar_textarea',
            '#title'         => t('Description'),
            '#description'   => t('Detailed description about this event.'),
        ),
    );

    if ($element['#fields']) {
        foreach ((array) $element['#fields'] as $key => $value) {
            if (!isset($fields[$key])) {
                continue;
            }

            // jezeli podano false jako wartosc pola, nie dodawaj tego pola
            if (false === $value) {
                $fields[$key] = false;

            } else if (is_array($value)) {
                $fields[$key] = array_merge($fields[$key], $value);

            } else if (is_string($value)) {
                $fields[$key]['#title'] = $value;
            }
        }
    }

    // aby nie dodawac wybranego pola nalezy podac jego nazwe w kluczu, zas
    // jako wartosc podac false. Jezeli podano jako wartosc tablice, zostanie
    // ona scalona z predefiowana tablica opisujaca pole. Jezeli podano
    // wartosc typu string, zostanie ona ustawiona jako tytul pola. Wartosci
    // innych typow zostana zignorowane podczas dodawania pola.

    // wypelnij wszystkie pola niezbedne dla form_buildera
    $element_fields = array();

    if (false !== $fields['start_date']) {
        $element_fields['start_date'] = $fields['start_date'];

        // dodaj walidacje poczatku, poniewaz jest element z data poczatku.
        // Jezeli go nie ma, zakladamy, ze walidacja bedzie przeprowadzona
        // gdzie indziej.
        $element_fields['start_date']['#element_validate'] = array('form_type_scholar_element_events_validate');
    }

    if (false !== $fields['end_date']) {
        $element_fields['end_date'] = $fields['end_date'];
    }

    // dodaj kontener z polami na tytul lub tresc, jezeli pozwolono na dodanie
    // przynajmniej jednego z tych pol

    $add_title = false !== $fields['title'];
    $add_body  = false !== $fields['body'];

    if ($add_title || $add_body) {
        foreach (scholar_languages() as $code => $name) {
            $element_fields[$code] = array(
                '#type'          => 'scholar_checkboxed_container',
                '#checkbox_name' => 'status',
                '#title'         => 'Add event in language: ' . scholar_language_label($code, $name),
                '#tree'          => true,
            );

            if ($add_title) {
                $element_fields[$code]['title'] = $fields['title'];
            }

            if ($add_body) {
                $element_fields[$code]['body'] = $fields['body'];
            }
        }
    }

    $element['#fields'] = $element_fields;

    return $element;
} // }}}

/**
 * Wartością elementu są dane wydarzeń, których kluczami są kody języka.
 * Niestety form_builder nadpisuje wartości dla kontenerów. Więc trzeba
 * sobie z tym poradzić.
 * @return array
 */
function form_type_scholar_element_events_value($element, $post = false) // {{{
{
    $value = array();

    if (false === $post) {
        if ($element['#default_value']) {
            $post = $element['#default_value'];
        }
    } else {
        // ze wzgledu na strukture formularza trzeba do kazdej tablicy
        // reprezentujacej pojedyncze wydarzenie trzeba wpisac daty,
        // znajdujace sie na najwyzszym poziomie przeslanej tablicy
        $start_date = isset($post['start_date']) ? $post['start_date'] : null;
        $end_date   = isset($post['end_date'])   ? $post['end_date']   : null;

        foreach (scholar_languages() as $language => $name) {
            $post[$language]['start_date'] = $start_date;
            $post[$language]['end_date']   = $end_date;
        }
    }

    if ($post) {
        $keys = array('status', 'start_date', 'end_date', 'title', 'body');
        foreach (scholar_languages() as $language => $name) {
            if (isset($post[$language])) {
                $value[$language] = array();

                foreach ($keys as $key) {
                    $value[$language][$key] = isset($post[$language][$key])
                                            ? $post[$language][$key]
                                            : null;
                }
            }
        }
    }

    return $value;
} // }}}

/**
 * Sprawdza, czy gdy wybrano utworzenie rekordu wydarzenia (w przynajmniej
 * jedym języku), podano również datę jego początku.
 *
 * @param array $element
 * @param array &$form_state
 */
function form_type_scholar_element_events_validate($element, &$form_state) // {{{
{
    // Jezeli w formularzu znajduje sie pole daty poczatku wydarzenia,
    // i gdy ma zostac utworzony rekord wydarzenia dla przynajmniej
    // jednego jezyka, wymagaj podania daty poczatku.
    if ($element['#value']) {
        foreach ($element['#value'] as $language => $event) {
            if ($event['status'] && 0 == strlen($event['start_date'])) {
                // zgodnie z dokumentacja dla form_set_error nazwy
                // elementow zagniezdzonych przechowywane sa jako
                // zlepek wartosci w #parents sklejonych znakami ][
                $parents = $element['#parents'];
                $parents[] = 'start_date';

                // form_set_error operuje na statycznej tablicy, wspolnej
                // dla wszystkich formularzy na stronie
                form_set_error(implode('][', $parents), t('Event start date is required.'));
                break;
            }
        }
    }
} // }}}

/**
 * Generuje HTML reprezentujący element formularza do edycji wydarzeń.
 *
 * @param array $element
 * @return string
 */
function theme_scholar_element_events($element) // {{{
{
    // przygotuj elementy tak, aby zawieraly wszystkie niezbedne
    // wlasciwosci i mogly zostac bezpiecznie wyrenderowane
    $fields = $element['#fields'];

    $fields['#tree'] = true;
    $fields['#name'] = $element['#name'];
    $fields['#parents'] = $element['#parents'];
    $fields['#post'] = $element['#post'];

    // ponadto trzeba przekazac wartosci elementom
    foreach ($element['#value'] as $language => $event) {
        if (isset($fields['start_date'])) {
            $fields['start_date']['#value'] = $event['start_date'];
        }

        if (isset($fields['end_date'])) {
            $fields['end_date']['#value'] = $event['end_date'];
        }

        $fields[$language]['#default_value']  = $event['status'];
        $fields[$language]['title']['#value'] = $event['title'];
        $fields[$language]['body']['#value']  = $event['body'];
    }

    $form_state = array();
    $fields = form_builder(__FUNCTION__, $fields, $form_state);

    // trzeba recznie wyrenderowac pola. Gdyby chciec skorzystac
    // z automatycznego renderingu, po prostu dodajac dodatkowe
    // pola jako dzieci elementu (np. w za pomoca funkcji #process),
    // podczas pobierania wartosci elementu zostalaby ona
    // nadpisywana przez wartosci dzieci.

    return drupal_render($fields);
} // }}}

// vim: fdm=marker
