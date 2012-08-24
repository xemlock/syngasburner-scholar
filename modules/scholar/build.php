<?php

function get_contents($file)
{
    $contents = file_get_contents($file);
    $contents = str_replace(
        array('<?php', ' // {{{', ' // }}}', '// vim: fdm=marker'),
        '',
        $contents
    );

    return trim($contents);
}

$module = "<?php\n\n" . get_contents('scholar.module.php');
$module = preg_replace('/pages\/[_a-z0-9]+\.php/i', 'scholar.pages.inc', $module);
$module = preg_replace('/\s*__scholar_init\(\);/', '', $module);
$module = str_replace(
    array('js/scholar.js', 'css/scholar.css'),
    array('scholar.js', 'scholar.css'),
    $module
);

foreach (array('include', 'models') as $dir) {
    foreach (glob($dir . '/*.php') as $inc) {
        $module .= "\n\n" . get_contents($inc);
    }
}

$classes = '<?php';
foreach (glob('classes/*.php') as $inc) {
    $classes .= "\n\n" . get_contents($inc);
}

$pages = '<?php';
foreach (glob('pages/*.php') as $inc) {
    $pages .= "\n\n" . get_contents($inc);
}

file_put_contents('build/scholar.module', $module);
file_put_contents('build/scholar.classes.inc', $classes);
file_put_contents('build/scholar.pages.inc', $pages);

$info = trim(file_get_contents('scholar.info')) . "\n\n"
      . 'datestamp = "' . time() . '"' . "\n";

file_put_contents('build/scholar.info', $info);

copy('js/scholar.js',   'build/scholar.js');
copy('css/scholar.css', 'build/scholar.css');
copy('scholar.install.php', 'build/scholar.install');
