<?php

defined('DCMS') or die;

$listing = new listing();
$post = $listing->post();
$post->hightlight = true;
$post->icon('news');
$post->url = '/news/';
$post->title = __('Все новости');

if ($dcms->widget_items_count) {
    $week = mktime(0, 0, 0, date('n'), -7);
    $q = mysql_query("SELECT * FROM `news` WHERE `time` > '$week' ORDER BY `id` DESC LIMIT " . $dcms->widget_items_count);
    while ($news = mysql_fetch_assoc($q)) {
        $post = $listing->post();
        $post->icon('news');
        $post->title = for_value($news['title']);
        $post->url = '/news/comments.php?id=' . $news['id'];
        $post->time = vremja($news['time']);
        $post->hightlight = $news['time'] > NEW_TIME;
    }
}

$listing->display();
?>