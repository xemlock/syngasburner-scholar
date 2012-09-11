<?php

function scholar_markup_parser() // {{{
{
    $parser = new scholar_markup_parser;
    $parser->addTag('__language', array('single' => true))
           ->addTag('t', array('parse_inside' => false));

    return $parser;
} // }}}

function scholar_markup_renderer() // {{{
{
    $renderer = new scholar_markup_renderer(array('brInCode' => true));
    $renderer->setDefaultConverter('scholar_markup_converter');
        /*
        'preface', new scholar_markup_converter_preface)
             ->addConverter('chapter', new scholar_markup_converter_chapter)
             ->addConverter('section', new scholar_markup_converter_section)
             ->addConverter('block',   new scholar_markup_converter_block)
             ->addConverter('box',     new scholar_markup_converter_box)
             ->addConverter('asset',   new scholar_markup_converter_asset)
             ->addConverter('youtube', new scholar_markup_converter_youtube)
             ->addConverter('t',       new scholar_markup_converter_t)
             ->addConverter('node',    new scholar_markup_converter_node)
             ->addConverter('__tag',      new scholar_markup_converter___tag)
             ->addConverter('__language', new scholar_markup_converter___language);
         */

    return $renderer;
} // }}}

// vim: fdm=marker
