<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$doc = new document(5);
$doc->title = __('Параметры регистрации');

if (isset($_POST['save'])) {
    $dcms->reg_open = (int) !empty($_POST['reg_open']);
    $dcms->reg_with_mail = (int) !empty($_POST['reg_with_mail']);
    $dcms->clear_users_not_verify = (int) !empty($_POST['clear_users_not_verify']);
    $dcms->reg_with_rules = (int) !empty($_POST['reg_with_rules']);
    $dcms->reg_with_invite = (int) !empty($_POST['reg_with_invite']);
    $dcms->balls_for_invite = (int) $_POST['balls_for_invite'];
    $dcms->user_write_limit_hour = (int) $_POST['user_write_limit_hour'];    
    $dcms->save_settings($doc);
}

$form = new form('?' . passgen());
$form->checkbox('reg_open', __('Разрешить регистрацию'), $dcms->reg_open);
$form->checkbox('reg_with_mail', __('Активация по E-mail'), $dcms->reg_with_mail);
$form->checkbox('clear_users_not_verify', __('Удалять неактивированных пользователей более суток'), $dcms->clear_users_not_verify);
$form->checkbox('reg_with_invite', __('Только по пригласительным'), $dcms->reg_with_invite);
$form->text('balls_for_invite', __('Стоимость одного пригласительного (баллы)'), $dcms->balls_for_invite);
$form->checkbox('reg_with_rules', __('Соглашение с правилами'), $dcms->reg_with_rules);
$form->text('user_write_limit_hour', __('Разрешено писать через (часы) после регистрации'), $dcms->user_write_limit_hour);
$form->button(__('Применить'), 'save');
$form->display();

$doc->ret(__('Админка'), '/dpanel/');
?>
