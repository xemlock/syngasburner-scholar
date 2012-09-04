<?php

function scholar_markup_parser() // {{{
{
    $parser = new scholar_markup_parser;
    $parser->addTag('chapter')
           ->addTag('section');

    return $parser;
} // }}}

function scholar_markup_renderer()
{
    $renderer = new scholar_markup_renderer(array('brInCode' => true));
    $renderer->addConverter('preface', new scholar_markup_converter_preface)
             ->addConverter('chapter', new scholar_markup_converter_chapter)
             ->addConverter('section', new scholar_markup_converter_section)
             ->addConverter('block',   new scholar_markup_converter_block)
             ->addConverter('box',     new scholar_markup_converter_box)
             ->addConverter('asset',   new scholar_markup_converter_asset)
             ->addConverter('youtube', new scholar_markup_converter_youtube)
             ->addConverter('__tag',   new scholar_markup_converter___tag);

    return $renderer;
}

// vim: fdm=marker