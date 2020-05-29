<?php

include_once '../sys/inc/start.php';
$doc = new document();

$doc->title = __('Удаление раздела');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора раздела'));
    exit;
}
$id_topic = (int) $_GET['id'];
$q = mysql_query("SELECT * FROM `forum_topics` WHERE `id` = '$id_topic' AND `group_edit` <= '$user->group'");
if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Раздел не доступен для удаления'));
    exit;
}
$topic = mysql_fetch_assoc($q);

$q = mysql_query("SELECT * FROM `forum_categories` WHERE `id` = '$topic[id_category]'");
$category = mysql_fetch_assoc($q);

if (isset($_POST['delete'])) {
    if (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session'])) {
        $doc->err(__('Проверочное число введено неверно'));
    } else {
        $q = mysql_query("SELECT `id` FROM `forum_themes` WHERE `id_topic` = '$topic[id]'");
        while ($theme = mysql_fetch_assoc($q)) {
            // удаление всех файлов темы
            $dir = new files(FILES . '/.forum/' . $theme['id']);
            $dir->delete();
            unset($dir);
        }

        mysql_query("DELETE
FROM `forum_themes` , `forum_messages`, `forum_history`,  `forum_vote`, `forum_vote_votes`
USING `forum_themes`
LEFT JOIN `forum_messages` ON `forum_messages`.`id_theme` = `forum_themes`.`id`
LEFT JOIN `forum_history` ON `forum_history`.`id_message` = `forum_messages`.`id`
LEFT JOIN `forum_vote` ON `forum_vote`.`id_theme` = `forum_themes`.`id`
LEFT JOIN `forum_vote_votes` ON `forum_vote_votes`.`id_theme` = `forum_themes`.`id`
LEFT JOIN `forum_views` ON `forum_vote_votes`.`id_theme` = `forum_themes`.`id`
WHERE `forum_themes`.`id_topic` = '$topic[id]'");
        mysql_query("DELETE FROM `forum_topics` WHERE `id` = '$topic[id]' LIMIT 1");

        header('Refresh: 1; url=category.php?id=' . $topic['id_category']);

        $dcms->log('Форум', 'Удаление раздела из категории [url=/forum/category.php?id=' . $category['id'] . ']' . $category['name'] . '[/url]');

        $doc->msg(__('Рездел успешно удален'));
        exit;
    }
}


$doc->title = __('Удаление раздела "%s"', $topic['name']);

$form = new form("?id=$topic[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$form->captcha();
$form->bbcode('* ' . __('Все данные, относящиеся к данному разделу будут безвозвратно удалены.'));
$form->button(__('Удалить'), 'delete');
$form->display();

$doc->act(__('Параметры раздела'), 'topic.edit.php?id=' . $topic['id']);
$doc->ret(__('В раздел'), 'topic.php?id=' . $topic['id']);
$doc->ret(__('Форум'), './');
?>