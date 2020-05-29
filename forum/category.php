<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Форум');
$doc->ret(__('К категориям'), './');
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора категории'));
    exit;
}
$id_cat = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `forum_categories` WHERE `id` = '$id_cat' AND `group_show` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Категория не доступна'));
    exit;
}

$category = mysql_fetch_assoc($q);

$doc->title .= ' - ' . $category['name'];

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_topics` WHERE `id_category` = '$category[id]' AND `group_show` <= '$user->group'"), 0); // количество категорий форума
$pages->this_page(); // получаем текущую страницу

$q = mysql_query("SELECT * FROM `forum_topics` WHERE `id_category` = '$category[id]' AND `group_show` <= '$user->group' ORDER BY `time_last` DESC LIMIT $pages->limit");

$listing = new listing();
while ($topics = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $post->icon('forum.topic.png');
    $post->title = for_value($topics['name']);
    $post->content = text::for_opis($topics['description']);
    $post->url = "topic.php?id={$topics['id']}";
}
$listing->display(__('Доступных Вам разделов нет'));


$pages->display('?id=' . $id_cat . '&amp;'); // вывод страниц

if ($category['group_write'] <= $user->group) {
    $doc->act(__('Создать раздел'), 'topic.new.php?id_category=' . $category['id'] . "&amp;return=" . URL);
}
if ($category['group_edit'] <= $user->group) {
    $doc->act(__('Параметры категории'), 'category.edit.php?id=' . $category['id'] . "&amp;return=" . URL);
}
?>