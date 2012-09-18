<?php

class scholar_markup_renderer
{
    protected $_brInPre = true;
    protected $_rawTags = array();
    protected $_forbiddenTags = array();

    protected $_defaultConverter;
    protected $_converters = array();

    public function __construct($options = array()) // {{{
    {
        if ($options) {
            $this->setOptions($options);
        }
    } // }}}

    /**
     * Przekształca drzewo dokumentu BBCode na odpowiadający mu dokument HTML.
     *
     * @param Zend_Markup_TokenList $tree
     *     drzewo dokumentu do przetworzenia
     * @param array $no_render
     *     tagi, które nie będą przekształcane, a ich zawartość zostanie dodana
     *     bez zmian do wynikowego kodu
     */
    public function render(Zend_Markup_TokenList $tree) // {{{
    {
        $html = $this->_render($tree);
        $html = nl2br($html);
        return $html;
    } // }}}

    public function setOptions($options = array()) // {{{
    {
        foreach ((array) $options as $key => $value) {
            $method = 'set' . $key;

            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        return $this;
    } // }}}

    protected function _tagList($array) // {{{
    {
        $tags = array();

        foreach ((array) $array as $value) {
            $tags[strtolower($value)] = true;
        }

        return $tags;
    } // }}}
    
    /**
     * Set tags that will be included directly (with no processing)
     * in the rendering result.
     *
     * @param array $tags
     */
    public function setRawTags($tags) // {{{
    {
        $this->_rawTags = $this->_tagList($tags);
        return $this;
    } // }}}

    /**
     * Set tags that that will be ignored during rendering.
     *
     * @param array $tags
     */
    public function setForbiddenTags($tags) // {{{
    {
        $this->_forbiddenTags = $this->_tagList($tags);
        return $this;
    } // }}}

    public function isRawTag($tag) // {{{
    {
        return isset($this->_rawTags[strtolower($tag)]);
    } // }}}

    public function isForbiddenTag($tag) // {{{
    {
        return isset($this->_forbiddenTags[strtolower($tag)]);
    } // }}}

    protected function _render(Zend_Markup_TokenList $tree, $depth = 0) // {{{
    {
        $result = array();

        foreach ($tree as $token) {
            $type = $token->getType();

            if (Zend_Markup_Token::TYPE_TAG == $type) {
                $tagName = strtolower($token->getName());

                if ($this->isForbiddenTag($tagName)) {
                    // ignore this tag, but try to render its contents
                    // (watch out for lists!)
                    if ($token->hasChildren()) {
                        $result[] = $this->_render($token->getChildren(), $depth + 1);
                    }
                    continue;
                }

                if ($this->isRawTag($tagName)) {
                    // fallback to bbcode representation of this token, convert
                    // any angle brackets to HTML entities
                    $result[] = htmlspecialchars($this->rawTag($token));
                    continue;
                }

                switch ($tagName) {
                    case 'ldelim':
                        $result[] = '[';
                        break;

                    case 'rdelim':
                        $result[] = ']';
                        break;

                    case 'noparse':
                        if ($token->hasChildren()) {
                            foreach ($token->getChildren() as $child) {
                                $result[] = $this->rawTag($child);
                            }
                        }
                        break;

                    default:
                        $contents = $token->hasChildren() 
                            ? $this->_render($token->getChildren(), $depth + 1) 
                            : '';

                        $converter = $this->getConverter($tagName);

                        // run converter on this tag
                        if (is_callable($converter)) {
                            $result[] = (string) call_user_func($converter, $token, $contents);

                        } else if (is_object($converter) && is_callable($converter, 'convert')) {
                            $result[] = (string) $converter->convert($token, $contents);

                        } else {
                            $result[] = $contents;
                        }

                        break;
                }

            } else if (Zend_Markup_Token::TYPE_NONE == $type) {
                // text content of an element is stored in _tag property of a token,
                // Root token has children and its type is TYPE_NONE, do not increment
                // depth counter if starting from root token.
                if ($token->hasChildren() && 'Zend_Markup_Root' == $token->getName()) {
                    $result[] = $this->_render($token->getChildren(), 0);

                } else {
                    $result[] = htmlspecialchars($token->getTag());
                }
            }
        }

        return implode('', $result);
    } // }}}

    protected function _checkConverter($converter) // {{{
    {
        if (!(is_callable($converter) || (is_object($converter) && is_callable($converter, 'convert')))) {
            throw new Exception("Token converter must be a valid callback or an object implementing the 'convert' method");
        }
    } // }}}

    /**
     * @param null|callable $converter
     */
    public function setDefaultConverter($converter) // {{{
    {
        if (null !== $converter) {
            $this->_checkConverter($converter);
        }
        $this->_defaultConverter = $converter;
        return $this;
    } // }}}

    /**
     * Adds token converter to handle given tags.
     *
     * @param string $tags
     * @param callback $converter
     */
    public function addConverter($tags, $converter) // {{{
    {
        $this->_checkConverter($converter);        

        foreach (explode(' ', $tags) as $tag) {
            if (strlen($tag)) {
                $this->_converters[strtolower($tag)] = $converter;
            }
        }

        return $this;
    } // }}}

    public function getConverter($tag) // {{{
    {
        $tag = strtolower($tag);
        return isset($this->_converters[$tag]) ? $this->_converters[$tag] : $this->_defaultConverter;
    } // }}}

    public function removeConverter($tag) // {{{
    {
        $tag = strtolower($tag);

        if (isset($this->_converters[$tag])) {
            unset($this->_converters[$tag]);
        }

        return $this;
    } // }}}

    public function rawTag(Zend_Markup_Token $token) // {{{
    {
        switch ($token->getType()) {
            case Zend_Markup_Token::TYPE_TAG:
                $tagName = strtolower($token->getTag());

                switch ($tagName) {
                    // since ldelim and rdelim are internal names convert them
                    // to escaped square brackets
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
                                $contents[] = $this->rawTag($child);
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
