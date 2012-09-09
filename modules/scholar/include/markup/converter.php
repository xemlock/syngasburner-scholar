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
            $width  = min(640, max(0, $token->getAttribute('width')));
            $height = min(360, max(0, $token->getAttribute('height')));

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

class scholar_markup_converter_t implements scholar_markup_converter // {{{
{
    public function convert($token, $contents)
    {
        $language = scholar_markup_converter___language::getLanguage();
        return t($contents, array(), $language);
    }
}  // }}}

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

/**
 * Konwerter przechowujący / ustawiający wartość języka w przetwarzanym
 * dokumencie. Niektóre tagi mogą korzystać z udostępnianej przez niego
 * funkcjonalności, np. {@see scholar_markup_converter_t}.
 */
class scholar_markup_converter___language implements scholar_markup_converter // {{{
{
    protected static $_language;

    public static function getLanguage()
    {
        return self::$_language;
    }

    public function __construct()
    {
        global $language;
        self::$_language = $language->language;
    }

    /**
     * Ustawia aktualny język na ten podany w głównym atrybucie tagu.
     */
    public function convert($token, $contents)
    {
        self::$_language = (string) $token->getAttribute('__language');
    }
} // }}}

class scholar_markup_converter_node implements scholar_markup_converter
{
    public function convert($token, $contents)
    {
        $language = scholar_markup_converter___language::getLanguage();
        $contents = trim($contents);

        $parts = explode('.', $token->getAttribute('node'));
        $link  = false;

        // [node="person.1"][/node]
        // [node="person.1"]Kierownik projektu[/node]
        // [node="25"][/node]

        // jezeli link nie zostal znaleziony zostaje wyrenderowany
        // <del title="Broken link">$contents</del>
        if (count($parts) > 1) {
            $table   = null;
            $subtype = null;

            // wyznacz nazwe tabeli w bazie danych
            switch ($parts[0]) {
                case 'category':
                    $table = 'categories';
                    break;

                case 'person':
                    $table = 'people';
                    break;

                case 'article':
                case 'class':
                case 'conference':
                case 'journal':
                case 'presentation':
                case 'training':
                    $table   = 'generics';
                    $subtype = $parts[0];
                    break;
            }
            if ($table) {
                $link = scholar_node_link($parts[1], $table, $language);
            }

        } else {
            $link = scholar_node_link($parts[0]);
        }

        return $link
            ? l(strlen($contents) ? $contents : $link['title'], $link['path'], array('absolute' => true))
            : ('<del title="' . t('Broken link', array(), $language) . '">' . $contents . '</del>');
    }
}

// vim: fdm=marker
