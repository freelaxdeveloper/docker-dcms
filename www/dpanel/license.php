<?php

include_once '../sys/inc/start.php';
$doc = new document(groups::max());
$doc->title = __('Лицензионное соглашение');
$doc->ret(__('Админка'), './');

$bb = new bb(H . '/sys/docs/license.txt');
if ($bb->title)
    $doc->title = $bb->title;

$listing = new listing();

$post = $listing->post();
$post->content[] = $bb->getText();
$listing->display();
?>
