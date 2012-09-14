<?php

function scholar_pages_system_index()
{
    $output = '';
    $output .= scholar_oplink(t('Database schema'), 'system.schema');
    $output .= '<br/>';
    $output .= scholar_oplink(t('Settings'), 'settings');
    return $output;
}

function scholar_pages_system_schema() // {{{
{
    $html = '';

    $tables = array();

    foreach (drupal_get_schema() as $name => $table) {
        if (strncmp('scholar_', $name, 8)) {
            continue;
        }
        $tables[$name] = $table;
    }

    ksort($tables);

    foreach ($tables as $name => $table) {
        $html .= db_prefix_tables(
            implode(";\n", db_create_table_sql($name, $table)) . ";\n"
        );
        $html .= "\n";
    }

    return '<pre><code class="sql">' . $html . '</code></pre>';
} // }}}

