<?php

/**
 * Schema modułu scholar.
 *
 * @author xemlock
 * @version 2012-07-27
 */
function scholar_schema()
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
            'last_rendered' => array(
                // kiedy ostatnio renderowano zawartosc wezla
                'type'      => 'datetime'
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
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'status' => array( // czy wezly opublikowane
                'type'      => 'int',
                'size'      => 'tiny',
                'not null'  => true,
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
            'has_node' => array(
                'type'      => 'int',
                'size'      => 'tiny',
                'not null'  => true,
            ),
            'gallery_id' => array(
                'description' => 'id galerii - każdy rekord może mieć powiązaną galerię, unikalną dla siebie',
                'type'      => 'int',
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
        'description' => 'pliki podpięte do wpisów',
        'fields' => array(
            'file_id' => array(
                // REFERENCES files (fid)
                'type'      => 'int',
                'not null'  => true,
            ),
            'object_id' => array(
                // REFERENCES scholar_objects (id)
                'type'      => 'int',
                'not null'  => true,
            ),
            'category_id' => array(
                // REFERENCES scholar_categories (id)
                'type'      => 'int',
                'not null'  => true,
            ),
        ),
        'primary key' => array('file_id'),
    ); // }}}

  return $schema;
}

function scholar_install() {
    drupal_install_schema('scholar');
}

function scholar_uninstall() {
}
