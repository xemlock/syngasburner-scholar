<?php

interface scholar_converter // {{{
{
    public function convert(Zend_Markup_Token $token, $contents);
} // }}}

class scholar_converter_preface implements scholar_converter // {{{
{
    protected $_prefaces = array();

    public function convert(Zend_Markup_Token $token, $contents)
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
    public function convert(Zend_Markup_Token $token, $contents)
    {
        return '<div class="scholar-chapter"><h1>' . scholar_renderer::getTokenAttribute($token) . '</h1>' . $contents . '</div>';
    }
} // }}}

class scholar_converter_section implements scholar_converter // {{{
{
    public function convert(Zend_Markup_Token $token, $contents)
    {
        return '<div class="scholar-section"><h2>' . scholar_renderer::getTokenAttribute($token) . '</h2>' . $contents . '</div>';
    }
} // }}}

class scholar_converter_block implements scholar_converter // {{{
{
    public function convert(Zend_Markup_Token $token, $contents)
    {
        $date = scholar_renderer::getTokenAttribute($token);
        $result = '';
        if ($date) {
            $date = str_replace('--', ' &ndash; ', $date);
            $result .= '<div class="tm">' . trim($date) . '</div>';
        }
        $result .= '<div class="details">' . trim($contents) . '</div>';
        return '<div class="entry">' . $result . '</div>';
    }
} // }}}

class scholar_converter_box implements scholar_converter // {{{
{
    public function convert(Zend_Markup_Token $token, $contents)
    {
        return '<div>' . $contents . '</div>';
    }
} // }}}

class scholar_converter_res implements scholar_converter // {{{
{
    public function convert(Zend_Markup_Token $token, $contents)
    {
        return '';
    }
} // }}}

// vim: fdm=marker
