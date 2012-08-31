<?php

interface scholar_converter // {{{
{
    public function convert($token, $contents);
} // }}}

class scholar_converter_preface implements scholar_converter // {{{
{
    protected $_prefaces = array();

    public function convert($token, $contents)
    {
        $this->_prefaces[] = $contents;
        return '';
    }

    public function render()
    {
        return implode('', $this->_prefaces);
    }
} // }}}

class scholar_converter_chapter implements scholar_converter // {{{
{
    public function convert($token, $contents)
    {
        return '<div class="scholar-chapter"><h1>' . str_replace("''", '"', $token->getAttribute('chapter')) . '</h1>' . trim($contents) . '</div>';
    }
} // }}}

class scholar_converter_section implements scholar_converter // {{{
{
    public function convert($token, $contents)
    {
        return '<div class="scholar-section"><h2>' . str_replace("''", '"', $token->getAttribute('section')) . '</h2>' . trim($contents) . '</div>';
    }
} // }}}

class scholar_converter_block implements scholar_converter // {{{
{
    public function convert($token, $contents)
    {
        $date = $token->getAttribute('block');
        $result = '';
        if ($date) {
            $date = str_replace('--', ' &ndash; ', $date);
            $result .= '<div class="tm">' . trim($date) . '</div>';
        }
        $result .= '<div class="details">' . trim($contents) . '</div>';
        return '<div class="scholar-block">' . $result . '</div>';
    }
} // }}}

class scholar_converter_box implements scholar_converter // {{{
{
    public function convert($token, $contents)
    {
        return '<div>' . trim($contents) . '</div>';
    }
} // }}}

class scholar_converter_res implements scholar_converter // {{{
{
    public function convert($token, $contents)
    {
        $label = trim($token->getAttribute('res'));

        if (empty($label)) {
            $label = basename($contents);
        }

        return '<a class="scholar-res" href="' . htmlspecialchars($contents) . '">'
             . htmlspecialchars($label) . '</a>';
    }
} // }}}

// wewnetrzne konwertery nie do dokumentacji
class scholar_converter___tag implements scholar_converter // {{{
{
    public function convert($token, $contents)
    {
        $tag = preg_replace('/[^-_a-z0-9]/i', '', $token->getAttribute('__tag'));

        if (empty($tag)) {
            return $contents;
        }

        $attribs = $token->getAttributes();
        unset($attribs['__tag']);

        $output = '<' . $tag;
        foreach ($attribs as $key => $value) {
            $output .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        $output .= '>' . $contents . '</' . $tag . '>';

        return $output;
    }
} // }}}


// vim: fdm=marker
