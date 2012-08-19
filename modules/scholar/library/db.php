<?php

/**
 * @param array $header         tablica koloumn tabeli w postaci opisanej
 *                              w theme_table()
 * @param string|array $before  jeżeli podano argument typu array, zostanie on
 *                              użyty zamiast parametru $columns, w przeciwnym
 *                              razie argument zostanie umieszczony w wynikowym
 *                              stringu bezpośrednio za klauzulą ORDER BY, przed
 *                              kodem opisującym sortowanie
 * @param array $columns        OPTIONAL tablica z dopuszczalnymi nazwami kolumn,
 *                              jeżeli została podana kolumny, których nazwy nie
 *                              znajdują się na niej zostaną usunięte z zapytania
 * @return string
 */
function scholar_tablesort_sql($header, $before = '', $columns = null) // {{{
{
    // jezeli $before jest tablica uzyj jej jako $columns
    if (is_array($before)) {
        $columns = $before;
        $before  = '';
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
 * @param string $identifier            nazwa tabeli lub kolumny
 * @return string
 */
function scholar_db_quote_identifier($identifier) // {{{
{
    global $db_url;
    static $db_type = null;

    if (null === $db_type) {
        $db_type = array_shift(explode(':', $db_url));
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
 * @param array $conds          tablica z warunkami
 */
function scholar_db_where($conds) // {{{
{
    $where = array();

    foreach ($conds as $key => $value) {
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
            $where[] = $column . ' IN ' . $values;

        } elseif (null === $value) {
            $where[] = $column . ' IS NULL';

        } else {
            $where[] = $column . " = " . scholar_db_quote($value);
        }
    }

    return implode(' AND ', $where);
} // }}}

/**
 * Zwraca wyrażenie SQL, które przekształca kod kraju w jego nazwę
 * w bieżącym języku.
 * @param string $column        nazwa kolumny przechowującej dwuliterowy kod kraju
 * @param string $table         nazwa tabeli
 * @return string               wyrażenie CASE przekształcające kod kraju w jego nazwę
 */
function scholar_db_country_name($column, $table) // {{{
{
    $column = db_escape_table($column);
    $table  = db_escape_table($table);

    if (empty($column) || empty($table)) {
        return 'NULL';
    }

    // pobierz liste wystepujacych w tabeli krajow
    $query = db_query("SELECT DISTINCT $column FROM {$table} WHERE $column IS NOT NULL");
    $codes = array();

    while ($row = db_fetch_array($query)) {
        $codes[] = $row[$column];
    }

    // jezeli przeszukiwania w podanej kolumnie nie daly zadnych wynikow
    // nazwa kraju bedzie pusta
    if (empty($codes)) {
        return 'NULL';
    }

    $countries = scholar_countries();

    $sql = "CASE $column";
    foreach ($codes as $code) {
        $country = isset($countries[$code]) ? $countries[$code] : null;
        $sql .= " WHEN " . scholar_db_quote($code) . " THEN " . scholar_db_quote($country);
    }
    $sql .= " ELSE NULL END";

    return $sql;
} // }}}

// drupal_write_record dziala dobrze, jezeli zadna z wartosci obiektu nie
// jest nullem, co jest ok, gdy operujemy na tabelach gdzie wszystkie kolumny
// maja wlasciwosc NOT NULL. Dla tabeli z generykami to oczywiscie nie jest
// prawdziwe i funkcja drupalowa po prostu nie dziala.
function scholar_db_write_record($table, &$record, $update = array())
{
    return drupal_write_record($table, $record, $update);
}

// vim: fdm=marker
