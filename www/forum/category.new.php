<?php

include_once '../sys/inc/start.php';
$doc = new document(5);
$doc->title = __('Новая категория');

if (isset($_POST['name']) && isset($_POST['description']) && isset($_POST['position'])) {
    $name = text::for_name($_POST['name']);
    $description = text::input_text($_POST['description']);
    $position = (int) $_POST['position'];
    if (!$name) {
        $doc->err(__('Введите название категории'));
    } else {
        mysql_query("INSERT INTO `forum_categories` (`name`, `description`, `position`, `group_edit`)
 VALUES ('" . my_esc($name) . "', '" . my_esc($description) . "', '$position', '" . max($user->group, 5) . "')");

        $id_category = mysql_insert_id();
        $dcms->log('Форум', 'Создание категории "' . $name . '"');
        $doc->msg(__('Категория успешно создана'));
        // header('Refresh: 1; url=category.php?id='.$id_category);
        // header('Refresh: 1; url=?'.passgen());
        $doc->act(__('Создать еще'), '?' . passgen());
        $doc->ret(__('В категорию'), 'category.php?id=' . $id_category);
        $doc->ret(__('Форум'), './');
        exit;
    }
}

$form = new form('?' . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$form->text('name', __('Название категории'));
$form->textarea('description', __('Описание'));
$form->text('position', __('Позиция'), mysql_result(mysql_query("SELECT MAX(`position`) FROM `forum_categories`"), 0) + 1);
$form->button(__('Создать'));
$form->display();

$doc->ret(__('В форум'), './');
?>