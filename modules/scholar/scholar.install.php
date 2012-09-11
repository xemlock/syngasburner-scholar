<?php

/**
 * @param string $type_name
 * @param array $options
 * @return array
 */
function _scholar_schema_type($type_name = null, $options = array()) // {{{
{
    $types = array(
        'id' => array(
            'type'      => 'serial',
            'not null'  => true,
        ),
        'id_ref' => array(
            'description' => 'Identifier (primary key value) of a referenced record',
            'type'      => 'int',
            'not null'  => true,
        ),
        'optional_id_ref' => array(
            'type'      => 'int',
            'not null'  => false,
        ),
        'table_name' => array(
            'description' => 'Table name containing a referenced record, without scholar_ prefix',
            'type'      => 'varchar',
            // polowa maksymalnej dlugosci nazwy tabeli / kolumny w MySQL
            'length'    => 32,
            'not null'  => true,
        ),
        'subtype' => array(
            'description' => 'Subtype of a record',
            'type'      => 'varchar',
            'length'    => 32,
        ),
        'language' => array(
            'description' => 'Name of a language, references languages (language)',
            'type'      => 'varchar', // typem languages.language jest VARCHAR(12)
            'length'    => 12,
            'not null'  => true,
        ),
        'weight' => array(
            'description' => 'Record ordering',
            'type'      => 'int',
            'default'   => 0,
            'not null'  => true,
        ),
        'counter' => array(
            'type'      => 'int',
            'unsigned'  => true,
            'not null'  => true,
            'default'   => 0,
        ),
    );

    if (null === $type_name) {
        return $types;
    }

    return array_merge(
        isset($types[$type_name]) ? $types[$type_name] : array(),
        (array) $options
    );
} // }}}

/**
 * Deklaracja tabel modułu.
 *
 * @return array
 */
