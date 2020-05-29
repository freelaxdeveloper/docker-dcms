<?php

include_once '../sys/inc/start.php';
$doc = new document(3);
$doc->title = __('Удаление сообщений');

if (isset($_POST['delete'])) {
    if (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session'])) {
        $doc->err(__('Проверочное число введено неверно'));
    } else {
        $dcms->log('Мини чат', 'Очистка от всех сообщений');

        mysql_query("TRUNCATE TABLE `chat_mini`");
        $doc->msg(__('Все сообщения успешно удалены'));
        header('Refresh: 1; url=./?' . SID);
        $doc->ret(__('Вернуться'), './');
        exit;
    }
}

$smarty = new design();
$smarty->assign('method', 'post');
$smarty->assign('action', '?' . passgen());
$elements = array();
$elements[] = array('type' => 'captcha', 'session' => captcha::gen(), 'br' => 1);
$elements[] = array('type' => 'text', 'value' => '* '.__('Все сообщения будут удалены без возможности восстановления'), 'br' => 1);
$elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'delete', 'value' => __('Удалить'))); // кнопка
$smarty->assign('el', $elements);
$smarty->display('input.form.tpl');

$doc->ret(__('Вернуться'), './');
?>