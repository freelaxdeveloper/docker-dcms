<?php

defined('DCMS') or die;
global $user;


$dir = new files(FILES . '/.downloads');
$content = $dir->getNewFiles();
$files = &$content['files'];
$new_files = count($files);

$listing = new listing();

$post = $listing->post();
$post->hightlight = true;
$post->icon('downloads');
$post->url = '/files/.downloads/';
$post->title = __('Загрузки');
if ($new_files)
    $post->counter = '+' . $new_files;

for ($i = 0; $i < $new_files && $i < $dcms->widget_items_count; $i++) {
    $ank = new user($files[$i]->id_user);
    $post = $listing->post();
    $post->title = for_value($files[$i]->runame);
    $post->time = vremja($files[$i]->time_add);
    $post->url = "/files" . $files[$i]->getPath() . ".htm";
    $post->image = $files[$i]->image();
    $post->icon($files[$i]->icon());
    if ($ank->id)
        $post->bottom = $ank->nick();
    
}

if ($new_files > $dcms->widget_items_count) {
    $post = $listing->post();
    $post->icon('new');
    $post->url = '/files/new.php?dir=.downloads';
    $post->title = __('Все новые файлы');
}

$listing->display();
?>
