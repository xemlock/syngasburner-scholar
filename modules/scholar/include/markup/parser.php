<?php

if (class_exists('Zend_Markup_Parser_Bbcode')) {

/**
 * A BBCode markup parser with added support for escaped square brackets.
 */
class scholar_markup_parser extends Zend_Markup_Parser_Bbcode
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

    /**
     * @throws Zend_Markup_Parser_Exception
     */
    public function addTag($name, $properties = array()) // {{{
    {
        if (preg_match('/[^a-z0-9]/i', $name)) {
            throw new Zend_Markup_Parser_Exception('Tag name contains invalid characters. Only alphanumeric characters are allowed.');
        }

        if (isset($properties['type'])) {
            switch ($properties['type']) {
                case self::TYPE_DEFAULT:
                case self::TYPE_SINGLE:
                    $type = $properties['type'];
                    break;

                default:
                    throw new Zend_Markup_Parser_Exception('Invalid tag type specified: \'' . $properties['type'] . '\'');
            }
        } elseif (isset($properties['single'])) {
            $type = $properties['single'] ? self::TYPE_SINGLE : self::TYPE_DEFAULT;

        } else {
            $type = self::TYPE_DEFAULT;
        }

        $stoppers = self::TYPE_DEFAULT == $type
                  ? array('[/' . $name . ']', '[/]')
                  : array();

        $tag = array(
            'type'     => $type,
            'stoppers' => $stoppers,
        );

        if (isset($properties['parse_inside']) && !$properties['parse_inside']) {
            $tag['parse_inside'] = false;
        }

        $this->_tags[$name] = $tag;

        return $this;
    } // }}}

    /**
     * @throws Zend_Markup_Parser_Exception if value is empty
     */
    public function parse($value) // {{{
    {
        // convert escaped square brackets to tags when using array arguments
        // str_replace is faster than strtr, see:
        // http://cznp.com/blog/3/strtr-vs-str_replace-a-battle-for-speed-and-dignity
        $value = str_replace(array('\[', '\]'), array('[ldelim]', '[rdelim]'), $value);
        return parent::parse($value);
    } // }}}
}

}

// vim: fdm=marker
