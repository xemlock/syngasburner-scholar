<?php

/**
 * Pobiera wszystkie rekordy związane z wynikiem zapytania.
 *
 * @param string|resource $query
 *     zapytanie SQL albo zasób reprezentujący wynik funkcji db_query
 * @return array
 *     lista pobranych rekordów
 */
function scholar_db_fetch_all($query) // {{{
{
    $rows = array();

    if (is_string($query)) {
        $args  = func_get_args();
        $query = call_user_func_array('db_query', $args);
    }

    while ($row = db_fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
} // }}}

/**
 * @param string|array $header
 *     tablica kolumn tabeli w postaci opisanej w theme_table(), lub gdy jest
 *     to string zostanie on potraktowany jako nazwa kolumny do sortowania
 *     rosnącego. Można też podać tablicę w postaci listy array('kolumna1 ASC',
 *     'kolumna2 DESC', 'kolumna3')
 * @param string|array $before
 *     jeżeli podano argument typu array, zostanie on użyty zamiast parametru
 *     $columns, w przeciwnym razie argument zostanie umieszczony w wynikowym
 *     stringu bezpośrednio za słowami kluczowymi ORDER BY, przed kodem
 *     opisującym sortowanie. Przecinek oddzielający wartość $before od nazw
 *     sortowanych kolumn jest automatycznie dodawany.
 * @param array $columns
 *     opcjonalna tablica z dopuszczalnymi nazwami kolumn. Jeżeli została
 *     podana, kolumny, których nazwy nie znajdują się w niej, zostaną usunięte
 *     z zapytania
 * @return string
 */
function scholar_tablesort_sql($header, $before = '', $columns = null) // {{{
{
    // jezeli $before jest tablica uzyj jej jako $columns
    if (is_array($before)) {
        $columns = $before;
        $before  = '';
    }

    if ($header) {
        $header = (array) $header;

        // sprawdz czy podano sortowanie w postaci stringow: "kolumna ASC"
        // lub "kolumna DESC"
        foreach ($header as &$column) {
            if (is_string($column)) {
                $column = trim($column);

                if (preg_match('/\s+(ASC|DESC)$/i', $column, $match)) {
                    // zeby dostac nazwe pola usun 4 ostatnie znaki, a nastepnie
                    // pozostale biale spacje z prawej strony
                    $field = rtrim(substr($column, 0, -4));
                    $sort  = $match[1];
                } else {
                    $field = $column;
                    $sort  = 'ASC';
                }

                $column = array('field' => $field, 'sort' => $sort);
            }
        }
        unset($column);
    }

    // jezeli podano niepusta liste kolumn odfiltruj kolumny,
    // ktorych w niej nie ma
    if (is_array($columns)) {
        foreach ($header as $key => $column) {
            if (!isset($column['field']) || !in_array($column['field'], $columns)) {
                unset($header[$key]);
            }
        }
    }

    $before = trim($before);

    if (strlen($before)) {
        // jezeli podano nazwy kolumn do umieszczenia bezposrednio
        // za slowami ORDER BY, dodaj przecinek
        $before = rtrim($before, ',') . ',';
    }

    return tablesort_sql($header, $before, $columns);
} // }}}

/**
 * Dodaje do wartości znaki unikowe na użytek zapytań SQL oraz otacza
 * ją pojedynczymi apostrofami.
 *
 * @param mixed $value
 * @return string
 */
function scholar_db_quote($value) // {{{
{
    if (null === $value) {
        return 'NULL';
    } else if (is_int($value)) {
        return $value;
    } else if (is_float($value)) {
        // %F non-locale aware floating-point number
        return sprintf('%F', $value);
    }
    return "'" . db_escape_string($value) . "'";
} // }}}

/**
 * Otacza podany łańcuch znaków znakami ograniczającymi zgodnymi
 * z używanym rodzajem bazy danych tak, by można go było użyć jako
 * nazwę tabeli lub kolumny.
 *
 * @param string $identifier
 *     nazwa tabeli lub kolumny
 * @return string
 */
function scholar_db_quote_identifier($identifier) // {{{
{
    global $db_url;
    static $db_type = null;

    if (null === $db_type) {
        $db_type = substr($db_url, 0, strpos($db_url, ':'));
    }

    // PostgreSQL: "Quoted identifiers can contain any character, except
    // the character with code zero."
    // MySQL: "Permitted characters in quoted identifiers include the full
    // Unicode Basic Multilingual Plane (BMP), except U+0000"
    $identifier = str_replace("\x00", '', $identifier);

    switch ($db_type) {
        case 'mysql':
        case 'mysqli':
            return "`" . str_replace('`', '``', $identifier) . "`";

        default:
            // pgsql sqlite oci sybase mssql dblib
            return '"' . str_replace('"', '""', $identifier) . '"';
    }
} // }}}

/**
 * Jeżeli wartosć jest tablicą zostanie użyta klauzula WHERE IN.
 * Jeżeli nazwa kolumny rozpoczyna się od ? dopuszczona zostanie
 * wartość NULL
 *
 * @param array $conds
 *     tablica z warunkami
 * @return string
 *     jezeli wejsciowa tablica warunkow jest pusta, zwrocony
 *     zostaje string "1", odpowiadający brakowi zadanych warunków
 *     wyszukiwania
 */
function scholar_db_where($conds) // {{{
{
    $where = array();

    foreach ((array) $conds as $key => $value) {
        if ('?' == substr($key, 0, 1)) {
            $null = true;
            $key  = substr($key, 1);
        } else {
            $null = false;
        }

        if (false !== ($pos = strpos($key, '.'))) {
            // alias tabeli, nie otaczaj go znakami ograniczajacymi
            $column = substr($key, 0, $pos + 1)
                    . scholar_db_quote_identifier(substr($key, $pos + 1));
        } else {
            $column = scholar_db_quote_identifier($key);
        }

        if (is_array($value)) {
            $values = count($value) 
                    ? '(' . join(',', array_map('scholar_db_quote', $value)) . ')'
                    : '(NULL)';
            $expr = $column . ' IN ' . $values;

        } elseif (null === $value) {
            $expr = $column . ' IS NULL';

        } else {
            $expr = $column . " = " . scholar_db_quote($value);
        }

        if ($null) {
            $expr = '(' . $expr . ' OR ' . $column . ' IS NULL)';
        }

        $where[] = $expr;
    }

    // jezeli wynikowa tablica jest pusta zwroc 0, tak by warunek
    // nigdy nie byl spelniony (WHERE 0)

    return $where ? implode(' AND ', $where) : '1';
} // }}}

/**
 * Zwraca wyrażenie SQL, które przekształca kod kraju w jego nazwę
 * w bieżącym języku.
 * @param string $column
 *     nazwa kolumny przechowującej dwuliterowy kod kraju, jeżeli w nazwie
 *     kolumny występuje kropka, zostanie ona potraktowana jako alias
 * @param string $table
 *     nazwa tabeli
 * @return string
 *     wyrażenie CASE przekształcające kod kraju w jego nazwę
 */
function scholar_db_country_name($column, $table) // {{{
{
    if (false !== ($pos = strpos($column, '.'))) {
        $alias  = substr($column, 0, $pos);
        $alias  = preg_replace('/[^_a-z0-9]/i', '', $alias);
        $column = substr($column, $pos + 1);
    } else {
        $alias  = '';
    }

    // korzystamy z udostepnianej funkcji do escape'owania nazw tabel, ze
    // wzgledu na dodawanie prefiksu do nazw tabel w db_query, ktore moze
    // nie byc odporne na nazwy tabel otoczone znakami ograniczajacymi
    $table  = db_escape_table($table);

    if (empty($column) || empty($table)) {
        return 'NULL';
    }

    $column = scholar_db_quote_identifier($column);

    // pobierz liste wystepujacych w tabeli krajow, tutaj alias
    // nie jest potrzebny
    $query = db_query("SELECT DISTINCT $column AS country FROM {{$table}} WHERE country IS NOT NULL");
    $codes = array();

    while ($row = db_fetch_array($query)) {
        $codes[] = $row['country'];
    }

    // jezeli przeszukiwania w podanej kolumnie nie daly zadnych wynikow
    // nazwa kraju bedzie pusta
    if (empty($codes)) {
        return 'NULL';
    }

    $countries = scholar_countries();

    if (strlen($alias)) {
        $sql = "CASE $alias.$column";
    } else {
        $sql = "CASE $column";
    }

    foreach ($codes as $code) {
        $country = isset($countries[$code]) ? $countries[$code] : null;
        $sql .= " WHEN " . scholar_db_quote($code) . " THEN " . scholar_db_quote($country);
    }

    $sql .= " ELSE NULL END";

    return $sql;
} // }}}

/**
 * Funkcja zapisująca rekord do bazy. W przeciwieństwie do funkcji
 * drupal_write_record dziala dobrze, gdy nowe wartości kolumny mają wartość
 * NULL.
 *
 * @param string $table
 * @param object &$record
 * @param string|array $update
 */
function scholar_db_write_record($table, &$record, $update = array()) // {{{
{
    $schema = drupal_get_schema($table);

    if (empty($schema)) {
        drupal_set_message(t('Unable to retrieve schema for table %table.', array('%table' => $table)), 'error');
        return false;    
    }

    // upwenij sie, ze podczas edycji wymagane kolumny sa ustawione
    $update = (array) $update;

    foreach ($update as $column) {
        if (empty($record->$column)) {
            drupal_set_message(t('Empty value of property %property required for update.', array('%property' => $column)), 'error');
            return false;
        }
    }

    // odfiltruj tylko te wartosci, ktorych klucze odpowiadaja kolumnom
    // podanej tabeli. Utworz przy okazji liste kolumn sekwencyjnych,
    // ktorych wartosci nalezy pobrac po dodaniu nowego rekordu do bazy.
    $serials = array();
    $values = array();

    foreach ($schema['fields'] as $name => $spec) {
        // dodaj nazwe kolumny do listy kolumn sekwencyjnych, jezeli
        // jej typ to 'serial'
        if ('serial' == $spec['type']) {
            $serials[] = $name;

            // wartosci kolumn sekwencyjnych nie podlegaja edycji, wiec
            // mozna je zignorowac podczas budowania tablicy z wartosciami
            // do zapisu
            continue;
        }

        if (property_exists($record, $name)) {
            $not_null = isset($spec['not null']) && $spec['not null'];
            $value    = $record->$name;

            // wszystkie wartosci rozne od liczb zmiennoprzecinkowych sa
            // konwertowane do stringow. Floaty sa poprawnie formatowane
            // przez scholar_db_quote na etapie budowania zapytania.
            if (!is_float($value)) {
                // przytnij wszystkie stringi, puste zastap nullem
                $value = trim($value);
                if (0 == strlen($value)) {
                    $value = null;
                }
            }

            // jezeli kolumna nie zezwala na NULL zastap go zerowa
            // wartoscia dla danego typu
            if (null === $value && $not_null) {
                switch ($spec['type']) {
                    case 'int': case 'float': case 'numeric':
                        $value = 0;
                        break;

                    case 'char': case 'varchar': case 'text':
                        $value = '';
                        break;

                    case 'datetime':
                        $value = date('Y-m-d H:i:s', 0);
                        break;
                }
            }

            $values[$name] = $value;
        }
    }

    $success = false;

    if ($values) {
        if ($update) {
            $assigns = array();

            foreach ($values as $column => $value) {
                $assigns[] = scholar_db_quote_identifier($column) . ' = ' . scholar_db_quote($value);
            }

            $where = array();

            foreach ($update as $column) {
                $where[$column] = $record->$column;
            }

            $sql = 'UPDATE {' . $table . '} SET ' . implode(', ', $assigns) . ' WHERE ' . scholar_db_where($where);

            if (db_query($sql)) {
                $success = true;
            }

        } else {
            $sql = 'INSERT INTO {' . $table . '} ('
                 . implode(', ', array_keys($values)) . ') VALUES ('
                 . implode(', ', array_map('scholar_db_quote', $values))
                 . ')';

            if (db_query($sql)) {
                // zaktualizuj kolumny sekwencyjne
                foreach ($serials as $column) {
                    $values[$column] = db_last_insert_id($table, $column);
                }

                $success = true;
            }
        }

        // wypelnij obiekt wartosciami zapisanymi do bazy. Nie ma sensu
        // odswiezac rekordu danymi z bazy, bo zwykle po zapisie i tak
        // nastepuje przekierowanie na inna strone.
        if ($success) {
            foreach ($values as $key => $value) {
                $record->$key = $value;
            }
        }
    }

    return $success;
} // }}}

// vim: fdm=marker
