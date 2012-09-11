<?php

/**
 * @param Zend_Markup_Token $token
 * @param string $contents
 * @return string
 */
function scholar_markup_converter_default($token, $contents)
{
    $tagName = strtolower($token->getName());

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

    switch ($tagName) {
        case 'br':
            $result[] = "<br/>";
            break;

                        case 'rule':
                            $result[] = "<hr/>";
                            break;

                        case 'item':
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

        // make sure list contents are LI tags only
        if (preg_match('/^<li[ >]/i', $contents) && preg_match('/<\/li>$/i', $contents)) {
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

