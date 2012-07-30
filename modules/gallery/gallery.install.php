<?php

function gallery_schema() {
  $schema['gallery'] = array( // {{{
    'description' => 'Gallery table.',
    'fields' => array(
      'id' => array(
        'description' => 'Gallery identifier.',
        'type' => 'serial',
        'not null' => true,
      ),
      'created' => array(
        'description' => 'Creation date.',
        'type' => 'datetime',
        'not null' => true,
      ),
    ),    
    'primary key' => array('id'),
  ); // }}}

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

  return $schema;
}

// Drupal pamieta wszystkie zainstalowane moduly w tabeli system
// w kolumnie 'name' znajduje sie nazwa modulu. Zeby wymusic hook
// install tego modulu, trzeba usunac odpowiadajacy mu wpis.
function gallery_install() {
  drupal_install_schema('gallery');
}

function gallery_uninstall() {
  // menu_cache_clear_all();
  // drupal_uninstall_schema('gallery');
}

// vim: ft=php
