<?php

interface scholar_markup_converter // {{{
{
    public function convert($token, $contents);
} // }}}

class scholar_markup_converter_youtube implements scholar_markup_converter // {{{
{
    public function convert($token, $contents)
    {
        // Format identyfikatora filmu, na podstawie wiadomosci z listy
        // com.googlegroups.youtube-api-gdata napisana przez YouTube API Guide
        // (yout...@youtube.com), wiecej szczegolow:
        // http://markmail.org/message/jb6nsveqs7hya5la
        //      If you just want to do a quick sanity check, this regex
        //      basically covers the format: [a-zA-Z0-9_-]{11}

        $youtube = $contents;

        if (preg_match('/^[-_a-z0-9]{11}$/i', $youtube)) {
            $width  = max(0, $token->getAttribute('width'));
            $height = max(0, $token->getAttribute('height'));

            if (0 == $width * $height) {
                // domyslna rozdzielczosc 360p
                $width = 640;
                $height = 360;
            }

            $attrs = array(
                'src'    => 'http://www.youtube.com/embed/' . $youtube,
                'width'  => $width,
                'height' => $height,
                'frameborder'     => 0,
                'allowfullscreen' => 1,
            );

            return '<iframe' . drupal_attributes($attrs) . '></iframe>';
        }
    }
} // }}}

class scholar_markup_converter_preface implements scholar_markup_converter // {{{
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

class scholar_markup_converter_chapter implements scholar_markup_converter // {{{
{
    public function convert($token, $contents)
    {
        return '<div class="scholar-chapter"><h1>' . str_replace("''", '"', $token->getAttribute('chapter')) . '</h1>' . trim($contents) . '</div>';
    }
} // }}}

class scholar_markup_converter_section implements scholar_markup_converter // {{{
{
    public function convert($token, $contents)
    {
        return '<div class="scholar-section"><h2>' . str_replace("''", '"', $token->getAttribute('section')) . '</h2>' . trim($contents) . '</div>';
    }
} // }}}

class scholar_markup_converter_block implements scholar_markup_converter // {{{
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

class scholar_markup_converter_box implements scholar_markup_converter // {{{
{
    public function convert($token, $contents)
    {
        return '<div>' . trim($contents) . '</div>';
    }
} // }}}

class scholar_markup_converter_asset implements scholar_markup_converter // {{{
{
    // [asset={url} details={details}]{label}[/asset]
    // [asset]{url}[/asset]
    public function convert($token, $contents)
    {
        $asset   = trim($token->getAttribute('asset'));
        $details = trim($token->getAttribute('details'));

        if (empty($asset)) {
            $asset = $contents;
            $label = basename($contents);
        } else {
            $label = $contents;
        }

        $attrs = array(
            'href'   => $asset,
            'target' => '_blank',
        );

        if (strlen($details)) {
            $attrs['title'] = $details;
        }

        // usun wszystkie potencjalnie niebezpieczne protokoly z adresu
        $contents = check_url($contents);

        return '<span class="scholar-asset">'
             . '<a' . drupal_attributes($attrs) . '>' . check_plain($label) . '</a>'
             . '</span>';
    }
} // }}}

class scholar_markup_converter_node implements scholar_markup_converter
{
    public function convert($token, $contents)
    {
        $node = explode('.', $token->getAttribute('node'));

        
    }
}

// wewnetrzny konwerter nie do dokumentacji
class scholar_markup_converter___tag implements scholar_markup_converter // {{{
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
