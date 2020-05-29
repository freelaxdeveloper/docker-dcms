<?php

defined('DCMS') or die;

global $user, $dcms;

$users = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `a_code` = '' AND `reg_date` > '" . NEW_TIME . "'"), 0);

$listing = new listing();

$post = $listing->post();
$post->hightlight = true;
$post->icon('users');
$post->url = '/users.php';
$post->title = __('Последние зарегистрированные');
if ($users)
    $post->counter = '+' . $users;


if ($dcms->widget_items_count) {
    $q = mysql_query("SELECT * FROM `users` WHERE `a_code` = '' AND `reg_date` > '" . NEW_TIME . "' ORDER BY `id` DESC LIMIT " . $dcms->widget_items_count);
    while ($ank = mysql_fetch_assoc($q)) {
        $post = $listing->post();
        $p_user = new user($ank['id']);
        $post->icon($p_user->icon());
        $post->title = $p_user->nick();
        $post->url = '/profile.view.php?id=' . $p_user->id;
        $post->time = vremja($p_user->reg_date);
    }
}

$post = $listing->post();
$post->hightlight = true;
$post->icon('users');
$post->title = __('Сейчас на сайте');
$post->url = '/online.users.php';
$post->counter = mysql_result(mysql_query("SELECT COUNT(*) FROM `users_online`"), 0);


$post = $listing->post();
$post->hightlight = true;
$post->icon('guest');
$post->title = __('Гости на сайте');
$post->url = '/online.guest.php';
$post->counter = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest_online` WHERE `conversions` >= '5'"), 0);


$listing->display();
?>