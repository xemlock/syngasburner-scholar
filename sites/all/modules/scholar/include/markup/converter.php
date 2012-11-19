<?php

/**
 * @param Zend_Markup_Token $token
 * @param string $contents
 * @return string
 */
function scholar_markup_converter(Zend_Markup_Token $token, $contents) // {{{
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
        'sub' => array(
            'start' => '<sub>',
            'end'   => '</sub>',
        ),
        'sup' => array(
            'start' => '<sup>',
            'end'   => '</sup>',
        ),
        'br' => array(
            'start' => '<br />',
        ),
        'li' => array(
            'start' => '<li>',
            'end'   => '</li>',
            'trim'  => true,
        ),
        '*' => array(
            'start' => '<li>',
            'end'   => '</li>',
            'trim'  => true,
        ),
    );

    // tagi BR i HR maja spacje przed ukosnikiem zamykajacym, poniewaz bez niej
    // drupalowy HTML corrector zamiast poprawiac, tworzy niepoprawny dokument,
    // patrz: http://drupal.org/node/787530
    if (isset($markup[$tagName])) {
        if (empty($markup[$tagName]['end'])) {
            return $markup[$tagName]['start'];
        }
        if (isset($markup[$tagName]['trim']) && $markup[$tagName]['trim']) {
            $contents = trim($contents);
        }
        return $markup[$tagName]['start'] . $contents . $markup[$tagName]['end'];
    }

    $callback = 'scholar_markup_converter_' . preg_replace('/[^_a-z0-9]/i', '_', $tagName);

    if (function_exists($callback)) {
        return call_user_func($callback, $token, $contents);
    }

    return $contents;
} // }}}

// standardowe tagi

function scholar_markup_converter_code(Zend_Markup_Token $token, $contents) // {{{
{
    $code = $token->getAttribute('code');
    $code = preg_replace('/[^_a-z0-9]/i', '', $code);

    $contents = htmlspecialchars($contents);
    $contents = nl2br($contents);

    $output = '<code' . ($code ? ' class="' . $code . '"' : '') . '>' . $contents . '</code>';
    $inline = scholar_parse_bool($token->getAttribute('inline'));

    return !$inline ? '<pre>' . $output . '</pre>' : $output;
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
    // [img width={width} height={height} align={left|right} title={title}]{url}[/img]
    $width  = max(0, $token->getAttribute('width'));
    $height = max(0, $token->getAttribute('height'));

    $title = trim($token->getAttribute('title'));

    // niepoprawny URL, nie tworz tagu obrazu
    $url = scholar_validate_url($contents);
    if (!$url) {
        return '';
    }

    $attrs = array(
        'src'   => $url,
        'alt'   => $title,
    );

    if ($width) {
        $attrs['width'] = $width;
    }

    if ($height) {
        $attrs['height'] = $height;
    }

    if (strlen($title)) {
        $attrs['title'] = $title;
    }

    // atrybut align jest przestarzaly w HTML 5
    switch (strtolower($token->getAttribute('align'))) {
        case 'left':
            $attrs['style'] = 'float:left';
            break;

        case 'right':
            $attrs['style'] = 'float:right';
            break;
    }

    // tag IMG ma spacje przed zamknieciem, poniewaz bez niej drupalowy
    // HTML corrector zamiast poprawiac, generuje niepoprawny dokument,
    // patrz: http://drupal.org/node/787530
    return '<img' . drupal_attributes($attrs) . ' />';
} // }}}

function scholar_markup_converter_list(Zend_Markup_Token $token, $contents) // {{{
{
    $contents = trim($contents);

    if ($contents) {
        // assume contents contain properly formed HTML tags (not necessarily
        // semantically valid). Make sure list contents are LI tags only

        // if contents don't start with an LI tag, add one to the beginning
        if (!preg_match('/^<li[\s>]/i', $contents)) {
            $contents = '<li>' . $contents;
        }

        // build list contents containing only LI tags
        // replace LI close tags with spaces, to avoid merging with text
        // contents not wrapped with LI tag.
        $contents = str_ireplace('</li>', ' ', $contents);
        $contents = preg_split('/<li(?=[\s>])/', $contents, -1, PREG_SPLIT_NO_EMPTY);
        $contents = array_map('trim', $contents);
        $contents = '<li' . implode('</li><li', $contents) . '</li>';

        $type = $token->getAttribute('list');

        if (strlen($type)) {
            // decimal numbers, letters or roman numbers
            if (in_array($type, array('1', 'A', 'a', 'I', 'i'))) {
                $attrs = array(
                    'type' => $type,
                );

                $start = (int) $token->getAttribute('start');
                if ($start) {
                    $attrs['start'] = $start;
                }

                return '<ol' . drupal_attributes($attrs) . '>' . $contents . '</ol>';
            }

            // decimal numbers, start numbering from number given as type value
            if (ctype_digit($type)) {
                return '<ol start="' . $type . '">' . $contents . '</ol>';
            }
        }
        return '<ul>' . $contents . '</ul>';
    }

    return '';
} // }}}

