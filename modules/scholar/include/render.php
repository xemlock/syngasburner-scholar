<?php

function render_people_node($id)
{
    $person = scholar_load_person($id);
    
    if (empty($person)) {
        return '';
    }

    return __FUNCTION__;
}

function render_categories_node($id)
{
    $category = scholar_load_category($id);

    if (empty($category)) {
        return '';
    }

    return __FUNCTION__;
}

function render_generics_node($id)
{
    $generic = scholar_load_generic($id);

    if (empty($generic)) {
        return '';
    }

    return __FUNCTION__;
}
