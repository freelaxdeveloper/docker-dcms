<?php

include_once '../sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Файлы');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
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

$q = mysql_query("SELECT * FROM `forum_themes` WHERE `id` = '$message[id_theme]'");

if (!mysql_num_rows($q)) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Тема не найдена'));
    exit;
}

$theme = mysql_fetch_assoc($q);

$autor = new user((int) $message['id_user']);

$access_edit = false;
$edit_time = $message['time'] - TIME + 600;

if ($user->group >= $message['group_edit'])
    $access_edit = true;
elseif ($user->id == $autor->id && $edit_time > 0) {
    $access_edit = true;
    $doc->msg(__('Для выгрузки файлов осталось %s сек', $edit_time));
}

if (!$access_edit) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Сообщение не доступно для редактирования'));
    exit;
}


$forum_dir = new files(FILES . '/.forum');

$theme_dir_path = FILES . '/.forum/' . $message['id_theme'];
if (!@is_dir($theme_dir_path)) {
    if (!$th_dir = $forum_dir->mkdir(__('Файлы темы #%d', $message['id_theme']), $message['id_theme']))
        $doc->access_denied(__('Не удалось создать папку под файлы темы'));

    $th_dir->group_show = $theme['group_show'];
    $th_dir->group_write = max($theme['group_write'], 2);
    $th_dir->group_edit = $theme['group_edit'];
    unset($th_dir);
}

$theme_dir = new files($theme_dir_path);

$post_dir_path = FILES . '/.forum/' . $message['id_theme'] . '/' . $message['id'];

if (!@is_dir($post_dir_path)) {
    if (!$p_dir = $theme_dir->mkdir(__('Файлы к сообщению #%d', $message['id']), $message['id']))
        $doc->access_denied(__('Не удалось создать папку под файлы сообщения'));
    $p_dir->id_user = $user->id;
    $p_dir->group_show = 0; // папка будет доступна гостям
    unset($p_dir);
}

$dir = new files($post_dir_path);


if (!empty($_FILES['file'])) {
    if ($_FILES['file']['error'])
        $doc->err(__('Ошибка при загрузке'));
    elseif (!$_FILES['file']['size'])
        $doc->err(__('Содержимое файла пусто'));
    elseif ($dcms->forum_files_upload_size && $_FILES['file']['size'] > $dcms->forum_files_upload_size)
        $doc->err(__('Размер файла превышает установленные ограниченияя'));
    else {
        if ($files_ok = $dir->filesAdd(array($_FILES['file']['tmp_name'] => $_FILES['file']['name']))) {
            $files_ok[$_FILES['file']['tmp_name']]->id_user = $user->id;
            $files_ok[$_FILES['file']['tmp_name']]->group_show = $dir->group_show;
            $files_ok[$_FILES['file']['tmp_name']]->group_edit = max($user->group, $dir->group_write, 2);
            unset($files_ok);
            $doc->msg(__('Файл "%s" успешно добавлен', $_FILES['file']['name']));
        } else {
            $doc->err(__('Не удалось сохранить выгруженный файл'));
        }
    }
} elseif (!empty($_GET['delete'])) {
    
}

$doc->title = __('Файлы к сообщению от "%s"', $autor->login);

$listing = new listing();
$content = $dir->getList('time_add:asc');

foreach ($content['files'] AS $file) {
    $post = $listing->post();
    $post->icon($file->icon());
    $post->image = $file->image();
    $post->title = for_value($file->runame);
    $post->url = "/files{$dir->path_rel}/" . urlencode($file->name) . ".htm";
    $post->content[] = $file->properties;
}
$listing->display(__('Вложения отсутствуют'));

$smarty = new design();
$smarty->assign('method', 'post');
$smarty->assign('files', 1);
$smarty->assign('action', "/forum/message.files.php?id=$message[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$elements = array();
$elements[] = array('type' => 'file', 'title' => 'Файл', 'br' => 1, 'info' => array('name' => 'file'));
$elements[] = array('type' => 'text', 'br' => 1, 'value' => '* ' . __('Файлы, размер которых превышает %s, загружены не будут', size_data($dcms->forum_files_upload_size))); // кнопка
$elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('value' => __('Прикрепить'))); // кнопка
$smarty->assign('el', $elements);
$smarty->display('input.form.tpl');

$doc->ret(__('В тему'), 'theme.php?id=' . $message['id_theme']);
?>