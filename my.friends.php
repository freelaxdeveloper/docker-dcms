<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Мои друзья');

$user->friend_new_count = mysql_result(mysql_query("SELECT COUNT(*) FROM `friends` WHERE `id_user` = '$user->id' AND `confirm` = '0'"), 0);

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `friends` WHERE `id_user` = '$user->id'"), 0);
$pages->this_page();

$q = mysql_query("SELECT * FROM `friends` WHERE `id_user` = '$user->id' ORDER BY `confirm` ASC, `time` DESC LIMIT $pages->limit");

$listing = new listing();
while ($friend = mysql_fetch_assoc($q)) {
    $post = $listing -> post();
    $ank = new user($friend['id_friend']);
    $post -> url = '/profile.view.php?id=' . $ank->id;
    $post -> title = $ank->nick();
    $post -> icon ($ank->icon());
    $post -> hightlight = !$friend['confirm'];    
    $post->content = $friend['confirm'] ? null : __('Хочет быть Вашим другом'); 
}
$listing -> display(__('Друзей нет'));

$pages->display('?');

$doc->ret(__('Личное меню'), '/menu.user.php');
?>
