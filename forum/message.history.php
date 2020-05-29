<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('История сообщений');
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора сообщения'));
    exit;
}
$id_theme = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `forum_messages` WHERE `id` = '$id_theme' AND `group_show` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Сообщение не доступно'));
    exit;
}

$message = mysql_fetch_assoc($q);
$ank2 = new user($message['id_user']);
if ($message['id_user'] != $user->id && $ank2->group >= $user->group) {
    header('Refresh: 1; url=./');
    $doc->err(__('Нет доступа к данной странице'));
    exit;
}
$listing = new listing();
$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_history` WHERE `id_message` = '$message[id]'"), 0); // количество сообщений  теме
$pages->this_page(); // получаем текущую страницу



$ank = new user($message['id_user']);

$post = $listing->post();
$post->title = $ank->nick();
$post->icon($ank->icon());
$post->content = text::output_text($message['message']);
$post->time = vremja($message['edit_time'] ? $message['edit_time'] : $message['time']);
$post->bottom = __('Текущая версия');

if ($message['edit_id_user']) {
    $post->bottom .= text::output_text(' ([user]' . $message['edit_id_user'] . '[/user])');
}

$q = mysql_query("SELECT * FROM `forum_history` WHERE `id_message` = '$message[id]' ORDER BY `id` DESC LIMIT $pages->limit");

while ($messages = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $ank = new user($message['id_user']);
    $post->title = $ank->nick();
    $post->icon($ank->icon());
    $post->content = text::output_text($messages['message']);
    $post->time = vremja($messages['time']);

    if ($message['id_user'] != $messages['id_user']) {
        $post->bottom = text::output_text('[user]' . $messages['id_user'] . '[/user]');
    }
}
$listing->display(__('Сообщения отсутствуют'));


$pages->display('?id=' . $message['id'] . '&amp;' . (isset($_GET['return']) ? 'return=' . urlencode($_GET['return']) . '&amp;' : null)); // вывод страниц

if (isset($_GET['return']))
    $doc->ret(__('В тему'), for_value($_GET['return']));
else
    $doc->ret(__('В тему'), 'theme.php?id=' . $message['id_theme']);
?>