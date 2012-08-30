<?php

/**
 * Reimplementacja theme_table pozwalająca na więcej niż jeden element
 * TBODY w tabeli.
 */
function scholar_theme_table($header, $rows, $attributes = array()) // {{{
{
    if ($header) {
        drupal_add_js('misc/tableheader.js');
        $attributes['class'] = empty($attributes['class']) ? 'sticky-enabled' : ($attributes['class'] . ' sticky-enabled');
    }

    $output = '<table' . drupal_attributes($attributes) . ">\n";

    if ($header) {
        $ts = tablesort_init($header);
        $output .= (count($rows) ? ' <thead><tr>' : ' <tr>');
        foreach ($header as $cell) {
            $cell = tablesort_header($cell, $header, $ts);
            $output .= scholar_theme_table_cell($cell, true);
        }
        $output .= (count($rows) ? " </tr></thead>\n" : "</tr>\n");

    } else {
        $ts = array();
    }

    if ($rows) {
        $flip = array(
            'even' => 'odd',
            'odd'  => 'even',
        );
        $parity = array();

        $tbodies = array();

        foreach ($rows as $row) {
            $attributes = array();
            $cells = null;

            if (isset($row['tbody'])) {
                $tbody = strval($row['tbody']);
                unset($row['tbody']);
            } else {
                $tbody = '';
            }

            // przygotuj zmienna przechowujaca parzystosc wiersza
            // w danym tbody
            if (!isset($parity[$tbody])) {
                $parity[$tbody] = 'even';
            }

            // sprawdz czy mamy do czynienia ze zlozona czy z prosta
            // definicja wiersza tabeli
            if (isset($row['data'])) {
                foreach ($row as $key => $value) {
                    if ($key == 'data') {
                        $cells = $value;
                    } else {
                        $attributes[$key] = $value;
                    }
                }
            } else {
                $cells = $row;
            }
            
            if ($cells) {
                // dodaj klase mowiaca o parzystosci wiersza (odd / even)
                $class = $parity[$tbody] = $flip[$parity[$tbody]];

                if (isset($attributes['class'])) {
                    $attributes['class'] .= ' ' . $class;
                } else {
                    $attributes['class'] = $class;
                }

                // zbuduj wiersz tabeli
                $tr = ' <tr' . drupal_attributes($attributes) . '>';
                $i = 0;
                foreach ($cells as $cell) {
                    $cell = tablesort_cell($cell, $header, $ts, $i++);
                    $tr .= scholar_theme_table_cell($cell);
                }
                $tr .= " </tr>\n";

                // dodaj wiersz do tbody
                if (!isset($tbodies[$tbody])) {
                    $tbodies[$tbody] = '';
                }

                $tbodies[$tbody] .= $tr;
            }
        }

        foreach ($tbodies as $tbody) {
            $output .= "<tbody>\n" . $tbody . "</tbody>\n";
        }
    }

    $output .= "</table>\n";

    return $output;
} // }}}

/**
 * Poniewaz _theme_table_cell sądząc po nazwie jest metodą prywatną,
 * moduł korzysta z własnej funkcji generujacej kod HTML komórki tabeli.
 *
 * @param array $cell
 * @param bool $header
 * @return string
 */
function scholar_theme_table_cell($cell, $header = false) // {{{
{
    if (is_array($cell)) {
        if (empty($cell['data'])) {
            // brak tresci w komorce
            $data = '';

        } else {
            $data = $cell['data'];
            unset($cell['data']);

            // jezeli trescia komorki jest tablica wyrenderuj jej zawartosc
            if (is_array($data)) {
                $data = drupal_render($data);
            }

            // jezeli komorke oznaczono jako naglowkowa (np. w wyniku wykonania
            // tablesort_header) ustaw odpowiednia flage 
            if (isset($cell['header'])) {
                $header |= $cell['header'];
                unset($cell['header']);
            }

            $attributes = drupal_attributes($cell);
        }
    } else {
        $data = $cell;
        $attributes = '';
    }

    if ($header) {
        return "<th{$attributes}>{$data}</th>";
    } else {
        return "<td{$attributes}>{$data}</td>";
    }
} // }}}

// vim: fdm=marker
