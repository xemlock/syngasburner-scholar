<?php

/**
 * @param string $name
 * @return mixed
 */
function scholar_setting($name) // {{{
{
    static $settings;

    $name = (string) $name;

    if (!array_key_exists($name, $settings)) {
        $func = 'scholar_setting_' . $name;

        if (function_exists($func)) {
            $settings[$name] = call_user_func($func);
        } else {
            $settings[$name] = variable_get('scholar_' . $name, null);
        }
    }

    return $settings[$name];
} // }}}

function scholar_setting_dateformat_single()
{
    
}

// vim: fdm=marker
