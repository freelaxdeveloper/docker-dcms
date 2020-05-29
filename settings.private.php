<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Настройки приватности');

if (isset($_POST ['save'])) {
    $user->vis_email = (isset($_POST ['email']) && $_POST ['email']);
    $user->vis_icq = (isset($_POST ['icq']) && $_POST ['icq']);
    $user->vis_friends = (isset($_POST ['friends']) && $_POST ['friends']);
    $user->vis_skype = (isset($_POST ['skype']) && $_POST ['skype']);
    $user->mail_only_friends = (isset($_POST ['mail_only_friends']) && $_POST ['mail_only_friends']);
    $doc->msg(__('Параметры успешно сохранены'));
}

$form = new design ();
$form->assign('method', 'post');
$form->assign('action', '?' . passgen());
$elements = array();

$elements [] = array('type' => 'checkbox', 'br' => 1, 'info' => array('value' => 1, 'checked' => $user->vis_email, 'name' => 'email', 'text' => __('Показывать %s', 'E-Mail')));
$elements [] = array('type' => 'checkbox', 'br' => 1, 'info' => array('value' => 1, 'checked' => $user->vis_icq, 'name' => 'icq', 'text' => __('Показывать %s', 'ICQ')));
$elements [] = array('type' => 'checkbox', 'br' => 1, 'info' => array('value' => 1, 'checked' => $user->vis_skype, 'name' => 'skype', 'text' => __('Показывать %s', 'Skype')));
$elements [] = array('type' => 'checkbox', 'br' => 1, 'info' => array('value' => 1, 'checked' => $user->vis_friends, 'name' => 'friends', 'text' => __('Список друзей')));
$elements [] = array('type' => 'text', 'br' => 1, 'value' => '* ' . __('Ваши друзья будут видеть все ваши данные независимо от установленных параметров'));


$elements [] = array('type' => 'checkbox', 'br' => 1, 'info' => array('value' => 1, 'checked' => $user->mail_only_friends, 'name' => 'mail_only_friends', 'text' => __('Принимать личные сообщения только от друзей')));


$elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'save', 'value' => __('Применить'))); // кнопка
$form->assign('el', $elements);
$form->display('input.form.tpl');

$doc->ret(__('Личное меню'), '/menu.user.php');
?>