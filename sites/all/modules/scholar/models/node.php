<?php

/**
 * Sprawdza czy tabela url_alias stworzona przez moduł path jest dostępna.
 *
 * @return bool
 */
function _scholar_url_alias_exists() // {{{
{
    static $exists = null;

    if (null === $exists) {
        $exists = db_table_exists('url_alias');
    }

    return $exists;
} // }}}

/**
 * Pobiera z bazy danych rekord wiążący węzeł z obiektem z podanej tabeli.
 * @param int $row_id
 * @param string $table_name
 *     nazwa modelu. Jeżeli jej nie podano, nastąpi wyszukiwanie wiązania
 *     po identyfikatorze węzła równym wartości $row_id
 * @param string $language OPTIONAL
 *     jeżeli nie podany zostaną pobrane wiązania dla wszystkich języków
 * @return false|array
 *     false jeżeli podano język, pusta tablica jeżeli go nie podano
 */
function _scholar_fetch_node_binding($row_id, $table_name = null, $language = null) // {{{
{
    // jezeli nie podano nazwy tabeli szukaj wedlug wezla
    if (null === $table_name) {
        $query = db_query("SELECT * FROM {scholar_nodes} WHERE node_id = %d", $row_id);
        return db_fetch_array($query);
    }

    // upewnij sie, ze identfikator rekordu jest liczba calkowita
    $row_id = intval($row_id);

    $sql = sprintf(
        "SELECT * FROM {scholar_nodes} WHERE table_name = '%s' AND row_id = %d",
        db_escape_string($table_name), $row_id
    );

    if (null === $language) {
        $query  = db_query($sql);
        $result = array();

        while ($row = db_fetch_array($query)) {
            $result[] = $row;
        }

    } else {
        $sql .= sprintf(" AND language = '%s'", db_escape_string($language));
        $query  = db_query($sql);
        $result = db_fetch_array($query);
    }

    return $result;
} // }}}

/**
 * Tworzy wiązanie między rekordem należącym do podanego modelu
 * z istniejącym węzłem.
 *
 * @param object &$node
 * @param int $row_id
 * @param string $table_name
 * @param string $body OPTIONAL
 */
function _scholar_bind_node(&$node, $row_id, $table_name, $body = '') // {{{
{
    if (empty($node->nid)) {
        return false;
    }

    $mlid = isset($node->menu['mlid']) ? intval($node->menu['mlid']) : null;
    if (empty($mlid)) {
        $mlid = 'NULL';

        // nie usuwamy aliasu gdy jest pusty link w menu, poniewaz alias
        // nalezy do wezla (segmentu) a nie do linku
    }

    // usuń obecne dowiązanie dla tego wezla i utwórz nowe
    db_query(
        "DELETE FROM {scholar_nodes} WHERE node_id = %d OR (table_name = '%s' AND row_id = %d AND language = '%s')", 
        $node->nid, $table_name, $row_id, $node->language
    );

    db_query(
        "INSERT INTO {scholar_nodes} (table_name, row_id, node_id, language, status, menu_link_id, path_id, last_rendered, title, body) VALUES ('%s', %d, %d, '%s', %d, %s, NULL, NULL, '%s', '%s')",
        $table_name, $row_id, $node->nid, $node->language, $node->status, $mlid, $node->title, $body
    );

    // obejscie problemu z aliasami i wielojezykowoscia, poprzez wymuszenie
    // neutralnosci jezykowej aliasu, patrz http://drupal.org/node/347265
    // (URL aliases not working for content not in default language)

    if (_scholar_url_alias_exists() && isset($node->path)) {
        $path = trim($node->path);

        // wyeliminuj aktualne aliasy dla tej sciezki
        db_query("DELETE FROM {url_alias} WHERE dst = '%s' OR src = 'node/%d'", $path, $node->nid);

        if (strlen($path)) {
            path_set_alias('node/'. $node->nid, $path, null, '');

            // path_set_alias nie zwraca identyfikatora utworzonej sciezki,
            // wiec musimy wyznaczyc go sami
            $query = db_query("SELECT * FROM {url_alias} WHERE dst = '%s'", $path);
            if ($url_alias = db_fetch_array($query)) {
                db_query("UPDATE {scholar_nodes} SET path_id = %d WHERE node_id = %d", $url_alias['pid'], $node->nid);
            }
        }
    }

    return true;
} // }}}

