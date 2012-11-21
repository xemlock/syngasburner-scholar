<?php

$data = json_decode(file_get_contents('tags.js'), true);

foreach ($data as $section => $tags) {
    echo "<h3>" . $section . "</h3>\n\n";
    foreach ($tags as $name => $info) {
        echo "<h4>" . $name . "</h4>\n";
        echo "<dl>\n",
             " <dt>Składnia:</dt>\n",
             " <dd><code>" . $info['syn'] . "</code></dd>\n",
             " <dt>Opis:</dt>\n",
             " <dd>" . $info['desc'] . "</dd>\n";
        if (isset($info['note'])) {
            echo '<p>', $info['note'], "</p>\n";
        }
        echo "</dl>\n";
    }
}
