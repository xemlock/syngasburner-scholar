<?php

/**
 * Deklaracja tabel modułu.
 *
 * @return array
 */
function scholar_schema() // {{{
{
    // predefiniowane typy kolumn {{{
    $field_type['id'] = array(
        'type'      => 'serial',
        'not null'  => true,
    );
    $field_type['table_name'] = array(
        'description' => 'Table name containing referenced record, without scholar_ prefix.',
        'type'      => 'varchar',
        'length'    => 32, // polowa maksymalnej dlugosci nazwy tabeli / kolumny
                           // w MySQL
        'not null'  => true,
    );
    $field_type['subtype'] = array(
        'description' => 'Subtype of a record.',
        'type'      => 'varchar',
        'length'    => 32,
    );
    $field_type['id_ref'] = array(
        'description' => 'Identifier (primary key value) of referenced record',
        'type'      => 'int',
        'not null'  => true,
    );
    $field_type['optional_id_ref'] = array(
        'type'      => 'int',
        'not null'  => false,
    );
    $field_type['language'] = array(
        'description' => 'Name of a language, references languages (language)',
        'type'      => 'varchar', // typem languages.language jest VARCHAR(12)
        'length'    => 12,
        'not null'  => true,
    );
    $field_type['weight'] = array(
        'description' => 'Record ordering',
        'type'      => 'int',
        'default'   => 0,
        'not null'  => true,
    );
    // }}}

    $schema['scholar_nodes'] = array( // {{{
        'description' => 'Bindings between nodes and scholar records.',
        'fields' => array(
            'table_name' => $field_type['table_name'],
            'row_id'     => $field_type['id_ref'],
            'language'   => $field_type['language'],
            'node_id' => array(
                // REFERENCES node (nid)
                'description' => 'REFERENCES node (nid)',
                'type'      => 'int',
                'not null'  => true,
            ),
            'menu_link_id' => array(
                // REFERENCES menu_links (mlid)
                'type'      => 'int',
            ),
            'path_id' => array(
                // REFERENCES url_alias (pid)
                'type'      => 'int',
            ),
            'status' => array(
                'description' => 'Is node published?',
                'type'      => 'int',
                'size'      => 'tiny',
                'not null'  => true,
            ),
            'last_rendered' => array(
                'type'      => 'int', // timestamp
            ),
            'body' => array(
                'description' => 'User-generated content that will be included in automatically generated node content.',
                'type'      => 'text',
                'size'      => 'big',
            ),
        ),
        'primary key' => array('table_name', 'row_id', 'language'),
        'unique keys' => array(
            'node'  => array('node_id'),
        ),
    ); // }}}

    $schema['scholar_people'] = array( // {{{
        'description' => 'osoby - autorzy prac, prowadzący wykłady etc.',
        'fields' => array(
            'id'         => $field_type['id'],
            'first_name' => array(
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'last_name'  => array(
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'image_id'   => array(
                // REFERENCES image (id)
                'type'      => 'int',
            ),
        ),
        'primary key'  => array('id'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
    ); // }}}

    $schema['scholar_categories'] = array( // {{{
        'description' => 'typy czasopism, załączników, wykładów',
        'fields' => array(
            'id'         => $field_type['id'],
            'table_name' => $field_type['table_name'],
            'subtype'    => $field_type['subtype'],
            'refcount' => array(
                'description' => 'liczba rekordow powiązanych z tą kategorią',
                'type'      => 'int',
                'not null'  => true,
                'default'   => 0,
            ),
        ),
        'primary key'  => array('id'),
    ); // }}}

    $schema['scholar_category_names'] = array( // {{{
        'description' => 'Category names',
        'fields' => array(
            'category_id' => $field_type['id_ref'],
            'language'    => $field_type['language'],
            'name'        => array(
                'type'     => 'varchar',
                'length'   => 128,
                'not null' => true,
            ),
        ),
        'primary key' => array('category_id', 'language'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
    ); // }}}

    $schema['scholar_generics'] = array( // {{{
        'description' => 'Table for storing generic records: articles, books (article containers), presentations, conferences (presentation containers), etc.',
        'fields' => array(
            'id'          => $field_type['id'],
            'parent_id'   => $field_type['optional_id_ref'], // references scholar_generics (id)
            'subtype'     => $field_type['subtype'],
            'category_id' => $field_type['optional_id_ref'], // REFERENCES scholar_categories (id)
                                                             // kategoria podtypu, np. podtypem conference jest konferencja, warsztaty lub seminarium
            'start_date' => array(
                'type'      => 'datetime',
            ),
            'end_date' => array(
                'type'      => 'datetime',
            ),
            'title' => array(
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'locality' => array(
                'description' => 'nazwa miejscowosci / miasta',
                'type'      => 'varchar',
                'length'    => 128,
            ),
            'country' => array(
                'description' => 'kraj',
                'type'      => 'char',
                'length'    => 2,
            ),
            'image_id' => array(
                // REFERENCES image (id)
                'type'      => 'int',
            ),
            'url' => array(
                'description' => 'zewnętrzny URL strony czasopisma lub konferencji',
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'bib_details' => array(
                'description' => 'bibliographic details (books and articles)',
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'bib_authors' => array(
                'description' => 'tekstowa reprezentacja autorów artykułu do umieszczenia w treści, zawierająca max trzy nazwiska',
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'list' => array(
                'description' => 'czy dodawac rekordy do automatycznie generowanych listingow na stronach osob i stronie z wystapieniami na konferencjach',
                'type'      => 'int',
                'size'      => 'tiny',
                'unsigned'  => true,
            ),
            'weight' => $field_type['weight'],
        ),
        'primary key' => array('id'),
        'indexes' => array(
            'parent'        => array('parent_id'),
            'subtype_category' => array('subtype', 'category_id'),
        ),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
    ); // }}}

    $schema['scholar_generic_suppinfo'] = array( // {{{
        'description' => 'Short info about related generic record.',
        'fields' => array(
            'generic_id' => $field_type['id_ref'],
            'language'   => $field_type['language'],
            'suppinfo'   => array(
                'type'     => 'varchar',
                'length'   => 255,
                'not null' => true,
            ),
        ),
        'primary key' => array('generic_id', 'language'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
    ); // }}}

    $schema['scholar_events'] = array( // {{{
        'description' => 'Powiazania miedzy rekordami generycznymi a zdarzeniami',
        'fields' => array(
            'table_name' => $field_type['table_name'],
            'row_id'     => $field_type['id_ref'],
            'event_id'   => array(
                // REFERENCES events (id)
                'type'     => 'int',
                'not null' => true,
            ),
            'language'   => $field_type['language'],
            'body'       => array(
                // tresc wydarzenia, ktora po przetworzeniu (renderowaniu)
                // zostanie zapisana do wezla
                'type'     => 'text',
                'size'     => 'medium',
            ),
        ),
        'primary key' => array('table_name', 'row_id', 'language'),
        'unique keys' => array(
            'event' => array('event_id'), // kazdy event moze byc podpiety do co najwyzej jednego rekordu generycznego
        ),
    ); // }}}

    $schema['scholar_authors'] = array( // {{{
        'description' => 'authors / contributors to articles, books, presentations',
        'fields' => array(
            'table_name' => $field_type['table_name'],
            'row_id'     => $field_type['id_ref'], // REFERENCES scholar_generics (id)
            'person_id'  => $field_type['id_ref'], // REFERENCES scholar_people (id)
            'weight'     => $field_type['weight'],
        ),
        'primary key'  => array('table_name', 'row_id', 'person_id'),
    ); // }}}

    $schema['scholar_files'] = array( // {{{
        // Scholar korzysta z wlasnego systemu zarzadzania plikami, miedzy
        // innymi dlatego, zeby jeden plik mogl byc podpiety do wiecej niz
        // jednego wezla.
        'fields' => array(
            'id' => $field_type['id'],
            'filename' => array(
                'description' => 'Nazwa pliku na dysku wewnatrz katalogu z plikami',
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'mimetype' => array(
                'type'      => 'varchar',
                'length'    => 64,
                'not null'  => true,
            ),
            'size' => array(
                'type'      => 'int',
                'unsigned'  => true,
                'not null'  => true,
            ),
            'md5sum' => array(
                'type'      => 'char',
                'length'    => 32,
                'not null'  => true,
            ),
            'user_id' => array(
                'descriptions' => 'Id uzytkownika, ktory wgral plik',
                'type'      => 'int',
                'not null'  => true,
            ),
            'upload_time' => array(
                'type'      => 'datetime',
                'not null'  => true,
            ),
        ),
        'primary key' => array('id'),
        'unique keys' => array(
            'filename'  => array('filename'),
            'md5sum'    => array('md5sum'),
        ),        

    ); // }}}

    $schema['scholar_attachments'] = array( // {{{
        'description' => 'pliki podpięte do wpisów',
        'fields' => array(
            'table_name' => $field_type['table_name'],
            'row_id'     => $field_type['id_ref'],
            'file_id'    => $field_type['id_ref'], // REFERENCES scholar_files (fid)
            'language'   => $field_type['language'],
            'weight'     => $field_type['weight'],
            'label'      => array(
                'description' => 'etykieta pliku uzywana np. w nazwie linku do tego pliku',
                'type'      => 'varchar',
                'length'    => 64,
                'not null'  => true,
            ),
        ),
        'primary key' => array('table_name', 'row_id', 'file_id', 'language'),
    ); // }}}

    $schema['scholar_pages'] = array( // {{{
        'description' => 'Lista funkcji generujacych tresci stron. Dlatego tak, zeby wykorzystac mechanizmy dolaczania wezlow i plikow.',
        'fields' => array(
            'id' => $field_type['id'],
            'callback' => array(
                'description' => 'Name of a function that generates page code to be rendered.',
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'title' => array(
                'description' => 'Human-readable page title.',
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
        ),
        'primary key' => array('id'),
        'unique keys' => array(
            'callback' => array('callback'),
        ),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
    ); // }}}

    return $schema;
} // }}}

function scholar_install() // {{{
{
    $dir = file_directory_path() . '/scholar';
    if (!is_dir($dir) && !mkdir($dir, 0777)) {
        trigger_error('scholar_install: Unable to create storage directory: ' . $dir, E_USER_ERROR);
    }

    drupal_install_schema('scholar');

    // raz utworzone strony nie moga zostac usuniete
    db_query("INSERT INTO {scholar_pages} (callback, title) VALUES ('%s', '%s')",
        'scholar_page_publications', 'Publications and results presentation'
        // Publikacje i prezentacja wyników
    );
    db_query("INSERT INTO {scholar_pages} (callback, title) VALUES ('%s', '%s')",
        'scholar_page_conferences', 'Presentations at Conferences, Workshops and Seminars'
        // Wystąpienia na konferencjach, warsztatach i seminariach
    );
} // }}}

function scholar_uninstall() // {{{
{
    variable_del('scholar_last_change');
} // }}}

// cleanup query
// DROP TABLE scholar_events; DROP TABLE scholar_attachments; DROP TABLE scholar_files; DROP TABLE scholar_authors; DROP TABLE scholar_nodes; DROP TABLE scholar_people; DROP TABLE scholar_generic_suppinfo; DROP TABLE scholar_generics; DROP TABLE scholar_category_names; DROP TABLE scholar_categories; DROP TABLE scholar_pages; DELETE FROM system WHERE name = 'scholar';

// vim: fdm=marker
