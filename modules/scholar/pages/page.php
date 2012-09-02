<?php

/**
 * Lista stron.
 *
 * @return string
 */
function scholar_page_list() // {{{
{
    // pojedynczy element w podmenu z typem MENU_DEFAULT_LOCAL_TASK
    // nie tworzy tabow. Menu dla stron zlozone jest z pojedynczego
    // elementu MENU_LOCAL_TASK, i rowniez nie ma zadnych tabow.
    // Dodajemy wiec, by strona wygladala podobnie do innych list.
    scholar_add_tab(t('list'), scholar_admin_path('page'));

    // poniewaz element menu odpowiadajacy tej stronie ma typ
    // MENU_LOCAL_TASK, jego bezposredni tytul nie jest brany pod uwage,
    // uzywany jest tytul jego rodzica. Stos wywolan ustawiajacych tytul:
    //    menu_get_active_title (menu.inc)
    //    drupal_get_title (path.inc)
    //    template_preprocess_page (theme.inc)
    // Dlatego tytul musi byc ustawiony recznie
    drupal_set_title(t('Pages'));

    $header = array(
        array('data' => t('Title')),
        array('data' => t('Published')),
        array('data' => t('Operations')),
    );

    $query = scholar_pages_recordset($sql);
    $rows  = array();

    $num_languages = count(scholar_languages());

    // dla kazdego wiersza podaj ile wersji jezykowych wezlow odpowiadajacych
    // danej stronie jest opublikowanych
    while ($row = db_fetch_array($query)) {
        $published = intval($row['published']);

        if ($published) {
            $published_html = '<em>' . t('Yes') . '</em>';

            if ($published < $num_languages) {
                $published_html .= ' (' . $published . '/' . $num_languages . ')';
            }

        } else {
            $published_html = '<em>' . t('No') . '</em>';
        }

        $rows[] = array(
            t($row['title']),
            $published_html,
            l(t('edit'), scholar_admin_path('page/edit/' . $row['id'])),
        );
    }

    // posortuj nazwy stron wzgledem tlumaczen ich tytulow
    if ('en' != $language->language) {
        scholar_asort($rows, create_function('$a, $b', 'return strcoll($a[0], $b[0]);'));
    }

    foreach ($rows as &$row) {
        $row[0] = check_plain($row[0]);
    }

    if (empty($rows)) {
        $rows[] = array(
            array('data' => t('No records found'), 'colspan' => 3)
        );
    }

    $help = '<div class="help">'
          . t('Below is the list of available special pages automatically generated by the Scholar module.')
          . '</div>';

    return $help . scholar_theme_table($header, $rows);
} // }}}

/**
 * Formularz edycji strony.
 *
 * @param array &$form_state
 * @param int $id
 * @return array
 */
function scholar_page_form(&$form_state, $id) // {{{
{
    $page = scholar_load_record('pages', $id, scholar_admin_path('page'));

    scholar_add_tab(t('list'), scholar_admin_path('page'));
    scholar_add_tab(t('edit'), scholar_admin_path('page/edit/' . $page->id));

    // wczytaj tlumaczenie tytulu strony, bedzie on uzyty w komunikacie
    // o zaktualizowaniu rekordu.
    $page->title = t($page->title);

    $form = scholar_generic_form(array(
        'title' => array(
            '#disabled' => true,
            '#description' => t('Page title was set internally and cannot be changed.'),
        ),
        'nodes',
        'files',
    ), $page);

    $form['submit'] = array(
        '#type'  => 'submit',
        '#value' => t('Save changes'),
    );
    $form['cancel'] = array(
        '#type'  => 'scholar_element_cancel',
        '#value' => scholar_admin_path('page'),
    );

    return $form;
} // }}}

/**
 * Zapisuje nowe ustawienia dla wybranej strony.
 *
 * @param array $form
 * @param array &$form_state
 */
function scholar_page_form_submit($form, &$form_state) // {{{
{
    if (empty($form['#record'])) {
        return;
    }

    $values = $form_state['values'];
    $record = $form['#record'];

    $title  = $record->title;

    // jezeli wezly maja pusty tytul wstaw tytul strony odpowiedni
    // dla jezyka wezla
    foreach ($values['nodes'] as $language => &$node) {
        $node_title = trim($node['title']);
        if (empty($node_title)) {
            $node_title = t($title, array(), $language);
        }
        $node['title'] = $node_title;
    }
    unset($node);

    scholar_populate_record($record, $values);

    if (scholar_save_record('pages', $record)) {
        drupal_set_message(t('%title updated successfully.', array('%title' => t($title))));
        drupal_goto(scholar_admin_path('page'));
    }
} // }}}

// vim: fdm=marker