/**
 * Ustawia wartości pól węzła na podstawie podanego wiązania.
 *
 * @param object $node
 * @param array $binding
 */
function _scholar_populate_node(&$node, $binding) // {{{
{
    $node->menu = null; // menu_link
    $node->path = null; // url_alias path dst
    $node->pid  = null; // url_alias path id
    $node->title    = $binding['title'];
    $node->body     = $binding['body']; // nieprzetworzona tresc wezla
    $node->status   = $binding['status'];
    $node->language = $binding['language'];

    // Dociagamy menu link i url alias zgodne z danymi w binding
    // a nie tymi dostarczonymi przez nodeapi
    if ($binding['menu_link_id']) {
        $query = db_query("SELECT * FROM {menu_links} WHERE mlid = %d", $binding['menu_link_id']);

        if ($row = db_fetch_array($query)) {
            $node->menu = $row;
        } else {
            $node->menu = null;
        }
    }

    if (_scholar_url_alias_exists()) {
        $query = db_query("SELECT * FROM {url_alias} WHERE pid = %d", $binding['path_id']);

        if ($row = db_fetch_array($query)) {
            // pid jest potrzebne przy usuwaniu/edycji aliasow tak, by 
            // nie zostawiac osieroconych rekordow, patrz dokumentacja
            // path_nodeapi()
            $node->pid  = $row['pid'];
            $node->path = $row['dst'];
        } else {
            $node->pid  = null;
            $node->path = null;
        }
    }
} // }}}

/**
 * Pobiera z bazy węzeł należący do podanego wiązania i zwraca reprezentujący
 * go obiekt. Jeżeli węzeł nie został znaleziony zostaje zwrócony obiekt
 * zawierający dane z wiązania, jednakże o pustym identyfikatorze (nid ma
 * wartość NULL). Ładowanie węzła odbywa się poprzez Node API.
 *
 * @param array $binding
 * @return object
 */
function _scholar_node_load($binding) // {{{
{
    $node = node_load($binding['node_id']);

    if (empty($node)) {
        $node = scholar_create_node();
    }

    _scholar_populate_node($node, $binding);

    return $node;
} // }}}

/**
 * Pobiera z bazy rekord węzła przypisany do rekordu z danej tabeli,
 * z nieprzetworzoną treścią, z wypełnionymi polami menu i path.
 *
 * @param int $row_id
 * @param string $table_name
 * @param string $language
 * @return false|object
 */
function scholar_fetch_node($row_id, $table_name, $language) // {{{
{
    $node = false;

    if ($binding = _scholar_fetch_node_binding($row_id, $table_name, $language)) {
        // Reczne pobranie zamiast node_load() zeby nie uniknac wywolania
        // hooka nodeapi, poniewaz dostep do tego wezla ma byc jedynie 
        // dla modulu scholar.
        $rendering = _scholar_rendering_enabled(false);
        $node = _scholar_node_load($binding);

        _scholar_rendering_enabled($rendering);
    }

    return $node;
} // }}}

/**
 * Zwraca wszystkie wezly powiązane z tym rekordem, indeksowane kodem języka.
 *
 * @return array
 */
function scholar_load_nodes($row_id, $table_name) // {{{
{
    $nodes = array();
    $rendering = _scholar_rendering_enabled(false);

    foreach (_scholar_fetch_node_binding($row_id, $table_name) as $binding) {
        $node = _scholar_node_load($binding);
        $nodes[$node->language] = $node;
    }

    _scholar_rendering_enabled($rendering);

    return $nodes;
} // }}}

