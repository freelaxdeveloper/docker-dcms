<?php

include_once '../sys/inc/start.php';
$doc = new document(5);
$doc->title = __('Информация о системе');

$check = new check_sys();

$listing = new listing();

$post = $listing->post();
$post -> icon('info');
$post -> title = __('Версия DCMS: %s', $dcms->version);

foreach ($check->oks as $ok) {    
    $post = $listing->post();
    $post -> icon('checked');
    $post -> title = $ok;
}
foreach ($check->notices as $note) {
    $post = $listing->post();
    $post -> icon('notice');
    $post -> title = $note;
    $post -> hightlight = true;    
}
foreach ($check->errors as $err) {
    $post = $listing->post();
    $post -> icon('error');
    $post -> title = $err;
    $post -> hightlight = true;  
}
$listing ->display();

$doc->ret(__('Админка'), '/dpanel/');
?>
