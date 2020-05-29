<?php 
include_once '../sys/inc/start.php';
$doc = new document ();
$doc->title = __('Фотоальбомы');

// папка фотоальбомов пользователей
$photos = new files ( FILES . '/.photos' );



?>