/**
 * Po zapisie każdy rekord opisujący węzeł ma ustawione pole nid przechowujące
 * identyfikator zapisanego węzła oraz pole status z zerojedynkową wartością
 * mówiącą czy węzeł został opublikowany.
 *
 * @param int $row_id
 * @param string $table_name
 * @param array &$nodes
 *     wartość w postaci zwracanej przez podformularz węzłów
 * @return int
 *     liczba zapisanych węzłów
 */
function scholar_save_nodes($row_id, $table_name, &$nodes) // {{{
{
    $saved = 0;

    foreach ($nodes as $language => &$node_data) {
        // sprobuj pobrac wezel powiazany z tym obiektem
        $node = scholar_fetch_node($row_id, $table_name, $language);

        $status = isset($node_data['status']) && intval($node_data['status']) ? 1 : 0;

        // id wezla jest pobierany z wiazania i nie moze zostac nadpisany
        // wartoscia z nowych danych wezla
        if (isset($node_data['nid'])) {
            unset($node_data['nid']);
        }

        $node_data['status']   = $status;
        $node_data['language'] = $language;

        if (empty($node->nid)) {
            // jezeli status jest zerowy, a wezel nie istnieje nie tworz nowego,
            // ustaw pusty id wezla
            if (!$status) {
                $node_data['nid'] = null;
                continue;
            }
        }

        foreach ($node_data as $key => $value) {
            $node->$key = $value;
        }

        if (isset($node_data['menu'])) {
            // wyznacz parenta z selecta, na podstawie modules/menu/menu.module:429
            $menu = $node_data['menu'];
            list($menu['menu_name'], $menu['plid']) = explode(':', $node_data['menu']['parent']);

            // menu jest zapisywane za pomoca hooku menu_nodeapi
            $node->menu = $menu;
        }

        // alias zapisywany za pomoca path_nodeapi
        if (isset($node_data['path'])) {
            $node->path = rtrim($node_data['path']['path'], '/');
        }

        if (scholar_save_node($node, $row_id, $table_name)) {
            $node_data['nid'] = $node->nid;
            ++$saved;
        } else {
            // zapis sie nie powiodl, ustaw pusty id wezla
            $node_data['nid'] = null;
        }
    }
    unset($node_data);

    return $saved;
} // }}}

/**
 * Tworzy pusty węzeł gotowy do zapisu za pomocą node_save().
 *
 * @param array $values OPTIONAL
 * @return object
 */
function scholar_create_node($values = array()) // {{{
{
    $node = new stdClass;

    $node->title    = '';
    $node->body     = '';
    $node->type     = 'scholar';
    $node->created  = time();
    $node->changed  = null;
    $node->promote  = 0; // Display on front page ? 1 : 0
    $node->sticky   = 0; // Display top of page ? 1 : 0
    $node->format   = 2; // 1:Filtered HTML, 2: Full HTML
    $node->status   = 1; // Published ? 1 : 0
    $node->language = '';
    $node->revision = null;

    $node->nid      = null;
    $node->menu     = null;
    $node->path     = null;
    $node->pid      = null;

    return $node;
} // }}}

/**
 * Zapisuje węzeł i podpina go do obiektu z podanej tabeli.
 *
 * @param object &$node
 * @param int $row_id
 * @param string $table_name
 * @return bool
 */
function scholar_save_node(&$node, $row_id, $table_name) // {{{
{
    $body = trim($node->body);

    $node->format = scholar_setting_node_format();
    $node->type   = 'scholar';
    $node->body   = ''; // puste body, bo tresc do przetworzenia zostanie
                        // zapisana w bindingu

    $node->revision = null; // nigdy nie tworz nowych rewizji, poniewaz
                            // tresc jest generowana automatycznie

    // jezeli podano pusta nazwe linku w menu, a jest podany jego mlid
    // usun ten link. Trzeba to zrobic tutaj, bo menu_nodeapi tego nie
    // zrobi.
    if (isset($node->menu)) {
        $link_title = trim($node->menu['link_title']);

        if (0 == strlen($link_title)) {
            menu_link_delete(intval($node->menu['mlid']));
            unset($node->menu);
        }
    }

    node_save($node);

    // przywroc body do wartosci sprzed zapisu
    $node->body = $body;

    if (empty($node->nid)) {
        // zapis wezla nie powiodl sie
        return false;
    }

    // dodaj węzeł do indeksu powiązanych węzłów
    return _scholar_bind_node($node, $row_id, $table_name, $body);
} // }}}