function scholar_schema() // {{{
{

    $schema['scholar_nodes'] = array( // {{{
        'description' => 'Bindings between nodes and scholar records.',
        'fields' => array(
            'table_name' => _scholar_schema_type('table_name'),
            'row_id'     => _scholar_schema_type('id_ref'),
            'language'   => _scholar_schema_type('language'),
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
            'title' => array(
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
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
            'id'         => _scholar_schema_type('id'),
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
            'category_id' => _scholar_schema_type('optional_id_ref'),
            'image_id'   => array(
                // REFERENCES image (id)
                'type'      => 'int',
            ),
            'user_id' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
            'create_time' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
        ),
        'primary key'  => array('id'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
    ); // }}}

    $schema['scholar_categories'] = array( // {{{
        'description' => 'typy czasopism, załączników, wykładów',
        'fields' => array(
            'id'         => _scholar_schema_type('id'),
            'table_name' => _scholar_schema_type('table_name'),
            'subtype'    => _scholar_schema_type('subtype'),
            'refcount'   => _scholar_schema_type('counter'), // liczba rekordow powiązanych z tą kategorią
            'user_id' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
            'create_time' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
        ),
        'primary key'  => array('id'),
    ); // }}}

    $schema['scholar_category_names'] = array( // {{{
        'description' => 'Category names',
        'fields' => array(
            'category_id' => _scholar_schema_type('id_ref'),
            'language'    => _scholar_schema_type('language'),
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
        'description' => 'Table for storing generic records: articles, journale (article containers), presentations, conferences (presentation containers), etc.',
        'fields' => array(
            'id'          => _scholar_schema_type('id'),
            'subtype'     => _scholar_schema_type('subtype'),
            'parent_id'   => _scholar_schema_type('optional_id_ref', array(
                'description' => 'identifier of a parent generic record, REFERENCES scholar_generics (id)',
            )),
            'child_count' => _scholar_schema_type('counter'), // liczba rekordow takich ze parent_id jest id tego rekordu
            'category_id' => _scholar_schema_type('optional_id_ref'), // REFERENCES scholar_categories (id)
                                                             // kategoria podtypu, np. podtypem conference jest konferencja, warsztaty lub seminarium
            'start_date' => array(
                'type'        => 'datetime',
            ),
            'start_date_len' => _scholar_schema_type('counter', array(
                'description' => 'number of relevant start date characters',
                'size'        => 'tiny',
            )), 
            'end_date' => array(
                'type'        => 'datetime',
            ),
            'end_date_len' => _scholar_schema_type('counter', array(
                'description' => 'number of relevant end date characters',
                'size'        => 'tiny',
            )),
            'title' => array(
                'type'        => 'varchar',
                'length'      => 255,
                'not null'    => true,
            ),
            'locality' => array(
                'description' => 'name of city / town',
                'type'        => 'varchar',
                'length'      => 128,
            ),
            'country' => array(
                'description' => 'ISO 3166-1 alpha-2 country code',
                'type'        => 'char',
                'length'      => 2,
            ),
            'image_id' => array(
                'description' => 'image identifier, REFERENCES image (id)',
                'type'        => 'int',
            ),
            'url' => array(
                'description' => 'URL to an external website with detailed information (e.g. journal or conference)',
                'type'        => 'varchar',
                'length'      => 255,
            ),
            'bib_details' => array(
                'description' => 'bibliographic details (applies to journals and articles)',
                'type'        => 'varchar',
                'length'      => 255,
            ),
            'bib_authors' => array(
                'description' => 'tekstowa reprezentacja autorów artykułu do umieszczenia w treści, zawierająca max trzy nazwiska',
                'type'        => 'varchar',
                'length'      => 255,
            ),
            'list' => array(
                'description' => 'czy dodawac rekordy do automatycznie generowanych listingow na stronach osob i stronie z wystapieniami na konferencjach',
                'type'        => 'int',
                'size'        => 'tiny',
                'unsigned'    => true,
            ),
            'weight' => _scholar_schema_type('weight'),
            'user_id' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
            'create_time' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
        ),
        'primary key' => array('id'),
        'indexes' => array(
            'parent' => array('parent_id'),
            'subtype_category' => array('subtype', 'category_id'),
        ),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
    ); // }}}

    $schema['scholar_generic_suppinfo'] = array( // {{{
        'description' => 'Short info about related generic record.',
        'fields' => array(
            'generic_id' => _scholar_schema_type('id_ref'),
            'language'   => _scholar_schema_type('language'),
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
            'table_name' => _scholar_schema_type('table_name'),
            'row_id'     => _scholar_schema_type('id_ref'),
            'event_id'   => array(
                // REFERENCES events (id)
                'type'     => 'int',
                'not null' => true,
            ),
            'language'   => _scholar_schema_type('language'),
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
        'description' => 'authors / contributors to articles, presentations',
        'fields' => array(
            'table_name' => _scholar_schema_type('table_name'),
            'row_id'     => _scholar_schema_type('id_ref'), // REFERENCES scholar_generics (id)
            'person_id'  => _scholar_schema_type('id_ref'), // REFERENCES scholar_people (id)
            'weight'     => _scholar_schema_type('weight'),
        ),
        'primary key'  => array('table_name', 'row_id', 'person_id'),
    ); // }}}

    $schema['scholar_files'] = array( // {{{
        // Scholar korzysta z wlasnego systemu zarzadzania plikami, miedzy
        // innymi dlatego, zeby jeden plik mogl byc podpiety do wiecej niz
        // jednego wezla.
        'fields' => array(
            'id' => _scholar_schema_type('id'),
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
                'type'      => 'int',
                'not null'  => true,
            ),
            'create_time' => array(
                'type'      => 'int',
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
            'table_name' => _scholar_schema_type('table_name'),
            'row_id'     => _scholar_schema_type('id_ref'),
            'file_id'    => _scholar_schema_type('id_ref'), // REFERENCES scholar_files (fid)
            'language'   => _scholar_schema_type('language'),
            'weight'     => _scholar_schema_type('weight'),
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
        'fields' => array(
            'id'      => _scholar_schema_type('id'),
            'subtype' => _scholar_schema_type('subtype'),
            'title'   => array(
                'description' => 'Human-readable page title.',
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
        ),
        'primary key' => array('id'),
        'unique keys' => array(
            'subtype' => array('subtype'),
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
    db_query("INSERT INTO {scholar_pages} (id, subtype, title) VALUES (1, '%s', '%s')",
        'publications', 'Publications and results presentation'
        // Publikacje i prezentacja wyników
    );
    db_query("INSERT INTO {scholar_pages} (id, subtype, title) VALUES (2, '%s', '%s')",
        'conferences', 'Presentations at Conferences, Workshops and Seminars'
        // Wystąpienia na konferencjach, warsztatach i seminariach
    );
    db_query("INSERT INTO {scholar_pages} (id, subtype, title) VALUES (3, '%s', '%s')",
        'trainings', 'Trainings'
        // Szkolenia
    );
} // }}}

function scholar_uninstall() // {{{
{
    variable_del('scholar_last_change');
} // }}}

// cleanup query
// DROP TABLE scholar_events; DROP TABLE scholar_attachments; DROP TABLE scholar_files; DROP TABLE scholar_authors; DROP TABLE scholar_nodes; DROP TABLE scholar_people; DROP TABLE scholar_generic_suppinfo; DROP TABLE scholar_generics; DROP TABLE scholar_category_names; DROP TABLE scholar_categories; DROP TABLE scholar_pages; DELETE FROM system WHERE name = 'scholar';

// vim: fdm=marker
