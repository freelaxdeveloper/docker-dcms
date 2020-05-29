<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Очистка категории');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора категории'));
    exit;
}
$id_category = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `forum_categories` WHERE `id` = '$id_category' AND `group_edit` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Категория не доступна для чистки'));
    exit;
}

$category = mysql_fetch_assoc($q);

$doc->title = __('Чистка категории "%s"', $category['name']); // шапка страницы

if (isset($_POST['clear'])) {
    if (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session'])) {
        $doc->err(__('Проверочное число введено неверно'));
    } else {
        $doc->err('На данный момент не реализовано');
    }
}

$form = new form("?id=$category[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$count['themes_all'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_themes` WHERE `id_category` = '$category[id]'"), 0);
$form->bbcode(__('Всего тем в категории: %s', '[b]' . $count['themes_all'] . '[/b]'));

// темы, в которых была активность более года назад
$count['themes_old'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_themes` WHERE `id_category` = '$category[id]' AND `top` = '0' AND `time_last` < '" . (TIME - 31536000) . "'"), 0);
// echo "Не активные более года: $count[themes_old]<br />";
// темы, закрытые более трех месяцев
$count['themes_old2'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_themes` WHERE `id_category` = '$category[id]' AND `top` = '0' AND `group_write` > '1' AND `time_last` < '" . (TIME - 7884000) . "'"), 0);
// echo "Закрытые более трех месяцев: $count[themes_old2]<br />";
if (!$count['themes_old'] + $count['themes_old2'])
    $form->bbcode(__('[b]' . 'Категория не требует очистки' . '[/b]'));

$form->checkbox('themes_old', __('Не активные более года: %d ' . misc::number($count['themes_old'], 'тема', 'темы', 'тем'), $count['themes_old']), (bool) $count['themes_old']);
$form->checkbox('themes_old2', __('Закрыто более 3-х месяцев: %d ' . misc::number($count['themes_old2'], 'тема', 'темы', 'тем'), $count['themes_old2']), (bool) $count['themes_old2']);
$form->captcha();
$form->bbcode('* ' . __('Данные будут безвозвратно удалены'));
$form->button(__('Чистить'), 'clear');
$form->display();

$doc->act(__('Параметры категории'), 'category.edit.php?id=' . $category['id']);
$doc->ret(__('В категорию'), 'category.php?id=' . $category['id']);
$doc->ret(__('Форум'), './');
?>