<?php

function events_schema() {
  $schema['events'] = array( // {{{
    'description' => 'Events table.',
    'fields' => array(
      'id' => array(
        'type' => 'serial',
        'not null' => true,
      ),
      'created' => array(
        'type' => 'datetime',
        'not null' => true,
      ),
      'lang' => array(
        'type' => 'char',
        'length' => 2,
        'not null' => true,
      ),
      'start_date' => array(
        'type' => 'datetime',
        'not null' => true,
      ),
      'end_date' => array(
        'type' => 'datetime',
        'not null' => false,
      ),      
      'title' => array(
        'type' => 'varchar',
        'length' => 255,
        'not null' => true,        
      ),
      'body' => array(
        'type' => 'text',
        'not null' => true,
      ),
      'node_path' => array(
        'type' => 'varchar',
        'length' => 128,
      ),
      'image_id' => array(
        'type' => 'int', // references image(id)
      ),      
    ),
    'primary key' => array('id'),
  ); // }}}

  return $schema;
}

function events_install() {
  drupal_install_schema('events');
}

function events_uninstall() {
  // menu_cache_clear_all();
  // drupal_uninstall_schema('gallery');
}

// vim: ft=php