function scholar_markup_converter_url(Zend_Markup_Token $token, $contents) // {{{
{
    $url = $token->getAttribute('url');

    if (empty($url)) {
        $url = $contents;
    }

    $url = scholar_validate_url($url);

    if ($url) {
        $attrs = array(
            'href' => $url,
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

// Tag nonl2br był propozycją do vBulletin 4, niestety zignorowaną.
// https://www.vbulletin.com/forum/archive/index.php/t-197474.html
function scholar_markup_converter_nonl2br(Zend_Markup_Token $token, $contents) // {{{
{
    return str_replace(array("\r\n", "\n", "\r"), ' ', $contents);
} // }}}

// tagi scholara

// formatuje dane wedlug aktualnych ustawien formatu
function scholar_markup_converter_date(Zend_Markup_Token $token, $contents) // {{{
{
    return scholar_format_date($contents, scholar_markup_converter___language());
} // }}}

function scholar_markup_converter_preface($token = null, $contents = null, $first = false) // {{{
{
    static $prefaces = array();

    if (null === $token) {
        $preface = trim(implode('', $prefaces));
        return strlen($preface)
            ? '<div class="scholar-preface">' . nl2br($preface) . '</div>'
            : '';
    }

    if ($first) {
        array_unshift($prefaces, $contents);

    } else {
        $prefaces[] = $contents;
    }
} // }}}

/**
 * <code>[collapsible title="Tytuł bloku"]Treść[/collapsible]</code>
 * <code>[collapsible title="Tytuł bloku" collapsed="no"]Treść[/collapsible]</code>
 * <code>[collapsible="no" title="Tytuł bloku" collapsed="no"]Treść[/collapsible]</code>
 * atrybuty collapsible i collapsed mogą przyjąć następujące wartości logiczne: 0, no, false
 * 1, yes, true.
 * Tytuł musi być niepusty, aby zwijanie działało.
 */
function scholar_markup_converter_collapsible(Zend_Markup_Token $token, $contents) // {{{
{
    $collapsible = scholar_parse_bool($token->getAttribute('collapsible'));

    if (null === $collapsible) {
        // domyslnie blok jest zwijalny
        $collapsible = true;
    }

    $collapsed = scholar_parse_bool($token->getAttribute('collapsed'));

    if (null === $collapsed) {
        // domyslnie blok jest rozwiniety
        $collapsed = false;
    }

    $class = 'scholar-collapsible';
    $title = trim(str_replace("''", '"', $token->getAttribute('title')));

    if (!strlen($title)) {
        // jezeli tytul jest pusty, nie zezwalaj na zwijanie, w przeciwnym razie
        // zwinientego kontentu nie bedzie mozna rozwinac
        $collapsible = false;
    }

    if ($collapsible) {
        if ($collapsed) {
            $class .= ' scholar-collapsible-collapsed';
        }
    } else {
        $class .= ' scholar-collapsible-disabled';
    }

    return '<div class="' . $class . '">'
         . '<h3 class="scholar-collapsible-heading">' . htmlspecialchars($title) . '</h3>'
         . '<div class="scholar-collapsible-content">' . trim($contents) . '</div>'
         . '</div>';
} // }}}

function scholar_markup_converter_section(Zend_Markup_Token $token, $contents) // {{{
{
    return '<h2 class="scholar-section">' . $contents . '</h2>';
} // }}}

function scholar_markup_converter_subsection(Zend_Markup_Token $token, $contents) // {{{
{
    return '<h3 class="scholar-subsection">' . $contents . '</h3>';
} // }}}

function scholar_markup_converter_entry(Zend_Markup_Token $token, $contents) // {{{
{
    $language = scholar_markup_converter___language();
    $output = '';

    if ($date = $token->getAttribute('date')) {
        if ($formatted_date = scholar_format_date($date, $language)) {
            $output .= '<div class="scholar-entry-heading"><span class="tm">' . $formatted_date . '</span></div>';
        }
    } else if ($entry = $token->getAttribute('entry')) {
        $entry = str_replace(array('--', '...'), array('&ndash;', '&hellip;'), trim($entry));
        $output .= '<div class="scholar-entry-heading">' . $entry . '</div>';
    }

    $output .= '<div class="scholar-entry-content">' . trim($contents) . '</div>';

    return '<div class="scholar-entry">' . $output . '</div>';
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
        'href' => $asset,
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

// Link do węzła o podanym identyfikatorze.
// W przypadku odwoływania się do węzłów poprzez identyfikatory rekordów
// z innych tabel, język węzła musi pokrywać się z językiem w aktualnym
// kontekście renderowania.
// [node]25[/node]
// [node]person.1[/node]
// [node="person.1"]Kierownik projektu[/node]
function scholar_markup_converter_node(Zend_Markup_Token $token, $contents) // {{{
{
    $language = scholar_markup_converter___language();
    $node     = trim($token->getAttribute('node'));
    $contents = trim($contents);

    // jezeli atrybut node jest pusty, uzyj identyfikatora z tresci,
    // wyczysc tresc, zeby pozniej nie zostala wzieta jako tekst hiperlacza
    if (!strlen($node)) {
        $node     = $contents;
        $contents = '';
    }

    $parts = explode('.', $node);
    $link  = false;

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

/**
 * [gallery-img]url[/gallery-img]
 * Atrybuty:
 * title, width, height, align=left|right, lightbox (pusty lub z wartoscia id galerii)
 */
function scholar_markup_converter_gallery_img(Zend_Markup_Token $token, $contents) // {{{
{
    if (strlen($contents) && ctype_digit($contents)) {
        $width  = max(0, $token->getAttribute('width'));
        $height = max(0, $token->getAttribute('height'));
        $image  = scholar_gallery_image($contents, $width, $height);

        if ($image) {
            $title = trim($token->getAttribute('title'));

            // jezeli nie podano tytulu uzyj tytulu obrazu z bazy danych
            if (!strlen($title)) {
                $title = trim($image['title']);
            }

            $img = array(
                'src' => $image['thumb_url'],
                'alt' => $title, // (X)HTML: atrybut alt musi byc obecny
            );

            if (strlen($title)) {
                $img['title'] = $title;
            }

            if ($width) {
                $img['width'] = $width;
            }

            if ($height) {
                $img['height'] = $height;
            }

            $a = array(
                'href' => $image['image_url'],
            );

            switch (strtolower($token->getAttribute('align'))) {
                case 'left':
                    $a['style'] = 'float:left';
                    break;

                case 'right':
                    $a['style'] = 'float:right';
                    break;
            }

            if ($token->hasAttribute('lightbox')) {
                $lightbox = trim($token->getAttribute('lightbox'));

                $a['rel'] = strlen($lightbox)
                    ? 'lightbox[' . $lightbox . ']'
                    : 'lightbox';

                // dodaj atrybut title do linku, zostanie on uzyty jako tytul obrazu
                if ($title) {
                    $a['title'] = $title;
                }

                // dodaj dodatkowy opis obrazka
                $description = trim($image['description']);
                if ($description) {
                    $a['data-description'] = $description;
                }
            }

            // tag IMG ma spacje przed zamknieciem, poniewaz bez niej drupalowy
            // HTML corrector zamiast poprawiac, generuje niepoprawny dokument,
            // patrz: http://drupal.org/node/787530
            return '<a' . drupal_attributes($a) . '><img' . drupal_attributes($img) . ' /></a>';
        }
    }
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
function scholar_markup_converter___language($token = null, $contents = null) // {{{
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

/**
 * Obraz umieszczany w preambule strony.
 */
function scholar_markup_converter___image(Zend_Markup_Token $token, $contents) // {{{
{
    // trzeba pobrac szerokosc obrazu z ustawien
    $token->addAttribute('width', scholar_setting('image_width'));
    $token->addAttribute('height', null);
    $token->addAttribute('lightbox', scholar_setting('image_lightbox') ? '' : null);

    $output = scholar_markup_converter_gallery_img($token, $contents);

    if (strlen($output)) {
        scholar_markup_converter_preface($token, '<div class="scholar-image">' . $output . '</div>', true);
    }
} // }}}

// vim: fdm=marker
