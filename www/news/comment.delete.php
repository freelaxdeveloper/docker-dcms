<?php

include_once '../sys/inc/start.php';
$doc = new document(2);
$doc->title = __('Удаление комментария');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора комментария'));
    exit;
}
$id_message = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `news_comments` WHERE `id` = '$id_message' LIMIT 1");

if (!mysql_num_rows($q)) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Комментарий не найден'));
    exit;
}

$message = mysql_fetch_assoc($q);

mysql_query("DELETE FROM `news_comments` WHERE `id` = '$id_message' LIMIT 1");
$doc->msg(__('Комментарий успешно удален'));

if (isset($_GET['return']))
    header('Refresh: 1; url=' . $_GET['return']);
else
    header('Refresh: 1; url=./');
if (isset($_GET['return']))
    $doc->ret(__('Вернуться'), for_value($_GET['return']));
else
    $doc->ret(__('Вернуться'), './');
?>