<?php

/**
 * A BBCode markup parser with added support for escaped square brackets.
 */
class scholar_parser extends Zend_Markup_Parser_Bbcode
{
    public function __construct() // {{{
    {
        // add tags for left and right square brackets. It is a shame that
        // there is no other way to do this, than adding tags in overloaded
        // constructor.
        $this->_tags['ldelim'] = array(
            'type'     => self::TYPE_SINGLE,
            'stoppers' => array(),
        );
        $this->_tags['rdelim'] = array(
            'type'     => self::TYPE_SINGLE,
            'stoppers' => array(),
        );

        // no constructor in the parent class
        // parent::__construct();
    } // }}}

    public function parse($value) // {{{
    {
        // convert escaped square brackets to tags when using array arguments
        // str_replace is faster than strtr, see:
        // http://cznp.com/blog/3/strtr-vs-str_replace-a-battle-for-speed-and-dignity
        $value = str_replace(array('\[', '\]'), array('[ldelim]', '[rdelim]'), $value);
        return parent::parse($value);
    } // }}}
}

// vim: fdm=marker
