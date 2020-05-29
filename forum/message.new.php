<?php

include_once '../sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Новое сообщение');

if (!isset($_GET['id_theme']) || !is_numeric($_GET['id_theme'])) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора темы'));
    exit;
}
$id_theme = (int) $_GET['id_theme'];

$q = mysql_query("SELECT * FROM `forum_themes` WHERE `id` = '$id_theme' AND `group_write` <= '$user->group' LIMIT 1");

if (!mysql_num_rows($q)) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('В выбранную тему писать нельзя'));
    exit;
}

$theme = mysql_fetch_assoc($q);

$doc->title = $theme['name'] . ' - ' . __('Новое собщение');

$can_write = true;
if (!$user->is_writeable) {
    $doc->msg(__('Писать запрещено'), 'write_denied');
    $can_write = false;
}

if ($can_write) {

    if (isset($_POST['message'])) {
        $message = (string) $_POST['message'];
        $users_in_message = text::nickSearch($message);
        $message = text::input_text($message);


        $af = &$_SESSION['antiflood']['forummessage'][$id_theme][$message]; // защита от дублирования сообщений в теме

        if ($dcms->censure && $mat = is_valid::mat($message)) {
            $doc->err(__('Обнаружен мат: %', $mat));
        } elseif (!empty($af) && $af > TIME - 600 || $theme['id_last'] == $user->id && $theme['time_last'] > TIME - 10) {
            header('Refresh: 4; url=theme.php?id=' . $theme['id'] . '&page=end&' . SID);
            $doc->ret(__('В тему'), 'theme.php?id=' . $theme['id'] . '&amp;page=end');
            $doc->err(__('Сообщение уже отправлено или вы пытаетесь ответить сами себе'));
            exit;
        } elseif ($dcms->forum_message_captcha && $user->group < 2 && (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session']))) {
            $doc->err(__('Проверочное число введено неверно'));
        } elseif ($message) {
            $user->balls++;
            $af = TIME;

            $post_update = false;
            $q = mysql_query("SELECT * FROM `forum_messages` WHERE `id_theme` = '$theme[id]' ORDER BY `id` DESC LIMIT 1");

            if (mysql_num_rows($q)) {
                $last_post = mysql_fetch_assoc($q);
                if ($last_post['id_user'] == $user->id && $last_post['time'] > TIME - 7200) {
                    $post_update = true;
                    $id_message = $last_post['id'];
                }
            }

            if ($post_update && !isset($_POST['add_file'])) {
                $message = $last_post['message'] . "\n\n[small]Через " . vremja(TIME - $theme['time_last'] + TIME) . ":[/small]\n" . $message;
                mysql_query("UPDATE `forum_messages` SET `message` = '" . my_esc($message) . "' WHERE `id_theme` = '$theme[id]' AND `id_user` = '$user->id' ORDER BY `id` DESC LIMIT 1");
            } else {
                mysql_query("INSERT INTO `forum_messages` (`id_category`, `id_topic`, `id_theme`, `id_user`, `time`, `message`, `group_show`, `group_edit`)
 VALUES ('$theme[id_category]','$theme[id_topic]','$theme[id]','$user->id','" . TIME . "','" . my_esc($message) . "','$theme[group_show]','$theme[group_edit]')");


                $id_message = mysql_insert_id();
            }
            if (isset($_POST['add_file'])) {
                header('Refresh: 1; url=message.files.php?id=' . $id_message . '&return=' . urlencode('theme.php?id=' . $theme['id'] . '&page=end'));
                $doc->ret(__('Добавить файлы'), 'message.files.php?id=' . $id_message . '&amp;return=' . urlencode('theme.php?id=' . $theme['id'] . '&page=end'));
            } else {
                header('Refresh: 1; url=theme.php?id=' . $theme['id'] . '&page=end&' . SID);
                $doc->ret(__('В тему'), 'theme.php?id=' . $theme['id'] . '&amp;page=end');
            }


            if ($users_in_message) {
                for ($i = 0; $i < count($users_in_message) && $i < 20; $i++) {
                    $user_id_in_message = $users_in_message[$i];
                    if ($user_id_in_message == $user->id) {
                        continue;
                    }
                    $ank_in_message = new user($user_id_in_message);
                    if ($ank_in_message->notice_mention) {
                        $count_posts_for_user = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_messages` WHERE `id_theme` = '$theme[id]' AND `group_show` <= '$ank_in_message->group'"), 0);
                        $ank_in_message->mess("[user]{$user->id}[/user] упомянул" . ($user->sex ? '' : 'а') . " о Вас на форуме в [url=/forum/message.php?id_message={$id_message}]сообщении[/url] в теме [url=/forum/theme.php?id={$theme['id']}&postnum={$count_posts_for_user}#message{$id_message}]{$theme['name']}[/url]");
                    }
                }
            }

            $doc->msg(__('Сообщение успешно отправлено'));
            mysql_query("UPDATE `forum_themes` SET `time_last` = '" . TIME . "', `id_last` = '$user->id' WHERE `id` = '$theme[id]' LIMIT 1");
            // mysql_query("UPDATE `forum_topics` SET `time_last` = '".TIME."' WHERE `id` = '$theme[id_topic]' LIMIT 1");
            exit;
        } else {
            $doc->err(__('Сообщение пусто'));
        }
    }

    $form = new form("?id_theme=$theme[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
    $form->textarea('message', __('Сообщение'));
    $form->checkbox('add_file', __('Добавить файл'));
    if ($dcms->forum_message_captcha && $user->group < 2)
        $form->captcha();
    $form->button(__('Отправить'));
    $form->display();
}


if (isset($_GET['return']))
    $doc->ret(__('В тему'), for_value($_GET['return']));
else
    $doc->ret(__('В тему'), 'theme.php?id=' . $theme['id']);
?>