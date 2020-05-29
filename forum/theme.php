<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Форум');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора темы'));
    exit;
}
$id_theme = (int) $_GET['id'];
$q = mysql_query("SELECT `forum_themes`.* , `forum_categories`.`name` AS `category_name` , `forum_topics`.`name` AS `topic_name`
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

if ($user->group) {
    $q = mysql_query("SELECT * FROM `forum_views` WHERE `id_theme` = '$theme[id]' AND `id_user` = '$user->id'");
    if (!mysql_num_rows($q)) {
        mysql_query("INSERT INTO `forum_views` (`id_theme`, `id_user`, `time`) VALUES ('{$theme['id']}', '{$user->id}', '" . (TIME + 1) . "')");
    } else {
        mysql_query("UPDATE `forum_views` SET `time` = '" . (TIME + 1) . "' WHERE `id_user` = '{$user->id}' AND `id_theme` = '{$theme['id']}'");
    }
}

$doc->title .= ' - ' . $theme['name'];

$doc->description = $theme['name'];
$doc->keywords[] = $theme['name'];
$doc->keywords[] = $theme['topic_name'];
$doc->keywords[] = $theme['category_name'];

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_messages` WHERE `id_theme` = '$theme[id]' AND `group_show` <= '$user->group'"), 0); // количество сообщений  теме
$pages->this_page(); // получаем текущую страницу

if ($theme['id_vote']) {
    $q = mysql_query("SELECT * FROM `forum_vote` WHERE `id` = '$theme[id_vote]' AND `group_view` <= '$user->group'");
    if (mysql_num_rows($q)) {
        $vote = mysql_fetch_assoc($q);

        $votes = new votes($vote['name']);

        $vote_accept = !@mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote_votes` WHERE `id_vote` = '$theme[id_vote]' AND `id_user` = '$user->id'"), 0);
        if (!$vote['active'])
            $vote_accept = false;
        $q = mysql_query("SELECT `vote`, COUNT(*) as `count` FROM `forum_vote_votes` WHERE `id_vote` = '$theme[id_vote]' GROUP BY `vote`");
        $countets = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0);
        while ($r = mysql_fetch_assoc($q)) {
            $countets[$r['vote']] = $r['count'];
        }

        for ($i = 1; $i <= 10; $i++) {
            if ($vote['v' . $i]) {
                $votes->vote($vote['v' . $i], $countets[$i], 'theme.php?id=' . $theme['id'] . '&amp;page=' . $pages->this_page . '&amp;vote=' . $i);
            }
        }

        if (!empty($_GET['vote']) && $user->group >= $vote['group_vote'] && $vote_accept) {
            $vote_add = (int) $_GET['vote'];
            if ($vote['v' . $vote_add]) {
                mysql_query("INSERT INTO `forum_vote_votes` (`id_vote`, `id_theme`, `id_user`, `vote`) VALUES ('$vote[id]','$theme[id]', '$user->id', '$vote_add')");
                $doc->msg(__('Ваш голос успешно засчитан'));
                header('Refresh: 1; url=theme.php?id=' . $theme['id'] . '&page=' . $pages->this_page);
                $doc->ret(__('Вернуться в тему'), 'theme.php?id=' . $theme['id'] . '&amp;page=' . $pages->this_page);
                exit;
            }
        }

        $votes->display($user->group >= $vote['group_vote'] && $vote_accept);
    }
}

$q = mysql_query("SELECT * FROM `forum_messages` WHERE `id_theme` = '$theme[id]' AND `group_show` <= '$user->group' ORDER BY `id` ASC LIMIT $pages->limit");

$users_preload = array();
$messages = array();
while ($message = mysql_fetch_assoc($q)) {
    $messages[] = $message;
    $users_preload[] = $message['id_user'];
}

new user($users_preload); // предзагрузка данных пользователей одним запросом

$listing = new listing();
foreach ($messages AS $message) {
    $post = $listing->post();


    $ank = new user((int) $message['id_user']);


    if ($user->group) {
        $post->action('quote', "message.php?id_message=$message[id]&amp;quote"); // цитирование
    }

    if ($user->group >= $message['group_edit']) {
        if ($theme['group_show'] <= 1) {
            if ($message['group_show'] <= 1) {
                $post->action('hide', "message.edit.php?id=$message[id]&amp;return=" . URL . "&amp;act=hide&amp;" . passgen()); // скрытие
            } else {
                $post->action('show', "message.edit.php?id=$message[id]&amp;return=" . URL . "&amp;act=show&amp;" . passgen()); // показ

                $post->bottom = __('Сообщение скрыто');
            }
        }
        $post->action('edit', "message.edit.php?id=$message[id]&amp;return=" . URL); // редактирование
    } elseif ($user->id == $message['id_user'] && TIME < $message['time'] + 600) {
        // автору сообщения разрешается его редактировать в течении 10 минут
        $post->action('edit', "message.edit.php?id=$message[id]&amp;return=" . URL); // редактирование
    }

    if ($ank->group <= $user->group && $user->id != $ank->id) {
        if ($user->group >= 2)
        // бан
            $post->action('complaint', "/dpanel/user.ban.php?id_ank=$message[id_user]&amp;return=" . URL . "&amp;link=" . urlencode("/forum/message.php?id_message=$message[id]"));
        else
        // жалоба на сообщение
            $post->action('complaint', "/complaint.php?id=$message[id_user]&amp;return=" . URL . "&amp;link=" . urlencode("/forum/message.php?id_message=$message[id]"));
    }

    $post->title = $ank->nick();
    $post->icon($ank->icon());

    $post->time = vremja($message['time']);
    $post->url = 'message.php?id_message=' . $message['id'];
    $post->content = text::for_opis($message['message']);


    if ($message['edit_id_user'] && ($ank->group < $user->group || $ank->id == $user->id)) {
        $ank_edit = new user($message['edit_id_user']);
        $post->bottom .= ' <a href="message.history.php?id=' . $message['id'] . '&amp;return=' . URL . '">' . __('Изменено') . '(' . $message['edit_count'] . ')</a> ' . $ank_edit->login . ' (' . vremja($message['edit_time']) . ')<br />';
    }

    $post_dir_path = H . '/sys/files/.forum/' . $theme['id'] . '/' . $message['id'];
    if (@is_dir($post_dir_path)) {
        $listing_files = new listing();
        $dir = new files($post_dir_path);
        $content = $dir->getList('time_add:asc');
        $files = &$content['files'];
        $count = count($files);
        for ($i = 0; $i < $count; $i++) {
            $file = $listing_files->post();
            $file->title = for_value($files[$i]->runame);
            $file->url = "/files" . $files[$i]->getPath() . ".htm?order=time_add:asc";
            $file->content = output_text($files[$i]->properties);
            $file->icon($files[$i]->icon());
            $file->image = $files[$i]->image();
        }
        $post->content .= $listing_files->fetch();
    }
}

$listing->display(__('Сообщения отсутствуют'));


$pages->display('theme.php?id=' . $theme['id'] . '&amp;'); // вывод страниц

if ($theme['group_write'] <= $user->group) {
    $doc->act(__('Написать сообщение'), 'message.new.php?id_theme=' . $theme['id'] . "&amp;return=" . URL);
}

if ($user->group >= 2 || $theme['group_edit'] <= $user->group) {
    $doc->act(__('Действия'), 'theme.actions.php?id=' . $theme['id']);
}

$doc->ret($theme['topic_name'], 'topic.php?id=' . $theme['id_topic']);
$doc->ret($theme['category_name'], 'category.php?id=' . $theme['id_category']);
$doc->ret(__('Форум'), './');
?>