<?php

/**
 * Schema modułu scholar.
 *
 * @author xemlock
 * @version 2012-07-31
 */
function scholar_schema() // {{{
{
    $schema['scholar_nodes'] = array( // {{{
        'description' => 'numery węzłów zarządzanych przez obiekty scholara',
        'fields' => array(
            'table_name' => array(
                // people OR objects
                'type'      => 'varchar',
                'length'    => 32,
                'not null'  => true,
            ),
            'object_id' => array(
                // scholar_people.id OR scholar_objects.id
                'type'      => 'int',
                'not null'  => true,
            ),
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
            'language' => array(
                // REFERENCES languages (language)
                'type'      => 'varchar', // typ languages.language to VARCHAR(12)
                'length'    => 12,
                'not null'  => true,
            ),
            'status' => array( // czy wezel opublikowany
                'type'      => 'int',
                'size'      => 'tiny',
                'not null'  => true,
            ),
            'last_rendered' => array(
                // kiedy ostatnio renderowano zawartosc wezla, porownywane
                // z variable(name='scholar_last_change')
                // pusta wartosc oznacza koniecznosci wygenerowania tresci
                'type'      => 'datetime'
            ),
            'body' => array(
                // tresc wezla, ktora po przetworzeniu (renderowaniu)
                // zostanie zapisana do wezla
                'type'      => 'text',
                'size'      => 'big',
            ),
        ),
        'primary key' => array('table_name', 'object_id', 'language'),
        'unique keys' => array(
            'node'  => array('node_id'),
        ),
    ); // }}}

    $schema['scholar_people'] = array( // {{{
        'description' => 'osoby - autorzy prac, prowadzący wykłady etc.',
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'first_name' => array(
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'last_name' => array(
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'image_id' => array(
                // REFERENCES image (id)
                'type'      => 'int',
            ),
        ),
        'primary key'  => array('id'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',
    ); // }}}

    $schema['scholar_categories'] = array( // {{{
        'description' => 'typy czasopism, załączników, wykładów',
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'table_name' => array(
                'description' => 'do rekordów jakiej tabeli można zastostować te kategorie, nazwa tabeli bez prefiksu scholar_',
                'type'      => 'varchar',
                'length'    => 32,
                'not null'  => true,
            ),
            'subtype' => array(
                'description' => 'podtyp w tabeli, może być pusty jeżeli tabela tego nie wymaga',
                'type'      => 'varchar',
                'length'    => 32,
            ),
            'name' => array(
                'description' => 'angielska nazwa kategorii, jej tłumaczenie poprzez moduł Translate',
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'color' => array(
                'description' => 'kolor do oznaczenia obiektów danej kategorii np. w kalendarzu',
                'type'      => 'varchar',
                'length'    => 7,
            ),
        ),
        'primary key'  => array('id'),
        'unique keys'  => array(
            'name' => array('table_name', 'subtype', 'name'),
        ),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',
    ); // }}}

    $schema['scholar_objects'] = array( // {{{
        'description' => 'Generyczna tabela na obiekty: kontenery na publikacje: czasopisma, monografie lub wykłady: konferencje, seminaria, oraz ich elementy',
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'parent_id' => array(
                'type'      => 'int', // references scholar_objects (id)
            ),
            'subtype' => array(
                // predefiniowane: 
                //   journal (czasopismo, książka, monografia, proceedings), 
                //   conference (jak journal ale z dokładną datą początku),
                //   article (element składowy journal),
                //   lecture (element składowy conference),
                //   block (generyczny kontener używany w CV, istotny tylko tytuł),
                //   resume (element bloku w CV)
                'description' => 'predefiniowany typ, raz ustalony nie podlega zmianie',
                'type'      => 'varchar', 
                'length'    => 32,
                'not null'  => true,
            ), 
            'category' => array(
                // REFERENCES scholar_categories (id)
                // kategoria podtypu, np. podtypem conference jest konferencja, warsztaty lub seminarium
                'type'      => 'int',
                'not null'  => true,
            ),
            'start_date'     => array(
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
            'details' => array(
                'description' => 'article: dodatkowa specyfikacja uzupełniająca bibliografię',
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'url' => array(
                'description' => 'zewnętrzny URL strony czasopisma lub konferencji',
                'type'      => 'varchar',
                'length'    => 255,
            ),
        ),
        'primary key' => array('id'),
        'indexes' => array(
            'parent_id'        => array('parent_id'),
            'subtype_category' => array('subtype', 'category'),
        ),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',
    ); // }}}

    $schema['scholar_authors'] = array( // {{{
        'description' => 'autorzy artykułów',
        'fields' => array(
            'person_id' => array(
                // REFERENCES scholar_people (id)
                'type'      => 'int',
                'not null'  => true,
            ),
            'object_id' => array(
                // REFERENCES scholar_objects (id)
                'type'      => 'int',
                'not null'  => true,
            ),
            'author_order' => array(
                'description' => 'kolejność autorów artykułu',
                'type'      => 'int',
                'not null'  => true,
                'default'   => 0,
            ),
        ),
        'primary key'  => array('person_id', 'object_id'),
    ); // }}}

    $schema['scholar_files'] = array( // {{{
        // Scholar korzysta z wlasnego systemu zarzadzania plikami, miedzy
        // innymi dlatego, zeby jeden plik mogl byc podpiety do wiecej niz
        // jednego wezla.
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'user_id' => array(
                // moze byc pusty
                'type'      => 'int',
            ),
            'filename' => array(
                'description' => 'nazwa pliku na dysku wewnatrz katalogu z plikami modulu',
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
            'upload_time' => array(
                'type'      => 'datetime',
                'not null'  => true,
            ),
            'refcount' => array(
                'description' => 'Liczba obiektow odwolujacych sie do tego pliku',
                'type'      => 'int',
                'unsigned'  => true,
		'not null'  => true,
		'default'   => 0,
            )
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
            'file_id' => array(
                // REFERENCES scholar_files (fid)
                'type'      => 'int',
                'not null'  => true,
            ),
            'node_id' => array(
                // REFERENCES node (nid)
                'type'      => 'int',
                'not null'  => true,
            ),
            'label' => array(
                'description' => 'etykieta pliku, np. w nazwie linku',
                'type'      => 'varchar',
                'length'    => 64,
                'not null'  => true,
            ),
        ),
        'primary key' => array('file_id', 'node_id'),
    ); // }}}


  return $schema;
} // }}}

function scholar_install() // {{{
{
    require_once dirname(__FILE__) . '/scholar.file.php';
    $dir = scholar_file_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777)) {
        trigger_error('scholar_install: Unable to create storage directory: ' . $dir, E_USER_ERROR);
    }

    drupal_install_schema('scholar');
} // }}}

function scholar_uninstall() // {{{
{
    variable_del('scholar_last_change');
} // }}}

// DROP TABLE scholar_attachments; DROP TABLE scholar_files; DROP TABLE scholar_authors; DROP TABLE scholar_nodes; DROP TABLE scholar_people; DROP TABLE scholar_objects; DROP TABLE scholar_categories; DELETE FROM system WHERE name = 'scholar';

