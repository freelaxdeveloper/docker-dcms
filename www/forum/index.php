<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Форум - Категории');


$listing = new listing();


$post = $listing->post();
$post->url = 'search.php';
$post->title = __('Поиск');
$post->icon('forum.search');

if (false === ($new_themes = cache_counters::get('forum.new_themes.' . $user->group))) {
    $new_themes = mysql_result(mysql_query("SELECT COUNT(*)
FROM `forum_themes` AS `th`
LEFT JOIN `forum_topics` AS `tp` ON `tp`.`id` = `th`.`id_topic`
LEFT JOIN `forum_categories` AS `cat` ON `cat`.`id` = `th`.`id_category`
WHERE `th`.`group_show` <= '{$user->group}'
AND `tp`.`group_show` <= '{$user->group}'
AND `cat`.`group_show` <= '{$user->group}'
AND `th`.`time_create` > '" . NEW_TIME . "'"), 0);
    cache_counters::set('forum.new_themes.' . $user->group, $new_themes, 20);
}

$post = $listing->post();
$post->url = 'last.themes.php';
$post->title = __('Новые темы');
if ($new_themes) {
    $post->counter = '+' . $new_themes;
}
$post->icon('forum.lt');




if (false === ($new_posts = cache_counters::get('forum.new_posts.' . $user->group))) {
    $new_posts = mysql_result(mysql_query("SELECT COUNT(DISTINCT(`msg`.`id_theme`))
FROM `forum_messages` AS `msg`
LEFT JOIN `forum_themes` AS `th` ON `th`.`id` = `msg`.`id_theme`
LEFT JOIN `forum_topics` AS `tp` ON `tp`.`id` = `th`.`id_topic`
LEFT JOIN `forum_categories` AS `cat` ON `cat`.`id` = `th`.`id_category`
WHERE `th`.`group_show` <= '{$user->group}'
AND `tp`.`group_show` <= '{$user->group}'
AND `cat`.`group_show` <= '{$user->group}'
AND `msg`.`group_show` <= '{$user->group}'
AND `msg`.`time` > '" . NEW_TIME . "'"), 0);
    cache_counters::set('forum.new_posts.' . $user->group, $new_posts, 20);
}

$post = $listing->post();
$post->url = 'last.posts.php';
$post->title = __('Обновленные темы');
if ($new_posts) {
    $post->counter = '+' . $new_posts;
}
$post->icon('forum.lp');





if ($user->id) {
    if (false === ($my_themes = cache_counters::get('forum.my_themes.' . $user->id))) {
        $my_themes = mysql_result(mysql_query("SELECT COUNT(DISTINCT(`msg`.`id_theme`))
FROM `forum_messages` AS `msg`
LEFT JOIN `forum_themes` AS `th` ON `th`.`id` = `msg`.`id_theme`
LEFT JOIN `forum_topics` AS `tp` ON `tp`.`id` = `th`.`id_topic`
LEFT JOIN `forum_categories` AS `cat` ON `cat`.`id` = `th`.`id_category`
WHERE `th`.`id_autor` = '{$user->id}'
AND `th`.`group_show` <= '{$user->group}'
AND `tp`.`group_show` <= '{$user->group}'
AND `cat`.`group_show` <= '{$user->group}'
AND `msg`.`group_show` <= '{$user->group}'
AND `msg`.`id_user` <> '{$user->id}'
AND `msg`.`time` > '" . NEW_TIME . "'"), 0);

        cache_counters::set('forum.my_themes.' . $user->id, $my_themes, 20);
    }


    $post = $listing->post();
    $post->url = 'my.themes.php';
    $post->title = __('Мои темы');
    if ($my_themes) {
        $post->counter = '+' . $my_themes;
    }
    $post->icon('forum.my_themes');
}

$pages = new pages();
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_categories` WHERE `group_show` <= '$user->group'"), 0); // количество категорий форума
$pages->this_page(); // получаем текущую страницу

$q = mysql_query("SELECT * FROM `forum_categories` WHERE `group_show` <= '$user->group' ORDER BY `position` ASC LIMIT $pages->limit");
while ($category = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $post->url = "category.php?id=$category[id]";
    $post->title = for_value($category['name']);
    $post->icon('forum.category');
    $post->post = text::for_opis($category['description']);
}

$listing->display(__('Доступных Вам категорий нет'));


$pages->display('?'); // вывод страниц

if ($user->group >= 5) {
    $doc->act(__('Создать категорию'), 'category.new.php');
    $doc->act(__('Порядок категорий'), 'categories.sort.php');
}
?>