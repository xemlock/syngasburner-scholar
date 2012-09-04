<?php

function form_type_scholar_element_timespan_value($element, $post = false)
{}

function form_type_scholar_element_timespan_process($element)
{
    return $element;
}

function form_type_scholar_element_timespan_validate($element, &$form_state)
{}

function theme_scholar_element_timespan($element)
{
    return "<input size=2 placeholder=HH maxlength=2/>:<input size=2 placeholder=MM  maxlength=2 /> - <input size=2 placeholder=HH maxlength=2 />:<input size=2 placeholder=MM maxlength=2 />";
}

// vim: fdm=marker
