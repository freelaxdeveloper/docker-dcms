<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Новые сообщения');



$today = mktime(0, 0, 0);
$week = $today - 3600 * 24 * 7;


switch (@$_GET['period']) {
    case 'week':
        $period = 'week';
        $q_time_start = $week;
        $q_time_end = TIME;
        $doc->title = __('Сообщения за неделю');
        $cache_time = 3600; // кэш в секундах
        break;
    case 'yesterday':
        $period = 'yesterday';
        $q_time_start = $today - 3600 * 24;
        $q_time_end = $today;
        $doc->title = __('Вчерашние сообщения');
        $cache_time = 3600; // кэш в секундах
        break;
    default:
        $period = 'default';
        $q_time_start = NEW_TIME;
        $q_time_end = TIME;
        $cache_time = 20; // кэш в секундах
        break;
}



$cache_id = 'forum.last.posts_all.period-' . $period;


if (false === ($posts_all = cache::get($cache_id))) {
    $posts_all = array();
    $q = mysql_query("SELECT `th`.* ,
        `tp`.`name` AS `topic_name`,
        `cat`.`name` AS `category_name`,
        `tp`.`group_write` AS `topic_group_write`,
            GREATEST(`th`.`group_show`, `tp`.`group_show`, `cat`.`group_show`, `msg`.`group_show`) AS `group_show`,
            COUNT(DISTINCT `msg`.`id`) AS `count`,     
            (SELECT COUNT(*) FROM `forum_messages` AS `msg` WHERE `msg`.`id_theme` = `th`.`id` AND `msg`.`time` > '" . $q_time_start . "') AS `count_new`,
            (SELECT COUNT(`fv`.`id_user`) FROM `forum_views` AS `fv` WHERE `fv`.`id_theme` = `msg`.`id_theme`)  AS `views`            
FROM `forum_messages` AS `msg`
LEFT JOIN `forum_themes` AS `th` ON `th`.`id` = `msg`.`id_theme`
LEFT JOIN `forum_topics` AS `tp` ON `tp`.`id` = `th`.`id_topic`
LEFT JOIN `forum_categories` AS `cat` ON `cat`.`id` = `th`.`id_category`
WHERE `th`.`time_last` > '" . $q_time_start . "'
AND `th`.`time_last` < '" . $q_time_end . "'
GROUP BY `msg`.`id_theme`
ORDER BY MAX(`msg`.`id`) DESC");

    while ($theme = mysql_fetch_assoc($q)) {
        $posts_all[] = $theme;
    }

    cache::set($cache_id, $posts_all, $cache_time);
}


$count = count($posts_all);
$posts_for_view = array();
for ($i = 0; $i < $count; $i++) {
    if ($posts_all[$i]['group_show'] > $user->group) {
        continue;
    }
    $posts_for_view[] = $posts_all[$i];
}


$views = array();
$count_posts = count($posts_for_view);
if ($count_posts && $user->id) {
    for ($i = 0; $i < $count_posts; $i++) {
        $themes[] = $posts_for_view[$i]['id'];
    }

    $q = mysql_query("SELECT `id_theme`, MAX(`time`) AS `time` FROM `forum_views`  WHERE `id_user` = '$user->id' AND (`id_theme` = '" . implode("' OR `id_theme` = '", $themes) . "') GROUP BY `id_theme`");
    if (mysql_num_rows($q)) {
        while ($view = mysql_fetch_assoc($q)) {
            $views[$view['id_theme']] = $view['time'];
        }
    }
}




$pages = new pages;
$pages->posts = $count_posts;
$pages->this_page();
$start = $pages->my_start();
$end = $pages->end();



$ord = array();
$ord[] = array("?period=default&amp;page={$pages->this_page}", $dcms->new_time_as_date ? __('Сегодня') : __('За сутки'), $period == 'default');
$ord[] = array("?period=yesterday&amp;page={$pages->this_page}", __('Вчера'), $period == 'yesterday');
$ord[] = array("?period=week&amp;page={$pages->this_page}", __('За неделю'), $period == 'week');
$or = new design();
$or->assign('order', $ord);
$or->display('design.order.tpl');



$listing = new listing();

for ($z = $start; $z < $end && $z < $pages->posts; $z++) {
    $post = $listing->post();
    $themes = $posts_for_view[$z];
    if ($user->id) {
        if (isset($views[$themes['id']])) {
            $post->hightlight = $themes['time_last'] > $views[$themes['id']];
        } else {
            $post->hightlight = true;
        }
    }


    $is_open = (int) ($themes['group_write'] <= $themes['topic_group_write']);

    $post->icon("forum.theme.{$themes['top']}.$is_open.png");
    $post->time = vremja($themes['time_last']);
    $post->title = for_value($themes['name']);
    $post->counter = '+' . $themes['count_new'];
    $post->url = 'theme.php?id=' . $themes['id'] . '&amp;page=end';
    $autor = new user($themes['id_autor']);
    $last_msg = new user($themes['id_last']);
    $post->content = ($autor->id != $last_msg->id ? $autor->nick . '/' . $last_msg->nick : $autor->nick) . '<br />';
    $post->content .= "(<a href='category.php?id=$themes[id_category]'>" . for_value($themes['category_name']) . "</a> &gt; <a href='topic.php?id=$themes[id_topic]'>" . for_value($themes['topic_name']) . "</a>)<br />";
    $post->bottom = __('Просмотров: %s', $themes['views']);
}

$listing->display(__('Сообщений не найдено'));


$pages->display('?period=' . $period . '&amp;'); // вывод страниц


$doc->ret(__('Форум'), './');
?>