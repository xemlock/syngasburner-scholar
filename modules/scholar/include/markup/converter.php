<?php

/**
 * @param Zend_Markup_Token $token
 * @param string $contents
 * @return string
 */
function scholar_markup_converter(Zend_Markup_Token $token, $contents)
{
    $tagName = strtolower($token->getName());

    // lista tagow, ktore nie przyjmuja parametrow
    $markup = array(
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
        'br' => array(
            'start' => '<br/>',
        ),
        'rule' => array(
            'start' => '<hr/>',
        ),
        'item' => array(
            'start' => '<li>',
            'end'   => '</li>',
        ),
        '*' => array(
            'start' => '<li>',
            'end'   => '</li>',
        ),
    );

    if (isset($markup[$tagName])) {
        if (empty($markup[$tagName]['end'])) {
            return $markup[$tagName];
        }
        return $markup[$tagName]['start'] . $contents . $markup[$tagName]['end'];
    }

    $callback = 'scholar_markup_converter_' . $tagName;

    if (function_exists($callback)) {
        return call_user_func($callback, $token, $contents);
    }

    return $contents;
}

// standardowe tagi

function scholar_markup_converter_code(Zend_Markup_Token $token, $contents) // {{{
{
    $code = $token->getAttribute('code');
    $code = preg_replace('/[^_a-z0-9]/i', '', $code);

    $contents = htmlspecialchars($contents);
    $contents = nl2br($contents);

    return '<pre><code' . ($code ? ' class="' . $code . '"' : '') . '>'
         . $contents . '</code></pre>';
} // }}}

function scholar_markup_converter_color(Zend_Markup_Token $token, $contents) // {{{
{
    $color = $token->getAttribute('color');
    $color = scholar_validate_color($color);

    if ($color) {
        return '<span style="color:' . $color . '">' . $contents . '</span>';
    }

    return $contents;
} // }}}

function scholar_markup_converter_img(Zend_Markup_Token $token, $contents) // {{{
{
    // [img]{url}[/img]
    // [img width={width} height={height}]{url}[/img]
    // align=left|right

    $width  = intval($token->getAttribute('width'));
    $height = intval($token->getAttribute('height'));

    if ($width <= 0 || $height <= 0) {
        $width  = 0;
        $height = 0;
    }

    $attrs = array(
        'src' => $contents,
        'alt' => $token->getAttribute('alt'),
    );

    // atrybut align jest przestarzaly w HTML 5. Trudno.
    switch (strtolower($token->getAttribute('align'))) {
        case 'left':
            $attrs['style'] = 'float:left';
            break;

        case 'right':
            $attrs['style'] = 'float:right';
            break;
    }

    if ($width && $height) {
        $attrs['width'] = $widht;
        $attrs['height'] = $height;
    }

    return '<img' . drupal_attributes($attrs) . '/>';
} // }}}

