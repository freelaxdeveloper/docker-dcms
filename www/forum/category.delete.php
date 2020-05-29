<?php

include_once '../sys/inc/start.php';
$doc = new document();

$doc->title = __('Удаление категории');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора категории'));
    $doc->ret(__('Форум'), './');
    exit;
}
$id_category = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `forum_categories` WHERE `id` = '$id_category' AND `group_edit` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Категория не доступна для удаления'));
    $doc->ret(__('Форум'), './');
    exit;
}

$category = mysql_fetch_assoc($q);
$doc->title = __('Удаление категории "%s"', $category['name']); // шапка страницы

if (isset($_POST['delete'])) {
    if (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session'])) {
        $doc->err(__('Проверочное число введено неверно'));
    } else {
        // блокируем таблицы
        //   mysql_query("LOCK TABLES `forum_files` WRITE READ, `forum_categories` WRITE READ, `forum_topics` WRITE READ, `forum_themes` WRITE READ, `forum_messages` WRITE READ, `forum_history` WRITE READ, `forum_files` WRITE READ, `forum_vote` WRITE READ, `forum_vote_votes` WRITE READ");

        $q = mysql_query("SELECT `id` FROM `forum_themes` WHERE `id_category` = '$category[id]'");
        while ($theme = mysql_fetch_assoc($q)) {
            // удаление всех файлов темы
            $dir = new files(FILES . '/.forum/' . $theme['id']);
            $dir->delete();
            unset($dir);
        }

        mysql_query("DELETE
FROM `forum_topics`, `forum_themes` , `forum_messages`, `forum_history`, `forum_files`, `forum_vote`, `forum_vote_votes`
USING `forum_topics`
LEFT JOIN `forum_themes` ON `forum_themes`.`id_topic` = `forum_topics`.`id`
LEFT JOIN `forum_messages` ON `forum_messages`.`id_topic` = `forum_topics`.`id`
LEFT JOIN `forum_history` ON `forum_history`.`id_message` = `forum_messages`.`id`
LEFT JOIN `forum_files` ON `forum_files`.`id_topic` = `forum_topics`.`id`
LEFT JOIN `forum_vote` ON `forum_vote`.`id_theme` = `forum_themes`.`id`
LEFT JOIN `forum_vote_votes` ON `forum_vote_votes`.`id_theme` = `forum_themes`.`id`
LEFT JOIN `forum_views` ON `forum_vote_votes`.`id_theme` = `forum_themes`.`id`
WHERE `forum_topics`.`id_category` = '$category[id]'");

        mysql_query("DELETE FROM `forum_categories` WHERE `id` = '$category[id]' LIMIT 1");
        // оптимизация таблиц после удаления данных
        //   mysql_query("OPTIMIZE TABLE `forum_files`, `forum_categories`, `forum_topics`, `forum_themes`, `forum_messages`, `forum_history`, `forum_vote`, `forum_vote_votes`");
        // разблокируем таблицы
        //  mysql_query("UNLOCK TABLES");

        header('Refresh: 1; url=./');
        $dcms->log('Форум', 'Удаление категории "' . $category['name'] . '"');
        $doc->msg(__('Категория успешно удалена'));
        $doc->ret(__('Форум'), './');
        exit;
    }
}

$form = new form("?id=$category[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$form->captcha();
$form->bbcode('* ' . __('Все данные, относящиеся к данной категории будут безвозвратно удалены.'));
$form->button(__('Удалить'), 'delete');
$form->display();

$doc->act(__('Параметры категории'), 'category.edit.php?id=' . $category['id']);
$doc->ret(__('В категорию'), 'category.php?id=' . $category['id']);
$doc->ret(__('Форум'), './');
?>