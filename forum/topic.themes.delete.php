<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Удаление тем');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора раздера'));
    exit;
}
$id_topic = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `forum_topics` WHERE `id` = '$id_topic' AND `group_edit` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Раздел не доступен для редактирования'));
    exit;
}

$topic = mysql_fetch_assoc($q);

$doc->title .= ' - ' . $topic['name'];

switch (@$_GET['show']) {
    case 'all':$show = 'all';
        break;
    default:$show = 'part';
        break;
}

if (isset($_POST['delete'])) {
    $deleted = 0;

    foreach ($_POST as $key => $value) {
        if ($value && preg_match('#^theme([0-9]+)$#ui', $key, $n)) {
            if (function_exists('set_time_limit'))
                set_time_limit(30);

            $q = mysql_query("SELECT * FROM `forum_themes` WHERE `id` = '$n[1]' AND `group_edit` <= '$user->group' LIMIT 1");

            if (!@mysql_num_rows($q))
                continue;

            $theme = mysql_fetch_assoc($q);

            mysql_query("DELETE FROM `forum_themes` WHERE `id` = '$theme[id]' LIMIT 1");
            mysql_query("DELETE
FROM `forum_messages`, `forum_history`
USING `forum_messages`
LEFT JOIN `forum_history` ON `forum_history`.`id_message` = `forum_messages`.`id`
WHERE `forum_messages`.`id_theme` = '$theme[id]'");

            mysql_query("DELETE FROM `forum_vote` WHERE `id_theme` = '$theme[id]'");
            mysql_query("DELETE FROM `forum_vote_votes` WHERE `id_theme` = '$theme[id]'");
            mysql_query("DELETE FROM `forum_views` WHERE `id_theme` = '$theme[id]'");

            $dir = new files(FILES . '/.forum/' . $theme['id']);
            $dir->delete();
            unset($dir);

            $deleted++;
        }
    }

    $dcms->log('Форум', 'Удаление ' . $deleted . ' тем' . misc::number($deleted, 'ы', '', '') . ' из раздела [url=/forum/topic.php?id=' . $topic['id'] . ']' . $topic['name'] . '[/url]');
    $doc->msg(__('Успешно удален' . misc::number($deleted, 'а', 'ы', 'о') . ' %d тем' . misc::number($deleted, 'а', 'ы', ''), $deleted));
}
// меню сортировки
$ord = array();
$ord[] = array("?id=$topic[id]&amp;show=all", __('Все'), $show == 'all');
$ord[] = array("?id=$topic[id]&amp;show=part", __('Постранично'), $show == 'part');
$or = new design();
$or->assign('order', $ord);
$or->display('design.order.tpl');

$listing = new listing();
if ($show == 'part') {
    $pages = new pages;
    $pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_themes` WHERE `id_topic` = '$topic[id]' AND `group_show` <= '$user->group'"), 0); // количество сообщений  теме
    $pages->this_page(); // получаем текущую страницу
    $q = mysql_query("SELECT * FROM `forum_themes`  WHERE `id_topic` = '$topic[id]' AND `group_show` <= '$user->group' ORDER BY `time_last` DESC LIMIT $pages->limit");
} else
    $q = mysql_query("SELECT * FROM `forum_themes`  WHERE `id_topic` = '$topic[id]' AND `group_show` <= '$user->group' ORDER BY `time_last` DESC");

while ($theme = mysql_fetch_assoc($q)) {
    $ch = $listing->checkbox();
    $ch->name = 'theme' . $theme['id'];
    $ch->title = for_value($theme['name']);

    $autor = new user($theme['id_autor']);
    $last_msg = new user($theme['id_last']);

    $ch->content = ($autor->id != $last_msg->id ? $autor->nick . '/' . $last_msg->nick : $autor->nick) . ' (' . vremja($theme['time_last']) . ')';
}

$form = new form('?id=' . $topic['id']);
$form->html($listing->fetch(__('Темы отсутствуют')));
$form->button(__('Удалить выделенные темы'), 'delete');
$form->display();

if ($show == 'part')
    $pages->display('?id=' . $theme['id'] . '&amp;show=part&amp;');

$doc->ret(__('В раздел'), 'topic.php?id=' . $topic['id']);
$doc->ret(__('В категорию'), 'category.php?id=' . $topic['id_category']);
$doc->ret(__('Форум'), './');
?>
