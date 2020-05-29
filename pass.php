<?php

include_once 'sys/inc/start.php';
$doc = new document();
$doc->title = __('Восстановление пароля');

if ($user->group) {
    $doc->err(__('Вы уже авторизованы'));
    exit;
}

if (!empty($_GET['id']) && !empty($_GET['code'])) {
    $doc->ret(__('Восстановление пароля'), '?' . passgen());

    $id = (int) $_GET['id'];
    $code = preg_replace('#[^a-z0-9]#i', '', $_GET['code']);

    $ank = new user($id);

    if (!$ank->group) {
        $doc->err(__('Пользователь с ID#%s не зарегистрирован', $id));
        exit;
    }

    if (!$ank->recovery_password || $ank->recovery_password !== $code) {
        $doc->err(__('Ключ для восстановления пароля не действителен'));
        exit;
    }

    $doc->title = __('Восстановление пароля к "%s"', $ank->login);

    if (isset($_POST['password1']) && isset($_POST['password2'])) {
        if ($_POST['password1'] !== $_POST['password2'])
            $doc->err(__('Пароли не совпадают'));
        elseif (!is_valid::password($_POST['password1']))
            $doc->err(__('Не корректный новый пароль'));
        else {
            $ank->password = crypt::hash($_POST['password1'], $dcms->salt);
            $ank->recovery_password = '';
            $doc->msg(__('Пароль успешно изменен'));
            header('Refresh: 2; url=/login.php?' . passgen());
            exit;
        }
    }

    $form = new design();
    $form->assign('method', 'post');
    $form->assign('action', '?id=' . $id . '&amp;code=' . $code . '&amp;' . passgen());
    $elements = array();

    $elements[] = array('type' => 'password', 'title' => __('Новый пароль'), 'br' => 1,
        'info' => array('name' => 'password1', 'value' => ''));

    $elements[] = array('type' => 'password', 'title' => __('Подтвердите пароль'), 'br' => 1,
        'info' => array('name' => 'password2', 'value' => ''));

    $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'save', 'value' => __('Применить'))); // кнопка
    $form->assign('el', $elements);
    $form->display('input.form.tpl');

    exit;
}

if (isset($_POST['post'])) {
    if (!is_valid::mail(@$_POST['mail']))
        $doc->err(__('Указан не корректный E-mail'));
    else {
        $mail = $_POST['mail'];
        $q = mysql_query("SELECT `id` FROM `users` WHERE `reg_mail` = '" . my_esc($mail) . "' ORDER BY `id` DESC LIMIT 1");

        if (!mysql_num_rows($q))
            $doc->err(__('Учетная запись, зарегистрированная на данный Email не обнаружена'));
        else {
            $ank = new user(mysql_result($q, 0, 'id'));
            $ank->recovery_password = $recovery_password = md5(passgen(100));

            $t = new design();
            $t->assign('title', __('Восстановление пароля'));
            $t->assign('login', $ank->login);
            $t->assign('site', $dcms->sitename);
            $t->assign('url', 'http://' . $_SERVER['HTTP_HOST'] . '/pass.php?id=' . $ank->id . '&amp;code=' . $recovery_password);

            if (mail::send($mail, __('Восстановление пароля'), $t->fetch('file:' . H . '/sys/templates/mail.pass.tpl'))) {
                $step = 3;
                $doc->msg(__('На Ваш E-mail отправлено письмо с ссылкой для активации аккаунта'));
            } else {
                $doc->err(__('Ошибка при отправке email, попробуйте позже'));
            }
        }
    }
}

$form = new design();
$form->assign('method', 'post');
$form->assign('action', '?' . passgen());
$elements = array();
$elements[] = array('type' => 'input_text', 'title' => __('Ваш E-mail'), 'br' => 1, 'info' => array('name' => 'mail'));
$elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'post', 'value' => __('Продолжить'))); // кнопка
$form->assign('el', $elements);
$form->display('input.form.tpl');
exit;
?>