function scholar_markup_converter_list(Zend_Markup_Token $token, $contents) // {{{
{
    $contents = trim($contents);

    // make sure list contents are LI tags only
    if (preg_match('/^<li[ >]/i', $contents) && preg_match('/<\/li>$/i', $contents)) {
        $type = $token->getAttribute('list');
        if (strlen($type)) {
            if (ctype_digit($type)) {
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

function scholar_markup_converter_url(Zend_Markup_Token $token, $contents) // {{{
{
    $url = $token->getAttribute('url');

    if (empty($url)) {
        $url = $contents;
    }

    if (false === strpos($url, '://')) {
        // wzgledny URL
        $url = valid_url($url, false) ? $url : false;
    } else {
        // absolutny URL
        $url = valid_url($url, true) ? $url : false;
    }

    if ($url) {
        if ('_self' == $token->getAttribute('target')) {
            $target = '_self';
        } else {
            $target = '_blank';
        }

        $attrs = array(
            'href' => $url,
            'target' => $target,
        );

        return '<a' . drupal_attributes($attrs) . '>' . $contents . '</a>';
    }

    return $contents;
} // }}}

function scholar_markup_converter_youtube(Zend_Markup_Token $token, $contents) // {{{
{
    // [youtube]video_id[/youtube]

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
} // }}}

function scholar_markup_converter_size(Zend_Markup_Token $token, $contents) // {{{
{
    $size = $token->getAttribute('size');

    // rozmiar czcionki tylko w procentach
    if (preg_match('/^\d+%$/', $size)) {
        return '<span style="font-size:' . $size . '">' . $contents . '</span>';
    }

    return $contents;
} // }}}

// tagi scholara

function scholar_markup_converter_preface(Zend_Markup_Token $token = null, $contents = null) // {{{
{
    static $prefaces = array();

    if (null === $token) {
        return implode('', $prefaces);
    }

    $prefaces[] = $contents;
} // }}}

function scholar_markup_converter_chapter(Zend_Markup_Token $token, $contents) // {{{
{
    return '<div class="scholar-chapter"><h1>' . str_replace("''", '"', $token->getAttribute('chapter')) . '</h1>' . trim($contents) . '</div>';
} // }}}

function scholar_markup_converter_section(Zend_Markup_Token $token, $contents) // {{{
{
    return '<div class="scholar-section"><h2>' . str_replace("''", '"', $token->getAttribute('section')) . '</h2>' . trim($contents) . '</div>';
} // }}}

function scholar_markup_converter_block(Zend_Markup_Token $token, $contents) // {{{
{
    if ($date = $token->getAttribute('date')) {
    $result = '';
    if ($date) {
        $date = explode(' - ', $date);
        $date = str_replace('--', ' &ndash; ', $date);
        $result .= '<div class="tm">' . trim($date) . '</div>';
    }
    } else if ($time = $token->getAttribute('time')) {
        $time = explode(' - ', $time);
    } else {
        
    }

    $result .= '<div class="details">' . trim($contents) . '</div>';
    return '<div class="scholar-block">' . $result . '</div>';
} // }}}

function scholar_markup_converter_box(Zend_Markup_Token $token, $contents) // {{{
{
    return '<div>' . trim($contents) . '</div>';
} // }}}

function scholar_markup_converter_asset(Zend_Markup_Token $token, $contents) // {{{
{
    // [asset={url} details={details}]{label}[/asset]
    // [asset]{url}[/asset]
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
} // }}}

function scholar_markup_converter_t(Zend_Markup_Token $token, $contents) // {{{
{
    $language = scholar_markup_converter___language();
    return t($contents, array(), $language);
}  // }}}

function scholar_markup_converter_node(Zend_Markup_Token $token, $contents) // {{{
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
} // }}}

// wewnetrzne nieudokumentowane konwertery

function scholar_markup_converter___tag(Zend_Markup_Token $token, $contents) // {{{
{
    $tag = preg_replace('/[^-_a-z0-9]/i', '', $token->getAttribute('__tag'));

    if (empty($tag)) {
        return $contents;
    }

    $attrs = $token->getAttributes();
    unset($attrs['__tag']);

    return '<' . $tag . drupal_attributes($attrs) . '>' . $contents . '</' . $tag . '>';
} // }}}

/**
 * Konwerter przechowujący / ustawiający wartość języka w przetwarzanym
 * dokumencie. Niektóre tagi mogą korzystać z udostępnianej przez niego
 * funkcjonalności, np. {@see scholar_markup_converter_t}.
 */
function scholar_markup_converter___language(Zend_Markup_Token $token = null, $contents = null) // {{{
{
    static $language = null;

    // uzyj biezacego jezyka jako domyslnego
    if (null === $language) {
        $language = scholar_language();
    }

    // zwraca aktualnie ustawiony jezyk, jezeli nie podano tokena
    if (null === $token) {
        return $language;
    }

    // ustawia nowy jezyk
    $language = (string) $token->getAttribute('__language');
} // }}}

// vim: fdm=marker
