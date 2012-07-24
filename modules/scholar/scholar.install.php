<?php

function scholar_schema()
{
    $schema['scholar_people'] = array(
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
            'title' => array(
                'description' => 'tytuł: dr, mgr, mgr inż. etc',
                'type'      => 'varchar',
                'length'    => 24,
            ),
            'create_time' => array(
                'type'      => 'datetime',
                'not null'  => true,
            ),
            'created_by' => array(
                'description' => 'id użytkownika, który utworzył rekord',
                'type'      => 'int',
                'not null'  => true,
            ),
            'photo' => array(
                'description' => 'zdjęcie osoby',
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'node_id' => array( // wezel musi byc dla kazdego jezyka
                'description' => 'id wezła, w którym umieszczana będzie treść',
                'type'      => 'int',
                'not null'  => true,
            ),
        ),
        'primary key'  => array('id'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',
    );

    $schema['scholar_categories'] = array(
        'description' => 'typy czasopism, załączników, etc.',
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'table_name' => array(
                'description' => 'do rekordów jakiej tabeli można zastostować te kategorie, nazwa tabeli bez prefiksu scholar_',
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'name' => array(
                'description' => 'angielska nazwa kategorii, jej tłumaczenie poprzez moduł Translate',
                'type'      => 'varchar',
                'not null'  => true,
            ),
        ),
        'primary key'  => array('id'),
        'unique keys'  => array(
            'name' => array('table_name', 'name'),
        ),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',
    );

    $schema['scholar_journals'] = array(
        'description' => 'czasopisma i monografie',
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'title' => array(
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'category' => array(
                'type'      => 'int',
                'not null'  => true,
                // references scholar_categories (id)
            ),
            'url' => array(
                'type'      => 'varchar',
                'length'    => 255,
            ),
            'node_id' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
        ),
        'primary key' => array('id'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',
    );

    $schema['scholar_articles'] = array(
        'description' => 'artykuły, prace, wykłady, prezentacje',
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'title' => array(
                'type'      => 'varchar',
                'length'    => 255,
                'not null'  => true,
            ),
            'year' => array(
                'type'      => 'int',
                'not null'  => true,
            ),
            'journal' => array(
                'type'      => 'varchar',
                'length'    => 255,
                // references scholar_journals (id)
            ),
            'details' => array(
                'type'      => 'varchar',
                'length'    => 255,
            ),
        ),
        'primary key'  => array('id'),
        'mysql_suffix' => 'CHARACTER SET utf8 COLLATE utf8_polish_ci',
    );

    $schema['scholar_authors'] = array(
        'description' => 'autorzy artykułów',
        'fields' => array(
            'person_id' => array(
                'type'      => 'int',
                'not null'  => true,
                // references scholar_people (id)
            ),
            'article_id' => array(
                'type'      => 'int',
                'not null'  => true,
                // references scholar_articles (id)
            ),
        ),
        'primary key'  => array('person_id', 'article_id'),
    );

    // to musi byc wielojezykowe, mamy node ids
    $schema['scholar_events'] = array(
        'description' => 'Konferencje, seminaria, warsztaty etc.',
        'fields' => array(
            'id' => array(
                'type'      => 'serial',
                'not null'  => true,
            ),
            'start_date' => array(
                'type'      => 'datetime',
                'not null'  => true,
            ),
            'end_date'  => array(
                'type'      => 'datetime',
            ),
        ),
    );

/*
  $schema['gallery_data'] = array( // {{{
    'description' => 'Gallery metadata.',
    'fields' => array(
      'gallery_id' => array( // references gallery (id)
        'description' => 'Id galerii.',
        'type' => 'int',
        'unsigned' => true,
        'not null' => true,
      ),
      'lang' => array(
        'description' => 'Jezyk w jakim napisane zostaly metadane.',
        'type' => 'char',
        'length' => 2,
        'not null' => true,
      ),
      'title' => array(
        'description' => 'Tytul galerii.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => true,
      ),
      'description' => array(
        'description' => 'Opis galerii.',
        'type' => 'text',
        'not null' => false,
      ),
    ),
    'primary key' => array('gallery_id', 'lang'),
  ); // }}}

  $schema['gallery_node'] = array( // {{{
    'description' => 'References between galleries and nodes.', 
    'fields' => array(
      'node_id' => array(
        'description' => 'Node identifier.',
        'type' => 'int', // references node (nid)
        'unsigned' => true,
        'not null' => true,        
      ),
      'gallery_id' => array(
        'description' => 'Gallery identifier.',
        'type' => 'int', // references gallery (id)
        'unsigned' => true,
        'not null' => true,        
      ),
      'layout' => array(
        'description' => 'Gallery layout type.',
        'type' => 'varchar',
        'length' => 16,
        'not null' => true,
        'default' => 'vertical',
      ),
    ),
    'primary key' => array('gallery_id', 'node_id'),
  ); // }}}
 
  $schema['image'] = array( // {{{
    'description' => 'Image table.',
    'fields' => array(
      'id' => array(
        'description' => 'Image identifier.',
        'type' => 'serial',
        'unsigned' => true,
        'not null' => true,
      ),
      'gallery_id' => array(
        'description' => 'Id galerii, do ktorej nalezy obraz.',
        'type' => 'int', // references gallery (id)
        'unsigned' => true,
        'not null' => false,
      ),
      'mimetype' => array(
        'description' => 'Typ MIME.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => true,
      ),
      'filename' => array(
        'description' => 'Nazwa pliku.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => true,
      ),
      'filesize' => array(
        'description' => 'Rozmiar obrazu.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => true,
      ),
      'width' => array(
        'description' => 'Szerokosc obrazu (px).',
        'type' => 'int',
        'unsigned' => true,
        'not null' => true,
      ),
      'height' => array(
        'description' => 'Wysokosc obrazu (px).',
        'type' => 'int',
        'unsigned' => true,
        'not null' => true,
      ),
      'created' => array(
        'description' => 'Data dodania do bazy.',
        'type' => 'datetime',
        'not null' => true,
      ),
      'weight' => array(
        'description' => 'Kolejnosc sortowania w galerii.',
        'type' => 'int',
        'not null' => true,
        'default' => 0,
      ),
    ),
    'primary key' => array('id'),
  ); // }}}

  $schema['image_data'] = array( // {{{
    'description' => 'Metadane obrazu.',
    'fields' => array(
      'image_id' => array( // references image (id)
        'description' => 'Id obrazu.',
        'type' => 'int',
        'unsigned' => true,
        'not null' => true,
      ),
      'lang' => array(
        'description' => 'Jezyk w jakim napisane zostaly metadane.',
        'type' => 'char',
        'length' => 2,
        'not null' => true,
      ),
      'title' => array(
        'description' => 'Tytul obrazu.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => true,
      ),
      'description' => array(
        'description' => 'Opis obrazu.',
        'type' => 'text',
        'not null' => false,
      ),
    ),
    'primary key' => array('image_id', 'lang'),
  ); // }}}
*/
  return $schema;
}

function scholar_install() {
    drupal_install_schema('scholar');
}

function scholar_uninstall() {
}
