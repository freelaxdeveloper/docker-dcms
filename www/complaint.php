<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Жалоба на пользователя');



$can_write = true;
if (!$user->is_writeable) {
    $doc->msg(__('Вы не можете оставить жалобу'), 'write_denied');
    if (!empty($_GET['return'])) {
        $doc->ret(__('Вернуться'), for_value($_GET['return']));
    }
    exit;
}



$ank = new user(@$_GET['id']);

if (!$ank->group || $ank->group > $user->group) {
    if (isset($_GET['return'])) {
        header('Refresh: 1; url=' . $_GET['return']);
    } else {
        header('Refresh: 1; url=/');
    }
    $doc->err(__('Пользователь не найден'));
    exit;
}

$menu = new menu_code('code'); // загружаем меню кодекса
$doc->title = __('Жалоба на "%s"', $ank->login);

if (isset($_POST['complaint'])) {
    $link = !empty($_POST['link']) ? (string) $_POST['link'] : false;
    $code = !empty($_POST['code']) ? (string) $_POST['code'] : false;
    $comm = text::input_text(@$_POST['comment']);

    if (!$link) {
        $doc->err(__('Не указана ссылка на нарушение'));
    } elseif (!isset($menu->menu_arr[$code])) {
        $doc->err(__('Не выбрано нарушение'));
    } elseif (!$comm) {
        $doc->err(__('Необходимо прокомментировать жалобу'));
    } elseif (mysql_result(mysql_query("SELECT COUNT(*) FROM `complaints` WHERE `id_user` = '$user->id' AND `id_ank` = '$ank->id' AND `link` = '" . my_esc($link) . "' AND `time` > '" . NEW_TIME . "'"), 0))
        $doc->err(__('Вы уже жаловались сегодня на этого пользователя'));
    else {
        if (isset($_GET['return'])) {
            header('Refresh: 1; url=' . $_GET['return']);
        }

        mysql_query("INSERT INTO `complaints` (`time`, `id_user`, `id_ank`, `link`, `code`, `comment`)
VALUES ('" . TIME . "', '$user->id', '$ank->id', '" . my_esc($link) . "', '" . my_esc($code) . "', '" . my_esc($comm) . "')");

        $doc->msg(__('Жалоба будет рассмотрена модератором'));



        $mess = "Поступила [url=/dpanel/user.complaints.php]жалоба[/url] на пользователя [user]$ank->id[/user] от [user]$user->id[/user]";
        $admins = groups::getAdmins(2);
        foreach ($admins AS $admin) {
            $admin->mess($mess);
        }



        if (!empty($_GET['return'])) {
            $doc->ret(__('Вернуться'), for_value($_GET['return']));
        }

        exit;
    }
}

$link = !empty($_GET['link']) ? $_GET['link'] : (!empty($_POST['link']) ? $_POST['link'] : false);

$smarty = new design();
$smarty->assign('method', 'post');
$smarty->assign('action', '?' . passgen() . '&amp;id=' . $ank->id . (!empty($_GET['return']) ? '&amp;return=' . for_value($_GET['return']) : null));
$elements = array();
$elements[] = array('type' => 'input_text', 'title' => __('Ссылка'), 'br' => 1, 'info' => array('name' => 'link', 'value' => $link));

$elements[] = array('type' => 'select', 'br' => 1, 'title' => __('Нарушение'), 'info' => array('name' => 'code', 'options' => $menu->options()));

$elements[] = array('type' => 'textarea', 'title' => __('Комментарий'), 'br' => 1, 'info' => array('name' => 'comment'));
$elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('value' => __('Пожаловаться'), 'name' => 'complaint')); // кнопка
$smarty->assign('el', $elements);
$smarty->display('input.form.tpl');

if (!empty($_GET['return'])) {
    $doc->ret(__('Вернуться'), for_value($_GET['return']));
}
?>
