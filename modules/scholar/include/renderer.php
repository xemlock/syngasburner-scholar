<?php

class scholar_renderer
{
    protected $_brInPre = true;
    protected $_rawTags = array();
    protected $_forbiddenTags = array();

    protected $_converters = array( // {{{
    ); // }}}

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
        'sub' => array(
            'start' => '<sub>',
            'end'   => '</sub>',
        ),
        'sup' => array(
            'start' => '<sup>',
            'end'   => '</sup>',
        ),
    ); // }}}

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
     *     drzewo dukumentu do przetworzenia
     * @param array $no_render
     *     tagi, które nie będą przekształcane, a ich zawartość zostanie dodana
     *     bez zmian do wynikowego kodu
     */
    public function render(Zend_Markup_TokenList $tree) // {{{
    {
        $html = $this->_render($tree);
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

    /**
     * Set tags that will be included directly (with no processing)
     * in the rendering result.
     *
     * @param array $tags
     */
    public function setRawTags($tags) // {{{
    {
        $this->_rawTags = array_map('strtolower', (array) $tags);
        return $this;
    } // }}}

    /**
     * Set tags that that will be ignored during rendering.
     *
     * @param array $tags
     */
    public function setForbiddenTags($tags) // {{{
    {
        $this->_forbiddenTags = array_map('strtolower', (array) $tags);
        return $this;
    } // }}}

    public function isRawTag($tag) // {{{
    {
        return in_array(strtolower($tag), $this->_rawTags);
    } // }}}

    public function isForbiddenTag($tag) // {{{
    {
        return in_array(strtolower($tag), $this->_forbiddenTags);
    } // }}}

    /**
     * Whether all newlines inside PRE tag should be converted to BR tags,
     * or vice-versa. This setting is used by {@see renderCode} method.
     *
     * @param bool $flag
     */ 
    public function setBrInPre($flag = true) // {{{
    {
        $this->_brInPre = (bool) $flag;
        return $this;
    } // }}}

    public function getBrInPre() // {{{
    {
        return $this->_brInPre;
    } // }}}

    protected function _render(Zend_Markup_TokenList $tree, $depth = 0) // {{{
    {
        $result = array();

        foreach ($tree as $token) {
            $type = $token->getType();

            if (Zend_Markup_Token::TYPE_TAG == $type) {
                $tagName  = strtolower($token->getName());

                if ($this->isForbiddenTag($tagName)) {
                    continue;
                }

                if ($this->isRawTag($tagName)) {
                    // fallback to bbcode representation of this token, convert
                    // any angle brackets to HTML entities
                    $result[] = htmlspecialchars($this->rawTag($token));
                    continue;
                }

                $contents = $token->hasChildren() 
                          ? $this->_render($token->getChildren(), $depth + 1) 
                          : null;

                // check for available tag renderers
                if (isset($this->_converters[$tagName])) {
                    $result[] = $this->_converters[$tagName]->convert($token, $contents);

                } elseif (is_callable(array($this, $method = 'convert' . $tagName))) {
                    $result[] = $this->$method($token, $contents);

                } else {
                    switch ($tagName) {
                        case 'ldelim':
                            $result[] = '[';
                            break;

                        case 'rdelim':
                            $result[] = ']';
                            break;

                        case 'br':
                            $result[] = "<br/>";
                            break;

                        case 'hr':
                            $result[] = "<hr/>";
                            break;

                        case '*':
                            $result[] = '<li>' . trim($contents) . '</li>';
                            break;

                        default:
                            if (isset($this->_markup[$tagName])) {
                                $result[] = $this->_markup[$tagName]['start']
                                          . $contents
                                          . $this->_markup[$tagName]['end'];
                            }
                            break;
                    }
                }

            } else if (Zend_Markup_Token::TYPE_NONE == $type) {
                // text content of an element is stored in _tag property of a token,
                // Root token has children and its type is TYPE_NONE, do not increment
                // depth counter if starting from root token.
                if ($token->hasChildren() && 'Zend_Markup_Root' == $token->getName()) {
                    $result[] = $this->_render($token->getChildren(), 0);

                } else {
                    $contents = htmlspecialchars($token->getTag());

                    // convert newlines to BR if outside any tag
                    $result[] = !$depth ? nl2br($contents) : $contents;
                }
            }
        }

        return implode('', $result);
    } // }}}

    /**
     * Adds token converter.
     */
    public function addConverter($tag, $converter) // {{{
    {
        if (!is_callable(array($converter, 'convert'))) {
            throw new Exception('Token converter object must implement method "convert"');
        }

        $this->_converters[strtolower($tag)] = $converter;
        return $this;
    } // }}}

    public function getConverter($tag) // {{{
    {
        $tag = strtolower($tag);
        return isset($this->_converters[$tag]) ? $this->_converters[$tag] : null;
    } // }}}

    public function removeConverter($tag) // {{{
    {
        $tag = strtolower($tag);

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
    public static function getTokenAttribute(Zend_Markup_Token $token, $name = null) // {{{
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

    public static function validateUrl($url) // {{{
    {
        $url = (string) $url;

        $scheme = '(ftp|https?):\/\/';
        $host = '[a-z0-9](\.?[a-z0-9\-]*[a-z0-9])*';
        $port = '(:\d+)?';
        $path = '(\/[^\s]*)*';

        if (preg_match("/^$scheme$host$port$path$/i", $url)) {
            return $url;
        }

        return null;
    } // }}}

    public static function validateColor($color) { // {{{
        // trim white spaces (white spaces are ignored in CSS)
        $color = trim(strtolower($color));

        // CSS 2: extended color list
        $colors = array(
            'aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure',
            'beige', 'bisque', 'black', 'blanchedalmond', 'blue', 'blueviolet',
            'brown', 'burlywood', 'cadetblue', 'chartreuse', 'chocolate',
            'coral', 'cornflowerblue', 'cornsilk', 'crimson', 'cyan',
            'darkblue', 'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgreen',
            'darkgrey', 'darkkhaki', 'darkmagenta', 'darkolivegreen',
            'darkorange', 'darkorchid', 'darkred', 'darksalmon',
            'darkseagreen', 'darkslateblue', 'darkslategray', 'darkslategrey',
            'darkturquoise', 'darkviolet', 'deeppink', 'deepskyblue',
            'dimgray', 'dimgrey', 'dodgerblue', 'firebrick', 'floralwhite',
            'forestgreen', 'fuchsia', 'gainsboro', 'ghostwhite', 'gold',
            'goldenrod', 'gray', 'green', 'greenyellow', 'grey', 'honeydew',
            'hotpink', 'indianred', 'indigo', 'ivory', 'khaki', 'lavender',
            'lavenderblush', 'lawngreen', 'lemonchiffon', 'lightblue',
            'lightcoral', 'lightcyan', 'lightgoldenrodyellow', 'lightgray',
            'lightgreen', 'lightgrey', 'lightpink', 'lightsalmon',
            'lightseagreen', 'lightskyblue', 'lightslategray',
            'lightslategrey', 'lightsteelblue', 'lightyellow', 'lime',
            'limegreen', 'linen', 'magenta', 'maroon', 'mediumaquamarine',
            'mediumblue', 'mediumorchid', 'mediumpurple', 'mediumseagreen',
            'mediumslateblue', 'mediumspringgreen', 'mediumturquoise',
            'mediumvioletred', 'midnightblue', 'mintcream', 'mistyrose',
            'moccasin', 'navajowhite', 'navy', 'oldlace', 'olive', 'olivedrab',
            'orange', 'orangered', 'orchid', 'palegoldenrod', 'palegreen',
            'paleturquoise', 'palevioletred', 'papayawhip', 'peachpuff',
            'peru', 'pink', 'plum', 'powderblue', 'purple', 'red', 'rosybrown',
            'royalblue', 'saddlebrown', 'salmon', 'sandybrown', 'seagreen',
            'seashell', 'sienna', 'silver', 'skyblue', 'slateblue',
            'slategray', 'slategrey', 'snow', 'springgreen', 'steelblue',
            'tan', 'teal', 'thistle', 'tomato', 'turquoise', 'violet', 'wheat',
            'white', 'whitesmoke', 'yellow', 'yellowgreen',
        );

        if (in_array($color, $colors)) {
            return $color;
        }

        // #rrggbb
        if (preg_match('/^\#[0-9a-f]{6}$/i', $color)) {
            return $color;
        }

        // #rgb
        if (preg_match('/^\#[0-9a-f]{3}$/i', $color)) {
            // From CSS level 1 spec: the three-digit RGB notation (#rgb)
            // is converted into six-digit form (#rrggbb) by replicating digits
            $r = substr($color, 1, 1);
            $g = substr($color, 2, 1);
            $b = substr($color, 3, 1);
            return "#$r$r$g$g$b$b";
        }

        // rgb(r,g,b), each part can be an integer or a precentage value
        $part_re = '\s*(\d+%?)\s*';
        if (preg_match('/^rgb\(' . $part_re . ',' . $part_re . ',' . $part_re . '\)$/i', $color, $matches)) {
            // first element containing the whole match is now useless, and
            // can safely be used as a storage for hash character
            $matches[0] = '#'; 
            for ($i = 1, $n = count($matches); $i < $n; ++$i) {
                $part = $matches[$i];
                if (substr($part, -1) == '%') {
                    $part = round(substr($part, 0, -1) * 255 / 100.);
                }
                // From CSS level 1 spec: Values outside the numerical ranges
                // should be clipped.
                $part = dechex(min($part, 255));
                if (strlen($part) < 2) {
                    $part = '0' . $part;
                }
                $matches[$i] = $part;
            }
            return implode('', $matches);
        }

        // no rgba support for compatibility with older browsers

        // unable to normalize
        return null;
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

    public function convertCode(Zend_Markup_Token $token, $contents) // {{{
    {
        // class name is for code highlighting, it is
        // compatible with default highlight.js settings
        $code = $token->getAttribute('code');
        $code = preg_replace('/[^_a-z0-9]/i', '', $code);

        // convert all BR tags to newlines
        $contents = preg_replace('/<br\s*\/?>/i', "\n", $contents);
        $contents = htmlspecialchars($contents);

        if ($this->_brInPre) {
            $contents = nl2br($contents);
        }

        return '<pre><code' . ($code ? ' class="' . $code . '"' : '') . '>'
             . $contents . '</code></pre>';
    } // }}}

    public function convertColor(Zend_Markup_Token $token, $contents) // {{{
    {
        $color = $token->getAttribute('color');
        $color = self::validateColor($color);

        if ($color) {
            return '<span style="color:' . $color . '">' . $contents . '</span>';
        }

        return $contents;
    } // }}}

    public function convertImg(Zend_Markup_Token $token, $contents) // {{{
    {
        // [img]{url}[/img]
        // [img width={width} height={height}]{url}[/img]

        $width  = intval($token->getAttribute('width'));
        $height = intval($token->getAttribute('height'));

        if ($width <= 0 || $height <= 0) {
            $width  = 0;
            $height = 0;
        }

        $attrs = ' src="' . htmlspecialchars($contents) . '"'
               . ' alt="' . htmlspecialchars($token->getAttribute('alt')) . '"';

        if ($width && $height) {
            $attrs .= ' width="' . $width . '" height="' . $height . '"';
        }

        return '<img' . $attrs . '/>';
    } // }}}

    public function convertList(Zend_Markup_Token $token, $contents) // {{{
    {
        $contents = trim($contents);

        if ($contents) {
            if ($type = $token->getAttribute('list')) {
                if (is_numeric($type)) {
                    return '<ol start="' . $type . '">' . $contents . '</ol>';                
                } else {
                    return '<ol type="' . $type . '">' . $contents . '</ol>';
                }
            } else {
                return '<ul>' . $contents . '</ul>';
            }
        }

        return '';
    } // }}}

    public function convertUrl(Zend_Markup_Token $token, $contents) // {{{
    {
        $url = $token->getAttribute('url');

        if (empty($url)) {
            $url = $contents;
        }

        $url = self::validateUrl($url);

        if ($url) {
            if ('_self' == $token->getAttribute('target')) {
                $target = '_self';
            } else {
                $target = '_blank';
            }

            return '<a href="' . $url . '" target="' . $target . '">' . $contents . '</a>';
        }

        return $contents;
    } // }}}
}

// vim: fdm=marker