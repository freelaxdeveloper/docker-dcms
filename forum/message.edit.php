<?php

include_once '../sys/inc/start.php';
$doc = new document(1);
$doc->theme = __('Редактирование сообщения');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора сообщения'));
    exit;
}

$id_message = (int) $_GET['id'];
$q = mysql_query("SELECT * FROM `forum_messages` WHERE `id` = '$id_message'");

if (!mysql_num_rows($q)) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Сообщение не найдено'));

    exit;
}
$message = mysql_fetch_assoc($q);
$autor = new user((int) $message['id_user']);

$access_edit = false;
$edit_time = $message['time'] - TIME + 600;

if ($user->group >= $message['group_edit'])
    $access_edit = true;
elseif ($user->id == $autor->id && $edit_time > 0) {
    $access_edit = true;
    $doc->msg(__('Для изменения сообщения осталось %d сек', $edit_time));
}

if (!$access_edit) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Сообщение не доступно для редактирования'));
    exit;
}


$doc->title = __('Сообщение от "%s" - редактирование', $autor->login);

if (isset($_GET['act']) && $_GET['act'] == 'hide') {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=theme.php?id=' . $message['id_theme']);
    mysql_query("UPDATE `forum_messages` SET `group_show` = '2' WHERE `id` = '$message[id]' LIMIT 1");
    $doc->msg(__('Сообщение успешно скрыто'));
    if (isset($_GET['return']))
        $doc->ret(__('В тему'), for_value($_GET['return']));
    else
        $doc->ret(__('В тему'), 'theme.php?id=' . $message['id_theme']);
    exit;
}

if (isset($_GET['act']) && $_GET['act'] == 'show') {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=theme.php?id=' . $message['id_theme']);
    mysql_query("UPDATE `forum_messages` SET `group_show` = '0' WHERE `id` = '$message[id]' LIMIT 1");
    $doc->msg(__('Сообщение будет отображаться'));
    if (isset($_GET['return']))
        $doc->ret('В тему', for_value($_GET['return']));
    else
        $doc->ret(__('В тему'), 'theme.php?id=' . $message['id_theme']);
    exit;
}

if (isset($_POST['message'])) {
    $message_new = text::input_text($_POST['message']);

    if ($message_new == $message['message']) {
        $doc->err(__('Изменения не обнаружены'));
    } elseif ($dcms->censure && $mat = is_valid::mat($message_new)) {
        $doc->err(__('Обнаружен мат: %', $mat));
    } elseif ($message_new) {
        if (isset($_GET['return']))
            header('Refresh: 1; url=' . $_GET['return']);
        else
            header('Refresh: 1; url=theme.php?id=' . $message['id_theme']);

        mysql_query("INSERT INTO `forum_history` (`id_message`, `id_user`, `time`, `message`) VALUES ('$message[id]', '" . ($message['edit_id_user'] ? $message['edit_id_user'] : $message['id_user']) . "', '" . ($message['edit_time'] ? $message['edit_time'] : $message['time']) . "', '" . my_esc($message['message']) . "')");
        mysql_query("UPDATE `forum_messages` SET `message` = '" . my_esc($message_new) . "', `edit_count` = `edit_count` + 1, `edit_id_user` = '$user->id', `edit_time` = '" . TIME . "' WHERE `id` = '$message[id]' LIMIT 1");
        $doc->msg(__('Сообщение успешно изменено'));

        if (isset($_GET['return']))
            $doc->ret('В тему', for_value($_GET['return']));
        else
            $doc->ret(__('В тему'), 'theme.php?id=' . $message['id_theme']);
        exit;
    } else {
        $doc->err(__('Нельзя оставить пустое сообщение'));
    }
}

$form = new form("?id=$message[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$form->textarea('message', __('Редактирование сообщения'), $message['message']);
$form->button(__('Применить'));
$form->display();

$doc->act(__('Вложения'), 'message.files.php?id=' . $message['id'] . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));

if (isset($_GET['return']))
    $doc->ret(__('В тему'), for_value($_GET['return']));
else
    $doc->ret(__('В тему'), 'theme.php?id=' . $message['id_theme']);
?>