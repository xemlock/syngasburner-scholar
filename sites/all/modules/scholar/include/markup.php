<?php

function scholar_markup_parser() // {{{
{
    $parser = new scholar_markup_parser;
    $parser->addTag('__language', array('single' => true))
           ->addTag('br', array('single' => true))
           ->addTag('t', array('parse_inside' => false));

    return $parser;
} // }}}

function scholar_markup_renderer() // {{{
{
    $renderer = new scholar_markup_renderer;
    $renderer->setDefaultConverter('scholar_markup_converter');

    return $renderer;
} // }}}

// vim: fdm=marker
