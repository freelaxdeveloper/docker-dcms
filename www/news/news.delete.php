<?php

include_once '../sys/inc/start.php';
$doc = new document(4);
$doc->title = __('Удаление новости');
$doc->ret(__('К новостям'), './');

$id = (int) @$_GET['id'];

$q = mysql_query("SELECT * FROM `news` WHERE `id` = '$id' LIMIT 1");

if (!mysql_num_rows($q))
    $doc->access_denied(__('Новость не найдена или уже удалена'));

$news = mysql_fetch_assoc($q);

$ank = new user($news['id_user']);

if ($ank->group > $user->group)
    $doc->access_denied(__('У Вас нет прав для удаления данной новости'));

if (isset($_POST['delete'])) {
    if (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session'])) {
        $doc->err(__('Проверочное число введено неверно'));
    } else {
        mysql_query("DELETE FROM `news` WHERE `id` = '$id' LIMIT 1");
        mysql_query("DELETE FROM `news_comments` WHERE `id_news` = '$id'");
        $doc->msg(__('Новость успешно удалена'));
        header('Refresh: 1; url=./');
        exit;
    }
}

$smarty = new design();
$smarty->assign('method', 'post');
$smarty->assign('action', '?id=' . $id . '&amp;' . passgen());
$elements = array();
$elements[] = array('type' => 'captcha', 'session' => captcha::gen(), 'br' => 1);
$elements[] = array('type' => 'text', 'value' => '* ' . output_text(__('Новость "%s" будет удалена без возможности восстановления', $news['title'])), 'br' => 1);
$elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'delete', 'value' => __('Удалить'))); // кнопка
$smarty->assign('el', $elements);
$smarty->display('input.form.tpl');
?>
