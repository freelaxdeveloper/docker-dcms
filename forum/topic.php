<?php

include_once '../sys/inc/start.php';
$doc = new document();

$doc->title = __('Форум');
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора раздела'));

    exit;
}
$id_top = (int) $_GET['id'];

$q = mysql_query("SELECT `forum_topics`.*, `forum_categories`.`name` AS `category_name` FROM `forum_topics`
LEFT JOIN `forum_categories` ON `forum_categories`.`id` = `forum_topics`.`id_category`
WHERE `forum_topics`.`id` = '$id_top' AND `forum_topics`.`group_show` <= '$user->group' AND `forum_categories`.`group_show` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Раздел не доступен'));

    exit;
}

$topic = mysql_fetch_assoc($q);


$doc->title .= ' - ' . $topic['name'];

$posts = array();
$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_themes` WHERE `id_topic` = '$topic[id]' AND `group_show` <= '$user->group'"), 0); // количество категорий форума
$pages->this_page(); // получаем текущую страницу

$q = mysql_query("SELECT `forum_themes`.* ,
        COUNT(`forum_messages`.`id`) AS `count`,
        (SELECT COUNT(`fv`.`id_user`) FROM `forum_views` AS `fv` WHERE `fv`.`id_theme` = `forum_themes`.`id`)  AS `views`
        FROM `forum_themes`
LEFT JOIN `forum_messages` ON `forum_messages`.`id_theme` = `forum_themes`.`id`

 WHERE `forum_themes`.`id_topic` = '$topic[id]' AND `forum_themes`.`group_show` <= '$user->group' AND `forum_messages`.`group_show` <= '$user->group'
 GROUP BY `forum_themes`.`id`
 ORDER BY `forum_themes`.`top`, `forum_themes`.`time_last` DESC LIMIT $pages->limit");

$listing = new listing();

while ($themes = mysql_fetch_assoc($q)) {
    $post = $listing->post();

    $is_open = (int) ($themes['group_write'] <= $topic['group_write']);

    $post->icon("forum.theme.{$themes['top']}.$is_open");
    $post->title = for_value($themes['name']);
    $post->url = 'theme.php?id=' . $themes['id'];
    $post->counter = $themes['count'];
    $post->time = vremja($themes['time_last']);


    $autor = new user($themes['id_autor']);
    $last_msg = new user($themes['id_last']);

    $post->content = ($autor->id != $last_msg->id ? $autor->nick . '/' . $last_msg->nick : $autor->nick) . '<br />';
    $post->content .= __('Просмотров: %s', $themes['views']);
}


$listing->display(__('Доступных Вам тем нет'));

$pages->display('topic.php?id=' . $topic['id'] . '&amp;'); // вывод страниц

if ($topic['group_write'] <= $user->group) {
    $doc->act(__('Начать новую тему'), 'theme.new.php?id_topic=' . $topic['id'] . "&amp;return=" . URL);
}

if ($topic['group_edit'] <= $user->group) {
    $doc->act(__('Параметры раздела'), 'topic.edit.php?id=' . $topic['id'] . "&amp;return=" . URL);
}

$doc->ret($topic['category_name'], 'category.php?id=' . $topic['id_category']);
$doc->ret(__('Форум'), './');
?>
