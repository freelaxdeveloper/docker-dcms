<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Мой профиль');

if (isset($_GET ['set_wmid'])) {
    if (empty($_GET ['id_user']) || $_GET ['id_user'] != $user->id) {
        $doc->err(__('Ошибка аккаунта'));
    } elseif (empty($_GET ['tmp_code'])) {
        $doc->err(__('Отсутствует код активации'));
    } elseif ($user->wmid_tmp_code !== $_GET ['tmp_code']) {
        $doc->err(__('Ошибка кода активации'));
    } else {
        $user->wmid_tmp_code = '';
        $user->wmid = $user->wmid_tmp;
        $user->wmid_tmp = '';
        $doc->msg(__('Ваш WMID успешно активирован'));
    }
}

if (isset($_POST ['save'])) {
    $user->realname = text::for_name(@$_POST ['realname']);
    $user->icq_uin = text::icq_uin(@$_POST ['icq']);

    if (isset($_POST ['ank_d_r'])) {
        if ($_POST ['ank_d_r'] == null)
            $user->ank_d_r = '';
        else {
            $ank_d_r = (int) $_POST ['ank_d_r'];
            if ($ank_d_r >= 1 && $ank_d_r <= 31)
                $user->ank_d_r = $ank_d_r;
            else
                $doc->err(__('Не корректный формат дня рождения'));
        }
    }

    if (isset($_POST ['ank_m_r'])) {
        if ($_POST ['ank_m_r'] == null)
            $user->ank_m_r = '';
        else {
            $ank_m_r = (int) $_POST ['ank_m_r'];
            if ($ank_m_r >= 1 && $ank_m_r <= 12)
                $user->ank_m_r = $ank_m_r;
            else
                $doc->err(__('Не корректный формат месяца рождения'));
        }
    }

    if (isset($_POST ['ank_g_r'])) {
        if ($_POST ['ank_g_r'] == null)
            $user->ank_g_r = '';
        else {
            $ank_g_r = (int) $_POST ['ank_g_r'];
            if ($ank_g_r >= date('Y') - 100 && $ank_g_r <= date('Y'))
                $user->ank_g_r = $ank_g_r;
            else
                $doc->err(__('Не корректный формат года рождения'));
        }
    }

    if (isset($_POST ['skype'])) {
        if (empty($_POST ['skype']))
            $user->skype = '';
        elseif (!is_valid::skype($_POST ['skype']))
            $doc->err(__('Указан не корректный логин Skype'));
        else {
            $user->skype = $_POST ['skype'];
        }
    }

    if (!empty($_POST ['wmid_tmp'])) {
        if ($user->wmid && $user->wmid != $_POST ['wmid_tmp']) {
            $doc->err(__('Активированный WMID изменять и удалять запрещено'));
        } elseif (!is_valid::wmid($_POST ['wmid_tmp'])) {
            $doc->err(__('Указан не корректный WebMoney идентификатор'));
        } elseif ($user->wmid != $_POST ['wmid_tmp']) {
            $tmp_code = passgen();

            $user->wmid_tmp_code = $tmp_code;
            $user->wmid_tmp = $_POST ['wmid_tmp'];


            $t = new design();
            $t->assign('title', __('Активация WebMoneyID'));
            $t->assign('site', $dcms->sitename);
            $t->assign('wmid', $user->wmid_tmp);
            $t->assign('login', $user->login);
            $t->assign('url', "http://{$_SERVER['HTTP_HOST']}/profile.edit.php?id_user={$user->id}&tmp_code={$tmp_code}&set_wmid");
            mail::send($user->wmid_tmp . '@wmkeeper.com', __('Подтверждение WMID'), $t->fetch('file:' . H . '/sys/templates/mail.webmoney.tpl'));

            $doc->msg(__('На Ваш email %s@wmkeeper.com отправлено письмо с кодом подтверждения', $user->wmid_tmp));
        }
    }

    if (isset($_POST ['email'])) {
        if (empty($_POST ['email']))
            $user->email = '';
        elseif (!is_valid::mail($_POST ['email']))
            $doc->err(__('Указан не корректный E-mail'));
        else {
            $user->email = $_POST ['email'];
        }
    }

    $user->description = text::input_text(@$_POST ['description']);

    $doc->msg(__('Параметры успешно приняты'));
}

$form = new design ();
$form->assign('method', 'post');
$form->assign('action', '?' . passgen());
$elements = array();

$elements [] = array('type' => 'input_text', 'title' => __('Реальное имя'), 'br' => 1, 'info' => array('name' => 'realname', 'value' => $user->realname));

$elements [] = array('type' => 'text', 'value' => __('Дата рождения') . ':', 'br' => 1);
$elements [] = array('type' => 'input_text', 'br' => 0, 'info' => array('name' => 'ank_d_r', 'value' => $user->ank_d_r, 'size' => 2, 'maxlength' => 2));
$elements [] = array('type' => 'input_text', 'br' => 0, 'info' => array('name' => 'ank_m_r', 'value' => $user->ank_m_r, 'size' => 2, 'maxlength' => 2));
$elements [] = array('type' => 'input_text', 'br' => 1, 'info' => array('name' => 'ank_g_r', 'value' => $user->ank_g_r, 'size' => 4, 'maxlength' => 4));

$elements [] = array('type' => 'input_text', 'title' => __('Номер ICQ'), 'br' => 1, 'info' => array('name' => 'icq', 'value' => $user->icq_uin));

$elements [] = array('type' => 'input_text', 'title' => __('Skype логин'), 'br' => 1, 'info' => array('name' => 'skype', 'value' => $user->skype));

$elements [] = array('type' => 'input_text', 'title' => __('E-mail'), 'br' => 1, 'info' => array('name' => 'email', 'value' => $user->email));

$elements [] = array('type' => 'input_text', 'title' => __('WebMoney ID') . ($user->wmid_tmp ? ' (' . $user->wmid_tmp . ' ?)' : '') , 'br' => 1, 'info' => array('name' => 'wmid_tmp', 'value' => $user->wmid, 'disabled' => $user->wmid));

$elements [] = array('type' => 'textarea', 'title' => __('О себе') . ' [256]', 'br' => 1, 'info' => array('name' => 'description', 'value' => $user->description));

$elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'save', 'value' => __('Применить'))); // кнопка
$form->assign('el', $elements);
$form->display('input.form.tpl');

$doc->ret(__('Личное меню'), '/menu.user.php');
?>
