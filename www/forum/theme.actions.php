<?php

include_once '../sys/inc/start.php';
$doc = new document(2);
$doc->title = __('Форум');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора темы'));
    exit;
}
$id_theme = (int) $_GET['id'];
$q = mysql_query("SELECT `forum_themes`.* ,
        `forum_categories`.`name` AS `category_name` ,
        `forum_topics`.`name` AS `topic_name`,
        `forum_topics`.`group_write` AS `topic_group_write`
FROM `forum_themes`
LEFT JOIN `forum_categories` ON `forum_categories`.`id` = `forum_themes`.`id_category`
LEFT JOIN `forum_topics` ON `forum_topics`.`id` = `forum_themes`.`id_topic`
WHERE `forum_themes`.`id` = '$id_theme' AND `forum_themes`.`group_show` <= '$user->group' AND `forum_topics`.`group_show` <= '$user->group' AND `forum_categories`.`group_show` <= '$user->group'");
if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Тема не доступна'));
    exit;
}
$theme = mysql_fetch_assoc($q);


$doc->title = __('Тема %s - действия', $theme['name']);


$listing = new listing();

if ($theme['group_edit'] <= $user->group) {
    $post = $listing->post();
    $post->url = 'theme.status.php?id=' . $theme['id'];
    $post->title = $theme['group_write'] > $theme['topic_group_write'] ? __('Открыть тему') : __('Закрыть тему');
    $post->icon($theme['group_write'] > $theme['topic_group_write'] ? 'lock' : 'unlock');
}


if ($theme['group_edit'] <= $user->group) {
    $post = $listing->post();
    $post->url = 'theme.rename.php?id=' . $theme['id'];
    $post->title = __('Переименовать');
    $post->icon('rename');
}

if ($theme['group_edit'] <= $user->group) {
    $post = $listing->post();
    $post->url = 'theme.move.php?id=' . $theme['id'];
    $post->title = __('Переместить');
    $post->icon('move');
}

if ($theme['group_edit'] <= $user->group) {
    $post = $listing->post();
    $post->url = 'theme.security.php?id=' . $theme['id'];
    $post->title = __('Разрешения');
    $post->icon('security');
}


if (!$theme['id_vote'] && $theme['group_write'] <= $user->group && $user->group >= 2) {
    $post = $listing->post();
    $post->url = 'vote.new.php?id_theme=' . $theme['id'];
    $post->title = __('Создать голосование');
    $post->icon('create');
}
if ($theme['id_vote'] && $theme['group_write'] <= $user->group && $user->group >= 2) {
    $post = $listing->post();
    $post->url = 'vote.edit.php?id_theme=' . $theme['id'];
    $post->title = __('Изменить голосование');
    $post->icon('vote');
}


if ($theme['group_edit'] <= $user->group && $user->group >= 2) {
    $post = $listing->post();
    $post->url = 'theme.posts.delete.php?id=' . $theme['id'];
    $post->title = __('Удаление сообщений');
    $post->icon('delete');
}
if ($theme['group_edit'] <= $user->group && $user->group >= 2) {
    $post = $listing->post();
    $post->url = 'theme.delete.php?id=' . $theme['id'];
    $post->title = __('Удаление темы');
    $post->icon('delete');
}



$listing->display();


$doc->ret(__('Вернуться в тему'), 'theme.php?id=' . $theme['id']);
$doc->ret($theme['topic_name'], 'topic.php?id=' . $theme['id_topic']);
$doc->ret($theme['category_name'], 'category.php?id=' . $theme['id_category']);
$doc->ret(__('Форум'), './');
?>