/**
 * Usuwa węzły powiązane z tym obiektem.
 *
 * @param int $row_id
 * @param string $table_name
 */
function scholar_delete_nodes($row_id, $table_name) // {{{
{
    // po pierwsze zapamietaj wszystkie komunikaty, usuwanie wezla
    // bedzie utawialo wlasne, ktorych nie chcemy pokazywac -
    // dla kazdego usunietego wezla "Page has been deleted".
    $messages = drupal_get_messages();

    $bindings  = _scholar_fetch_node_binding($row_id, $table_name);
    $url_alias = _scholar_url_alias_exists();

    foreach ($bindings as $binding) {
        // Dla absolutnej pewnosci usun powiazane linki menu i aliasy.
        // Jezeli dane sa rozspojnione, to oczywiscie zostanie usuniete 
        // wiecej niz trzeba. Spoko :)
        db_query("DELETE FROM {menu_links} WHERE mlid = %d", $binding['menu_link_id']);

        if ($url_alias) {
            db_query("DELETE FROM {url_alias} WHERE pid =%d", $binding['path_id']);
        }

        db_query("DELETE FROM {scholar_nodes} WHERE node_id = %d", $binding['node_id']);

        // Tutaj musimy uzyc nodeapi zeby poprawnie usunac rekord wezla,
        // usuniete zostana linki menu i aliasy sciezek.
        // Poniewaz node_delete wywoluje nodeapi z parametrem load, musimy
        // zmienic typ wezla na ignorowany podczas ladowania.
        db_query("UPDATE {node} SET type = 'page' WHERE nid = %d", $binding['node_id']);

        // usuwamy binding
        db_query("DELETE FROM {scholar_nodes} WHERE node_id = %d", $binding['node_id']);

        // teraz spokojnie mozemy usunac wezel, jako ze przestal on
        // nalezec do scholara
        node_delete($binding['node_id']);
    }

    // usun komunikaty ustawione podczas usuwania wezla
    drupal_get_messages();

    // przywroc komunikaty sprzed usuniecia wezlow
    foreach ($messages as $type => $type_messages) {
        foreach ($type_messages as $message) {
            drupal_set_message($message, $type);
        }
    }
} // }}}

/**
 * Zwraca adres URL prowadzący do węzła przypisanego do rekordu z podanej
 * tabeli. Funkcja korzysta z wyniku funkcji {@see scholar_node_link()}, której
 * przekazuje bezpośrednio swoje parametry wywołania.
 *
 * @return false|string
 */
function scholar_node_url($row_id, $table_name = null, $language = null, $refresh = false) // {{{
{
    $link = scholar_node_link($row_id, $table_name, $language, $refresh);

    return $link
        ? url($link['path'], array('absolute' => true))
        : false;
} // }}}

/**
 * Funkcja pomocnicza szukająca w bazie opublikowanego węzła o podanym
 * identyfikatorze i zwracająca informacje o odnośniku do niego.
 *
 * @param int $node_id
 * @return array
 */
function _scholar_node_link_node($node_id) // {{{
{
    $query = db_query("SELECT title FROM {node} WHERE nid = %d AND status <> 0", $node_id);

    if ($node = db_fetch_array($query)) {
        $alias = false;

        // pobierz ewentualny alias
        if (_scholar_url_alias_exists()) {
            $query = db_query("SELECT dst FROM {url_alias} WHERE src = 'node/%d'", $node_id);
            if ($row = db_fetch_array($query)) {
                $alias = $row['dst'];
            }
        }

        $path = $alias ? $alias : sprintf('node/%d', $node_id);

        return array('node_id' => $node_id, 'title' => $node['title'], 'path' => $path);
    }

    return false;
} // }}}

