<?php
include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Личное меню');

$menu = new menu('user'); // загружаем пользовательское меню
$menu->display(); // выводим пользовательское меню


?>
