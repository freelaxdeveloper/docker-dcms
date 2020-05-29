<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Новый раздел');

if (!isset($_GET['id_category']) || !is_numeric($_GET['id_category'])) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./?' . SID);
    $doc->err(__('Ошибка выбора категории'));
    exit;
}
$id_category = (int) $_GET['id_category'];

$q = mysql_query("SELECT * FROM `forum_categories` WHERE `id` = '$id_category' AND `group_write` <= '$user->group'");

if (!mysql_num_rows($q)) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./?' . SID);
    $doc->err(__('В выбранной категории запрещено создавать разделы'));
    exit;
}

$category = mysql_fetch_assoc($q);

if (isset($_POST['name'])) {
    $name = text::for_name($_POST['name']);
    $description = text::input_text($_POST['description']);
    if (!$name) {
        $doc->err(__('Введите название раздела'));
    } else {
        mysql_query("INSERT INTO `forum_topics` (`id_category`, `time_create`,`time_last`, `name`, `description`, `group_show`, `group_write`, `group_edit`)
 VALUES ('$category[id]', '" . TIME . "','" . TIME . "','" . my_esc($name) . "', '" . my_esc($description) . "', '$category[group_show]','" . max($category['group_show'], 1) . "','" . max($user->group, 4) . "')");

        $id_topic = mysql_insert_id();
        $doc->msg(__('Раздел успешно создан'));

        $dcms->log('Форум', 'Создание раздела [url=/forum/topic.php?id=' . $id_topic . ']' . $name . '[/url] в категории [url=/forum/category.php?id=' . $category['id'] . ']' . $category['name'] . '[/url]');

        if (isset($_GET['return'])) {
            header('Refresh: 1; url=' . $_GET['return']);
            $doc->ret(__('Вернуться'), for_value($_GET['return']));
        } else {
            header('Refresh: 1; url=topic.php?id=' . $id_topic . '&' . SID);
            $doc->ret(__('В раздел'), 'topic.php?id=' . $id_topic);
        }

        exit;
    }
}

$doc->title = $category['name'] . ' - ' . __('Новый раздел');

$form = new form("?id_category=$category[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$form->text('name', __('Название раздела'));
$form->textarea('description', __('Описание'));
$form->button(__('Создать раздел'));
$form->display();

if (isset($_GET['return']))
    $doc->ret(__('В категорию'), for_value($_GET['return']));
else
    $doc->ret(__('В категорию'), 'category.php?id=' . $category['id']);
?>