/**
 * Funkcja pomocnicza zwracająca informacje o odnośniku do opublikowanego
 * węzła powiązanego z rekordem o podanym identyfikatorze należącym do podanego
 * modelu.
 *
 * @param int $row_id
 * @param string $table_name
 * @param string $language
 * @return array
 */
function _scholar_node_link_record($row_id, $table_name, $language) // {{{
{
    $binding = _scholar_fetch_node_binding($row_id, $table_name, (string) $language);

    if ($binding && $binding['status']) {
        $alias = false;

        if (_scholar_url_alias_exists()) {
            $query = db_query("SELECT dst FROM {url_alias} WHERE pid = %d", $binding['path_id']);
            if ($row = db_fetch_array($query)) {
                $alias = $row['dst'];
            }
        }

        $path = $alias ? $alias : ('node/' . $binding['node_id']);

        return array('node_id' => $binding['node_id'], 'title' => $binding['title'], 'path' => $path);
    }

    return false;
} // }}}

/**
 * Zwraca specyfikację odnośnika do węzła w postaci tablicy o polach: node_id,
 * title i path przechowyjących odpowiednio identyfikator węzła, jego tytuł
 * oraz ścieżkę.
 *
 * @param int $row_id
 *     identyfikator rekordu
 * @param string|bool $table_name
 *     opcjonalna nazwa modelu, do którego odnosi się identyfikator rekordu.
 *     Jeżeli nie została podana parametr $row_id zostaje potraktowany jako
 *     identyfikator węzła. Gdy podano wartość typu logicznego zostanie ona
 *     użyta jako parametr $refresh
 * @param string $language
 *     opcjonalny język węzła, brany pod uwagę tylko jeżeli podano nazwę
 *     modelu. Jeżeli podano nazwę modelu nie podając języka, zostanie użyty
 *     bieżący język
 * @param bool $refresh
 *     czy pobierać dane z bazy z pominięciem pamięci podręcznej funkcji
 * @return false|array
 */
function scholar_node_link($row_id, $table_name = null, $language = null, $refresh = false) // {{{
{
    static $links = array();

    // jezeli nazwa tabeli jest wartoscia logiczna uzyj jej
    // zamiast parametru $refresh
    if (is_bool($table_name)) {
        $refresh = $table_name;
        $table_name = null;
    }

    $row_id     = (int) $row_id;
    $table_name = (string) $table_name;
    $language   = (string) $language;

    // jezeli nie podano jezyka, uzyj domyslnego
    if (!strlen($language)) {
        $language = scholar_language();
    }

    $key = "{$row_id}_{$table_name}_{$language}";

    if (!isset($links[$key]) || $refresh) {
        $links[$key] = strlen($table_name)
            ? _scholar_node_link_record($row_id, $table_name, $language)
            : _scholar_node_link_node($row_id);
    }

    return $links[$key];
} // }}}

/**
 * @param int $format_id
 *     identyfikator istniejącego formatu danych
 * @return int
 *     liczba zaktualizowanych węzłów
 */
function scholar_set_node_format($format_id) // {{{
{
    // sprawdz, czy filtr o podanym identyfikatorze istnieje
    $row = db_fetch_array(db_query("SELECT COUNT(*) AS cnt FROM {filter_formats} WHERE format = %d", $format_id));
    if ($row['cnt']) {
        // skoro tak, ustaw jego identyfikator dla wszystkich rewizji wezlow
        // typu scholar
        db_query("UPDATE {node_revisions} SET format = %d WHERE nid IN (SELECT nid FROM {node} WHERE type = 'scholar')", $format_id);
        return db_affected_rows();
    }

    return 0;
} // }}}

// vim: fdm=marker
