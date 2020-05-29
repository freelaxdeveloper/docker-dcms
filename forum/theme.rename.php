<?php

include_once '../sys/inc/start.php';
$doc = new document(2);
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

if (isset($_POST['save'])) {
    if (isset($_POST['name'])) {
        $name = text::for_name($_POST['name']);
        if ($name && $name != $theme['name']) {
            $dcms->log('Форум', 'Изменение названия темы ' . $theme['name'] . ' на [url=/forum/theme.php?id=' . $theme['id'] . ']' . $name . '[/url]');

            $theme['name'] = $name;
            mysql_query("UPDATE `forum_themes` SET `name` = '" . my_esc($theme['name']) . "' WHERE `id` = '$theme[id]' LIMIT 1");
            $doc->msg(__('Название темы успешно изменено'));
        }
    }
}
$doc->title = __('Переименование темы %s', $theme['name']);

$form = new form("?id=$theme[id]&amp;" . passgen());
$form->text('name', __('Название'), $theme['name']);
$form->button(__('Применить'), 'save');
$form->display();

$doc->ret(__('Действия'), 'theme.actions.php?id=' . $theme['id']);
$doc->ret(__('Вернуться в тему'), 'theme.php?id=' . $theme['id']);
$doc->ret(empty($theme['topic_name']) ? __('В раздел') : $theme['topic_name'], 'topic.php?id=' . $theme['id_topic']);
$doc->ret(empty($theme['category_name']) ? __('В категорию') : $theme['category_name'], 'category.php?id=' . $theme['id_category']);
$doc->ret(__('Форум'), './');
?>
