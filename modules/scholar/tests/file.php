<?php

require_once '../scholar.module.php';
header('Content-Type: text/html; charset=utf-8');
$tests = array(
    'a.'  => false,
    '.b'  => false,
    '. ą' => false,
    ' . ' => false,
    'a . ' => false,
    'a . b' => 'a.b',
    ' Żółć. b情報 ' => 'Zolc.b',
    'セーラームーン 5.jpg' => '5.jpg',
);

var_dump(preg_replace('/[^\pL\pN\pP\pS\pZ]/u', '?', 'Żółćセーラームーン'));

$str = 'Zażółć gęślą jaźń; Herrens bön, även Fader vår eller Vår Fader. ĚØŘ! 美少女戦士セーラームーン';
var_dump(scholar_ascii($str) === 'Zazolc gesla jazn; Herrens bon, aven Fader var eller Var Fader. EOR! ');

$i = 0;
foreach ($tests as $key => $value) {
    printf("%4d ", ++$i);
    if (scholar_sanitize_filename($key) !== $value) {
        echo 'FAILED';
    } else {
        echo 'OK';
    }
    echo "\n";
}
