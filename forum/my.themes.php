<?php

include_once '../sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Мои темы');

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(DISTINCT(`msg`.`id_theme`))
FROM `forum_messages` AS `msg`
LEFT JOIN `forum_themes` AS `th` ON `th`.`id` = `msg`.`id_theme`
LEFT JOIN `forum_topics` AS `tp` ON `tp`.`id` = `th`.`id_topic`
LEFT JOIN `forum_categories` AS `cat` ON `cat`.`id` = `th`.`id_category`
WHERE `th`.`id_autor` = '{$user->id}'
AND `th`.`group_show` <= '{$user->group}'
AND `tp`.`group_show` <= '{$user->group}'
AND `cat`.`group_show` <= '{$user->group}'
AND `msg`.`group_show` <= '{$user->group}'"), 0); // количество категорий форума
$pages->this_page(); // получаем текущую страницу

$q = mysql_query("SELECT `th`.* ,
        `tp`.`name` AS `topic_name`,
        `cat`.`name` AS `category_name`,
        `tp`.`group_write` AS `topic_group_write`,
        COUNT(`msg`.`id`) AS `count`,
        (SELECT COUNT(`fv`.`id_user`) FROM `forum_views` AS `fv` WHERE `fv`.`id_theme` = `msg`.`id_theme`)  AS `views`
FROM `forum_messages` AS `msg`
LEFT JOIN `forum_themes` AS `th` ON `th`.`id` = `msg`.`id_theme`
LEFT JOIN `forum_topics` AS `tp` ON `tp`.`id` = `th`.`id_topic`
LEFT JOIN `forum_categories` AS `cat` ON `cat`.`id` = `th`.`id_category`
WHERE `th`.`id_autor` = '{$user->id}'
AND `th`.`group_show` <= '{$user->group}'
AND `tp`.`group_show` <= '{$user->group}'
AND `cat`.`group_show` <= '{$user->group}'
AND `msg`.`group_show` <= '{$user->group}'
GROUP BY `msg`.`id_theme`
ORDER BY MAX(`msg`.`time`) DESC LIMIT $pages->limit");


$listing = new listing();
while ($themes = mysql_fetch_assoc($q)) {
    $is_open = (int) ($themes['group_write'] <= $themes['topic_group_write']);
    $post = $listing->post();
    $post->icon("forum.theme.{$themes['top']}.$is_open.png");
    $post-> time = vremja($themes['time_last']);
    $post->title = for_value($themes['name']);
    $post->counter = $themes['count'];
    $post->url = 'theme.php?id=' . $themes['id'] . '&amp;page=end';
    $autor = new user($themes['id_autor']);
    $last_msg = new user($themes['id_last']);
    $post->content = ($autor->id != $last_msg->id ? $autor->nick . '/' . $last_msg->nick : $autor->nick) . '<br />';
    $post->content .= "(<a href='category.php?id=$themes[id_category]'>" . for_value($themes['category_name']) . "</a> &gt; <a href='topic.php?id=$themes[id_topic]'>" . for_value($themes['topic_name']) . "</a>)<br />";
    $post->bottom = __('Просмотров: %s', $themes['views']);
    
}

$listing -> display(__('Созданных Вами тем не найдено'));

$pages->display('?'); // вывод страниц
$doc->ret(__('Форум'), './');
?>