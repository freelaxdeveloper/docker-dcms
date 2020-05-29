<?php

include_once '../sys/inc/start.php';
$doc = new document(groups::max());
$doc->title = __('Сборка dcms.js');
$doc->ret(__('Админка'), './');


if (isset($_POST['build'])) {
    $jsbuild = new js_assembly(H . '/sys/javascript/sources/');
    $jsbuild->buildTo(H . '/sys/javascript/dcms.js');
    unset($jsbuild);
}

$listing = new listing();
$post = $listing->post();
$post->icon('info');
$post->title = __('Информация');
$post->content[] = 'Файл dcms.js - это результат объединения js файлов в папке sys/javascript/sources';
$post->content[] = 'Путь к файлу для подключения в теме оформления: /sys/javascript/build/dcms.js';

$post = $listing->post();
$post->icon('js');
$post->title = __('Последний раз собирался:');
$time = filemtime(H . '/sys/javascript/dcms.js');
$post->content[] = $time ? misc::vremja($time) : __('Еще не собирался');
$listing->display();



$form = new form();
$form->button(__('Собрать'), 'build');
$form->display();
?>
