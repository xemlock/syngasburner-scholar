<?php

/**
 * Deklaracja tabel modułu.
 *
 * @return array
 */
function scholar_schema() // {{{
{
    $schema['scholar_nodes'] = array( // {{{
        'description' => 'numery węzłów zarządzanych przez obiekty scholara',
        'fields' => array(
            'table_name' => array(
                // people, generics, categories
                'type'      => 'varchar',
                'length'    => 32,
                'not null'  => true,
            ),
            'row_id' => array(
                // scholar_people.id OR scholar_generics.id OR scholar_categories.id
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
                'type'      => 'int', // timestamp
            ),
            'body' => array(
                // tresc wezla, ktora po przetworzeniu (renderowaniu)
                // zostanie zapisana do wezla
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
            'color' => array(
                'description' => 'kolor do oznaczenia obiektów danej kategorii np. w kalendarzu',
                'type'      => 'varchar',
                'length'    => 7, // #rrggbb
            ),
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
        'description' => 'Nazwy kategorii',
        'fields' => array(
            'category_id' => array(
                'type' => 'int',
                'not null' => true,
            ),
            'name' => array(
                'description' => 'nazwa kategorii',
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'language' => array(
                'type'      => 'varchar',
                'length'    => 12,
                'not null'  => true,
            ),
        ),
        'primary key' => array('category_id', 'language'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',  
    ); // }}}

    $schema['scholar_generics'] = array( // {{{
        'description' => 'Generyczna tabela na obiekty: kontenery na publikacje: czasopisma, monografie lub wykłady: konferencje, seminaria, oraz ich elementy',
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'parent_id' => array(
                'type'      => 'int', // references scholar_generics (id)
            ),
            'subtype' => array(
                // predefiniowane:  
                //   conference (jak journal ale z dokładną datą początku),
                //   presentation (element składowy conference),
                //   journal (czasopismo, książka, monografia, proceedings),
                //   article (element składowy journal),
                'description' => 'predefiniowany typ, raz ustalony nie podlega zmianie',
                'type'      => 'varchar', 
                'length'    => 32,
                'not null'  => true,
            ), 
            'category_id' => array(
                // REFERENCES scholar_categories (id)
                // kategoria podtypu, np. podtypem conference jest konferencja, warsztaty lub seminarium
                'type'      => 'int',
            ),
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
            'details' => array(
                'description' => 'article - dodatkowa specyfikacja uzupełniająca bibliografię',
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'bib_authors' => array(
                'description' => 'tekstowa reprezentacja autorów artykułu do umieszczenia w treści, zawierająca max trzy nazwiska',
                'type'      => 'varchar',
                'length'    => 128,
            ),
        ),
        'primary key' => array('id'),
        'indexes' => array(
            'parent'        => array('parent_id'),
            'subtype_category' => array('subtype', 'category_id'),
        ),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',
    ); // }}}

    $schema['scholar_events'] = array( // {{{
        'description' => 'Powiazania miedzy rekordami generycznymi a zdarzeniami',
        'fields' => array(
            'table_name' => array(
                'type'      => 'varchar',
                'length'    => 32,
                'not null'  => true,
            ),
            'row_id' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
            'event_id' => array(
                // REFERENCES events (id)
                'type'      => 'int',
                'not null'  => true,
            ),
            'language' => array(
                // REFERENCES languages (language)
                'type'      => 'varchar', // typ languages.language to VARCHAR(12)
                'length'    => 12,
                'not null'  => true,
            ),
            'body' => array(
                // tresc wydarzenia, ktora po przetworzeniu (renderowaniu)
                // zostanie zapisana do wezla
                'type'      => 'text',
                'size'      => 'medium',
            ),
        ),
        'primary key' => array('table_name', 'row_id', 'language'),
        'unique keys' => array(
            'event' => array('event_id'), // kazdy event moze byc podpiety do co najwyzej jednego rekordu generycznego
        ),
    ); // }}}

    $schema['scholar_authors'] = array( // {{{
        'description' => 'autorzy artykułów lub książek',
        'fields' => array(
            'person_id' => array(
                // REFERENCES scholar_people (id)
                'type'      => 'int',
                'not null'  => true,
            ),
            'generic_id' => array(
                // REFERENCES scholar_generics (id)
                'type'      => 'int',
                'not null'  => true,
            ),
            'weight' => array(
                'description' => 'kolejność autorów artykułu',
                'type'      => 'int',
                'not null'  => true,
                'default'   => 0,
            ),
        ),
        'primary key'  => array('person_id', 'generic_id'),
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
            'table_name' => array(
                'description' => 'do rekordu jakiej tabeli nalezy identyfikator',
                'type'      => 'varchar',
                'length'    => 32,
                'not null'  => true,
            ),
            'row_id' => array(
                'description' => 'scholar_people.id albo scholar_generics.id, rozroznienie na podstawie table_name',
                'type'      => 'int',
                'not null'  => true,
            ),
            'file_id' => array(
                // REFERENCES scholar_files (fid)
                'type'      => 'int',
                'not null'  => true,
            ),
            'label' => array(
                'description' => 'etykieta pliku uzywana np. w nazwie linku do tego pliku',
                'type'      => 'varchar',
                'length'    => 64,
                'not null'  => true,
            ),
            'language' => array(
                'description' => 'jezyk etykiety pliku',
                'type'      => 'varchar', // typ languages.language to VARCHAR(12)
                'length'    => 12,
                'not null'  => true,
            ),
            'weight' => array(
                'description' => 'kolejnosc pliku na liscie',
                'type'      => 'int',
                'default'   => 0,
                'not null'  => true,
            ),
        ),
        'primary key' => array('table_name', 'row_id', 'file_id', 'language'),
    ); // }}}

  return $schema;
} // }}}

function scholar_install() // {{{
{
    require_once dirname(__FILE__) . '/models/file.php';

    $dir = scholar_file_path();
    if (!is_dir($dir) && !mkdir($dir, 0777)) {
        trigger_error('scholar_install: Unable to create storage directory: ' . $dir, E_USER_ERROR);
    }

    drupal_install_schema('scholar');
} // }}}

function scholar_uninstall() // {{{
{
    variable_del('scholar_last_change');
} // }}}

// cleanup query
// DROP TABLE scholar_events; DROP TABLE scholar_attachments; DROP TABLE scholar_files; DROP TABLE scholar_authors; DROP TABLE scholar_nodes; DROP TABLE scholar_people; DROP TABLE scholar_generics; DROP TABLE scholar_category_names; DROP TABLE scholar_categories; DELETE FROM system WHERE name = 'scholar';

// vim: fdm=marker
