<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$groups = groups::load_ini();
$doc = new document(4);
$doc->title = __('Профиль');

$browser_types = array('wap', 'pda', 'itouch', 'web');

if (isset($_GET ['id_ank']))
    $ank = new user($_GET ['id_ank']);
else
    $ank = $user;

if (!$ank->group) {
    if (isset($_GET ['return'])) {
        header('Refresh: 1; url=' . $_GET ['return']);
    } else {
        header('Refresh: 1; url=/');
    }

    $doc->err(__('Не удалось загрузить данные пользователя'));
    exit();
}

$doc->title .= ' "' . $ank->login . '"';

if ($ank->group >= $user->group) {
    if (isset($_GET ['return'])) {
        header('Refresh: 1; url=' . $_GET ['return']);
    } else {
        header('Refresh: 1; url=/');
    }

    $doc->err(__('Ваш статус не позволяет производить действия с данным пользователем'));
    exit();
}

if (isset($_POST ['save'])) {
    $ank->realname = text::for_name(@$_POST ['realname']);
    $ank->icq_uin = text::icq_uin(@$_POST ['icq']);
    $ank->balls = abs((int) @$_POST ['balls']);

    if (isset($_POST ['ank_d_r'])) {
        if ($_POST ['ank_d_r'] == null)
            $ank->ank_d_r = null;
        else {
            $ank_d_r = (int) $_POST ['ank_d_r'];
            if ($ank_d_r >= 1 && $ank_d_r <= 31) {
                $ank->ank_d_r = $ank_d_r;
            } else {
                $doc->err(__('Не корректный формат дня рождения'));
            }
        }
    }

    if (isset($_POST ['ank_m_r'])) {
        if ($_POST ['ank_m_r'] == null)
            $ank->ank_m_r = null;
        else {
            $ank_m_r = (int) $_POST ['ank_m_r'];
            if ($ank_m_r >= 1 && $ank_m_r <= 12) {
                $ank->ank_m_r = $ank_m_r;
            } else {
                $doc->err(__('Не корректный формат месяца рождения'));
            }
        }
    }

    if (isset($_POST ['ank_g_r'])) {
        if ($_POST ['ank_g_r'] == null)
            $ank->ank_g_r = null;
        else {
            $ank_g_r = (int) $_POST ['ank_g_r'];
            if ($ank_g_r >= date('Y') - 100 && $ank_g_r <= date('Y')) {
                $ank->ank_g_r = $ank_g_r;
            } else {
                $doc->err(__('Не корректный формат года рождения'));
            }
        }
    }

    if (!empty($_POST ['skype'])) {
        if (!is_valid::skype($_POST ['skype'])) {
            $doc->err(__('Указан не корректный логин Skype'));
        } else {
            $ank->skype = $_POST ['skype'];
        }
    }

    if (!empty($_POST ['email'])) {
        if (!is_valid::mail($_POST ['email'])) {
            $doc->err(__('Указан не корректный E-mail'));
        } else {
            $ank->email = $_POST ['email'];
        }
    }
    if (isset($_POST ['wmid'])) {
        if (empty($_POST ['wmid']))
            $ank->wmid = '';
        elseif (!is_valid::wmid($_POST ['wmid'])) {
            $doc->err(__('Указан не корректный идентификатор WebMoney'));
        } else {
            $ank->wmid = $_POST ['wmid'];
        }
    }
    if (!empty($_POST ['reg_mail'])) {
        if (!is_valid::mail($_POST ['reg_mail'])) {
            $doc->err(__('Указан не корректный Primary E-mail'));
        } else {
            $ank->reg_mail = $_POST ['reg_mail'];
        }
    }

    foreach ($browser_types as $type) {
        $t = "items_per_page_$type";
        // количество пунктов на страницу
        if (!empty($_POST [$t])) {
            $ipp = (int) $_POST [$t];
            if ($ipp >= 5 && $ipp <= 99) {
                $ank->$t = $ipp;
            } else {
                $doc->err(__('Недопустимое количество пунктов на страницу'));
            }
        }

        $t = "theme_$type";



        if (!empty($_POST [$t])) {
            $theme_set = (string) $_POST [$t];

            if (themes::exists($theme_set, $type)) {
                $ank->$t = $theme_set;
            }
        }
    }
    // временной сдвиг
    if (!empty($_POST ['time_shift'])) {
        $ipp = (int) $_POST ['time_shift'];
        if ($ipp >= - 12 && $ipp <= 12) {
            $ank->time_shift = $ipp;
        } else {
            $doc->err(__('Недопустимое время'));
        }
    }

    $ank->vis_email = (int) !empty($_POST ['vis_email']);
    $ank->vis_icq = (int) !empty($_POST ['vis_icq']);
    $ank->vis_friends = (int) !empty($_POST ['vis_friends']);
    $ank->vis_skype = (int) !empty($_POST ['vis_skype']);

    $dcms->log('Пользователи', 'Изменение профиля пользователя [url=/profile.view.php?id=' . $ank->id . ']' . $ank->login . '[/url]');

    $doc->msg(__('Профиль успешно изменен'));
}


$form = new form("?id_ank=$ank->id&amp;" . passgen() . (isset($_GET ['return']) ? '&return=' . urlencode($_GET ['return']) : null));

foreach ($browser_types as $type) {
    $t = "items_per_page_$type";
    $form->text($t, __('Пунктов на страницу') . ' (' . $type . ') [5-99]', $ank->$t);
}

foreach ($browser_types as $b_type) {
    $t = 'theme_' . $b_type;
    $options = array(); // темы оформления для wap браузера    
    $themes_list = themes::getList($b_type); // только для определенного типа браузера
    foreach ($themes_list as $theme)
        $options [] = array($theme ['dir'], $theme ['name'], $ank->$t === $theme ['dir']);
    $form->select($t, __('Тема оформления') . ' (' . strtoupper($b_type) . ')', $options);
}

$options = array(); // Врменной сдвиг
for ($i = - 12; $i < 12; $i++)
    $options [] = array($i, date('G:i', TIME + $i * 60 * 60), $ank->time_shift == $i);
$form->select('time_shift', __('Время') . ' (' . strtoupper($b_type) . ')', $options);

$form->text('realname', __('Реальное имя'), $ank->realname);

$form->bbcode(__('Дата рождения') . ':');
$form->text('ank_d_r', false, $ank->ank_d_r, false, 2);
$form->text('ank_m_r', false, $ank->ank_m_r, false, 2);
$form->text('ank_g_r', false, $ank->ank_g_r, true, 4);

$form->text('balls', __('Баллы'), $ank->balls);
$form->text('icq', __('Номер ICQ'), $ank->icq_uin);
$form->checkbox('vis_icq', __('Показывать ICQ в анкете'), $ank->vis_icq);
$form->text('skype', __('Skype логин'), $ank->skype);
$form->checkbox('vis_skype', __('Показывать Skype в анкете'), $ank->vis_skype);
$form->text('reg_mail', __('Primary E-mail'), $ank->reg_mail);
$form->text('email', __('E-mail'), $ank->email);
$form->checkbox('vis_email', __('Показывать Email в анкете'), $ank->vis_email);
$form->text('wmid', __('WebMoney ID'), $ank->wmid);
$form->checkbox('vis_friends', __('Отображать список друзей'), $ank->vis_friends);
$form->button(__('Применить'), 'save');
$form->display();


$doc->ret(__('Действия'), 'user.actions.php?id=' . $ank->id);
$doc->ret(__('Анкета "%s"', $ank->login), '/profile.view.php?id=' . $ank->id);
$doc->ret(__('Админка'), '/dpanel/');
?>
