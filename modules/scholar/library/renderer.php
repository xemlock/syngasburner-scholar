<?php

class scholar_renderer
{
    protected $_converters = array();

    protected $_markup = array( // {{{
        's' => array(
            'start' => '<span style="text-decoration:line-through">',
            'end'   => '</span>',
        ),
        'u' => array(
            'start' => '<span style="text-decoration:underline">',
            'end'   => '</span>',
        ),
        'i' => array(
            'start' => '<span style="font-style:italic">',
            'end'   => '</span>',
        ),
        'b' => array(
            'start' => '<span style="font-weight:bold">',
            'end'   => '</span>',
        ),
        'quote' => arraY(
            'start' => '<blockquote>',
            'end'   => '</blockquote>',
        ),
    ); // }}}

    /**
     * Przekształca drzewo dokumentu BBCode na odpowiadający mu dokument HTML.
     *
     * @param Zend_Markup_TokenList $tree
     *     drzewo dukumentu do przetworzenia
     * @param array $no_render
     *     tagi, które nie będą przekształcane, a ich zawartość zostanie dodana
     *     bez zmian do wynikowego kodu
     */
    public function render(Zend_Markup_TokenList $tree, array $no_render = array())
    {
        $html = $this->_render($tree, $no_render);
        $html = nl2br($html);
        return $html;
    }

    protected function _render(Zend_Markup_TokenList $tree, array $verbatim = array())
    {
        $result = array();

        foreach ($tree as $token) {
            switch ($token->getType()) {
                case Zend_Markup_Token::TYPE_TAG:
                    $tagName  = strtolower($token->getName());
                    $noRender = in_array($tag, $verbatim);

                    if ($noRender) {
                        // fallback to bbcode representation of token, convert
                        // any angle brackets to HTML entities
                        $result[] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $this->fallback($token));
                    
                    } else {
                        $contents = $token->hasChildren() 
                                  ? $this->_render($token->getChildren(), $verbatim) 
                                  : null;

                        // check for available tag renderers
                        if (isset($this->_converters[$tagName])) {
                            $result[] = $this->_converters[$tagName]->convert($token, $contents);

                        } elseif (is_callable($this, $method = 'render' . $tagName)) {
                            $result[] = $this->$method($token, $contents);

                        } else {
                            switch ($tagName) {
                            case 'ldelim':
                                $result[] = '[';
                                break;

                            case 'rdelim':
                                $result[] = ']';
                                break;

                            case '*':
                                $result[] = '<li>' . trim($contents) . '</li>';
                                break;

                            case 'list':
                                $type = Markup::getTokenAttribute($token);
                                $contents = trim($contents);
                                if ($type) {
                                    if (is_numeric($type)) {
                                        $result[] = "<ol start=\"$type\">" . $contents . '</ol>';                
                                    } else {
                                        $result[] = "<ol type=\"$type\">" . $contents . '</ol>';
                                    }
                                } else {
                                    $result[] = '<ul>' . $contents . '</ul>';
                                }
                                break;

                            case 'color':
                                $result[] = "<span style=\"color:" . Markup::getTokenAttribute($token) . "\">" . $contents . "</span>";
                                break;

                            case 'br':
                                $result[] = "<br/>";
                                break;

                            case 'hr':
                                $result[] = "<hr/>";
                                break;

                            case 'url':
                                $url = Markup::getTokenAttribute($token);
                                if (empty($url)) {
                                    $url = $contents;
                                }
                                $result[] = "<a href=\"$url\" target=\"_blank\">$contents</a>";
                                break;
                                
                            case 'quote':
                                $author = Markup::getTokenAttribute($token);
                                if ($author) {
                                    $author = str_replace('"', '', $author);
                                    $pos = strrpos($author, ';');
                                    if ($pos !== false) {
                                        // $post_id = substr($author, $pos + 1);
                                        $author = substr($author, 0, $pos);
                                    }
                                    $name = array_shift(explode($author, ' '));
                                    $author = $author . ' wrote:<br/>';
                                }
                                $result[] = "<blockquote>" . $author . $contents . "</blockquote>";
                                break;

                            case 'code':
                                // syntax hilight
                                $result[] = "<pre><code>" . $this->flatten($token) . '</code></pre>';
                                break;
                              
                            default:
                                $result[] = "<$tag>" . $contents . "</$tag>";
                                break;
                        }
                    }
                    }
                    break;

                case Zend_Markup_Token::TYPE_NONE:
                    // text content of an element is stored in _tag property of a token
                    $result[] = $contents 
                              ? $contents 
                              : str_replace(array('<', '>'), array('&lt;', '&gt;'), $token->getTag());
                    break;
            }
        }
        return implode('', $result);
    }

    /**
     * Adds token converter.
     */
    public function addConverter($tag, $converter) // {{{
    {
        if (!is_callable($converter, 'convert')) {
            throw new Exception('Token converter object must implement method "convert"');
        }

        $this->_converters[$tag] = $converter;
        return $this;
    } // }}}

    public function removeConverter($tag) // {{{
    {
        if (isset($this->_converters[$tag])) {
            unset($this->_converters[$tag]);
        }
        return $this;
    } // }}}

    /**
     * Get value of an attribute with given name, case-insensitive.
     *
     * @param Zend_Markup_Token $token
     *     syntax tree node
     * @param null|string $name
     *     attribute name, if null the name of an attribute with the
     *     same as token name is returned
     * @return null|string
     *     null if not attribute was found
     */
    public function getTokenAttribute(Zend_Markup_Token $token, $name = null) // {{{
    {
        if (null == $name) {
            $name = $token->getName();
        }

        // case sensitive attribute match
        $attr = $token->getAttribute($name);
        if (null !== $attr) {
            return $attr;
        }

        // case insensitive attribute match
        $attrs = $token->getAttributes();
        foreach ($attrs as $key => $value) {
            if (!strcasecmp($key, $name)) {
                return $value;
            }
        }

        return null;
    } // }}}

    /**
     * Conversion from token to it's BBCode tag counterpart.
     *
     * @param Zend_Markup_Token $token
     * @return string
     */
    public function fallback(Zend_Markup_Token $token) // {{{
    {
        switch ($token->getType()) {
            case Zend_Markup_Token::TYPE_TAG:
                $tagName = strtolower($token->getTag());

                switch ($tagName) {
                    case 'ldelim':
                        return '\[';

                    case 'rdelim':
                        return '\]';

                    default:
                        $tag = '[' . $tagName;

                        // render tag attributes
                        $attrs = array();
                        foreach ($token->getAttributes() as $key => $value) {
                            $attrs[strtolower($key)] = strval($value);
                        }

                        // append to tag default attribute
                        if (isset($attrs[$tag])) {
                            $tag .= '="' . $attrs[$tag] . '"';
                            unset($attrs[$tag]);
                        }

                        foreach ($attrs as $key => $value) {
                            $tag .= ' ' . $key . '="' . $value . '"';
                        }

                        $tag .= ']';

                        // get tag contents
                        $contents = array();

                        if ($token->hasChildren()) {
                            foreach ($token->getChildren() as $child) {
                                $contents[] = $this->flatten($child);
                            }
                        }

                        return $tag . implode('', $contents) . $token->getStopper();
                }

            case Zend_Markup_Token::TYPE_NONE:
                // content of a text token is stored in its _tag property
                return $token->getTag();
        }
    } // }}}
}

// vim: fdm=marker
