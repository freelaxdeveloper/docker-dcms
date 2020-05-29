<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Настройки уведомлений');

if (isset($_POST ['save'])) {
    $user->notice_mention = (isset($_POST ['notice_mention']) && $_POST ['notice_mention']);
    $user->notification_forum = (isset($_POST ['notification_forum']) && $_POST ['notification_forum']);
    $doc->msg(__('Параметры успешно сохранены'));
}

$form = new design ();
$form->assign('method', 'post');
$form->assign('action', '?' . passgen());
$elements = array();

$elements [] = array('type' => 'checkbox', 'br' => 1, 'info' => array('value' => 1, 'checked' => $user->notice_mention, 'name' => 'notice_mention', 'text' => __('Упоминание ника (@%s)',$user->login)));
$elements [] = array('type' => 'checkbox', 'br' => 1, 'info' => array('value' => 1, 'checked' => $user->notification_forum, 'name' => 'notification_forum', 'text' => __('Ответ на форуме')));

$elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'save', 'value' => __('Применить'))); // кнопка
$form->assign('el', $elements);
$form->display('input.form.tpl');

$doc->ret(__('Личное меню'), '/menu.user.php');
?>
