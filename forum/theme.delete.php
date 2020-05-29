<?php

include_once '../sys/inc/start.php';
$doc = new document(2);
$doc->title = __('Удаление темы');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора темы'));
    exit;
}
$id_theme = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `forum_themes` WHERE `id` = '$id_theme' AND `group_edit` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Тема не доступна для редактирования'));
    exit;
}

$theme = mysql_fetch_assoc($q);

$q = mysql_query("SELECT * FROM `forum_topics` WHERE `id` = '$theme[id_topic]' LIMIT 1");

$topic = mysql_fetch_assoc($q);

$doc->title .= ' "' . $theme['name'] . '"';

if (isset($_POST['delete'])) {
    if (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session'])) {
        $doc->err(__('Проверочное число введено неверно'));
    } else {
        // блокируем таблицы
        //  mysql_query("LOCK TABLES `forum_themes` WRITE READ, `forum_messages` WRITE READ, `forum_history` WRITE READ, `forum_vote` WRITE READ, `forum_vote_votes` WRITE READ");

        mysql_query("DELETE FROM `forum_themes` WHERE `id` = '$theme[id]' LIMIT 1");

        mysql_query("DELETE
FROM `forum_messages`, `forum_history`
USING `forum_messages`
LEFT JOIN `forum_history` ON `forum_history`.`id_message` = `forum_messages`.`id`
WHERE `forum_messages`.`id_theme` = '$theme[id]'");

        mysql_query("DELETE FROM `forum_vote` WHERE `id_theme` = '$theme[id]'");
        mysql_query("DELETE FROM `forum_vote_votes` WHERE `id_theme` = '$theme[id]'");
        mysql_query("DELETE FROM `forum_views` WHERE `id_theme` = '$theme[id]'");

        // оптимизация таблиц после удаления данных
        // mysql_query("OPTIMIZE TABLE `forum_themes`, `forum_messages`, `forum_history`, `forum_vote`, `forum_vote_votes`");
        // разблокируем таблицы
        // mysql_query("UNLOCK TABLES");
        // удаление всех файлов темы
        $dir = new files(FILES . '/.forum/' . $theme['id']);
        $dir->delete();
        unset($dir);

        header('Refresh: 1; url=topic.php?id=' . $theme['id_topic']);
        $doc->msg(__('Тема успешно удалена'));
        $dcms->log('Форум', 'Удаление темы "' . $theme['name'] . '" из раздела [url=/forum/topic.php?id=' . $topic['id'] . ']' . $topic['name'] . '[/url]');
        exit;
    }
}

$form = new form("?id=$theme[id]&amp;" . passgen());
$form->captcha();
$form->bbcode('* ' . __('Все данные, относящиеся к данной теме будут безвозвратно удалены.'));
$form->button(__('Удалить'), 'delete');
$form->display();

if (isset($_GET['return']))
    $doc->ret(__('В тему'), for_value($_GET['return']));
else
    $doc->ret(__('В тему'), 'theme.php?id=' . $theme['id']);

$doc->ret(__('В раздел'), 'topic.php?id=' . $theme['id_topic']);
$doc->ret(__('В категорию'), 'category.php?id=' . $theme['id_category']);
$doc->ret(__('Форум'), './');
?>