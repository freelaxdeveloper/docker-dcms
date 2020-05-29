<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Пригласительные');

if (isset($_GET['id'])) {
    $id_inv = (int) $_GET['id'];
    $q = mysql_query("SELECT * FROM `invations` WHERE `id` = '$id_inv' AND `id_user` = '$user->id' AND `id_invite` IS NULL LIMIT 1");

    if (!mysql_num_rows($q)) {
        header('Refresh: 1; url=?');
        $design->err(__('Пригласительный не найден'));
        $design->ret(__('К пригласительным'), '?');
        $design->head($title); // шапка страницы
        $design->title($title); // заголовок страницы
        $design->foot(); // ноги
        exit;
    }
    $inv = mysql_fetch_assoc($q);

    if (isset($_POST['delete']) && $inv['time_reg'] < TIME - 86400) {
        mysql_query("DELETE FROM `invations` WHERE `id` = '$inv[id]' LIMIT 1");
        header('Refresh: 1; url=?');
        $doc->msg(__('Пригласительный успешно удален'));
        $doc->ret(__('К пригласительным'), '?');
        exit;
    }

    if (isset($_POST['email']) && !$inv['email']) {
        if (!is_valid::mail($_POST['email']))
            $doc->err(__('Указан не корректный E-mail'));
        else {
            $email = $_POST['email'];
            $inv['code'] = passgen();
            $t = new design();
            $t->assign('title', __('Пригласительный'));
            $t->assign('login', $user->login);
            $t->assign('site', $dcms->sitename);
            $t->assign('url', 'http://' . $_SERVER['HTTP_HOST'] . '/reg.php?invite=' . $inv['code']);

            if (mail::send($email, __('Приглашение'), $t->fetch('file:' . H . '/sys/templates/mail.invite.tpl'))) {
                mysql_query("UPDATE `invations` SET `email` = '" . my_esc($email) . "', `time_reg` = '" . TIME . "', `code` = '$inv[code]' WHERE `id` = '$inv[id]' LIMIT 1");

                header('Refresh: 1; url=?');
                $doc->msg(__('Пригласительный успешно отправлен'));
                $doc->ret(__('К пригласительным'), '?');

                exit;
            } else
                $doc->err(__('Ошибка при отправке email, попробуйте позже'));
        }
    }

    $doc->title = __("Пригласительный #%s", $inv['id']);
    $doc->ret(__('К пригласительным'), '?');

    if ($inv['email']) {
        echo __('Пригласительный отправлен на email: %s', $inv['email']) . "<br />";
        echo __("Отправлен: %s", vremja($inv['time_reg'])) . "<br />";

        if ($inv['time_reg'] < TIME - 86400) {
            if (isset($_GET['delete'])) {
                $smarty = new design();
                $smarty->assign('method', 'post');
                $smarty->assign('action', "?id=$inv[id]");
                $elements = array();
                $elements[] = array('type' => 'text', 'br' => 1, 'value' => __('Подтвердите удаление пригласительного'));
                $elements[] = array('type' => 'text', 'br' => 1, 'value' => __('Его место займет новый пригласительный'));
                $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'delete', 'value' => __('Удалить'))); // кнопка
                $smarty->assign('el', $elements);
                $smarty->display('input.form.tpl');
            }

            $doc->act(__('Удалить приглашение'), "?id=$inv[id]&amp;delete");
        } else {
            echo __("В случае ошибки или отказа от пригласительного его можно будет удалить по истечению суток с момента отправки");
        }
    } else {
        $smarty = new design();
        $smarty->assign('method', 'post');
        $smarty->assign('action', "?id=$inv[id]");
        $elements = array();
        $elements[] = array('type' => 'input_text', 'br' => 1, 'title' => __('Email'), 'info' => array('name' => 'email', 'value' => ''));
        $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('value' => __('Отправить'))); // кнопка
        $smarty->assign('el', $elements);
        $smarty->display('input.form.tpl');
    }
    exit;
}

$k_inv = (int) ($user->balls / $dcms->balls_for_invite); // количество пригласительных
$doc->msg(__("У Вас %s пригласительны" . misc::number($k_inv, 'й', 'x', 'х'), $k_inv), 'invations');
$k = mysql_result(mysql_query("SELECT COUNT(*) FROM `invations` WHERE `id_user` = '$user->id'"), 0);

if ($k_inv > $k) {
    // пополняем список пригластельных
    $k_add = $k_inv - $k;
    $arr_ins = array();
    for ($i = 0; $i < $k_add; $i++)
        $arr_ins[] = "('$user->id')";
    mysql_query("INSERT INTO `invations` (`id_user`) VALUES " . implode(',', $arr_ins));
}


$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `invations` WHERE `id_user` = '$user->id'"), 0); // количество пригласительных
$pages->this_page(); // получаем текущую страницу

$q = mysql_query("SELECT * FROM `invations` WHERE `id_user` = '$user->id' ORDER BY (`id_invite` IS NULL) DESC, (`email` IS NULL) ASC, `id` ASC LIMIT $pages->limit");


$listing = new listing();
while ($inv = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $post->icon('invite');
    if ($inv['id_invite']) {
        $ank = new user($inv['id_invite']);
        $post->time = vremja($inv['time_reg']);
        $post->content = __('Использован');        
        $post->title = $ank->nick();
        $post->url = '/profile.view.php?id=' . $ank->id;
    } elseif ($inv['email']) {
        $post->url = '?id=' . $inv['id'];
        $post->title = __('Пригласительный #%s', $inv['id']);
        $post->content = __('Отправлен на email: %s', $inv['email']) . '<br />';
        if (!$inv['code']) {
            $post->content .= __('Активирован');
        }
        if ($inv['time_reg'] < TIME - 86400) {
            // 86400 секунд = 1 сутки - вчемя, через которое можно деактивировать неиспользованный пригласительный
            $post->action('delete', "?id={$inv['id']}&amp;delete");
        }
    } else {
        $post->title = "<a href='?id=$inv[id]'>" . __('Пригласительный #%s', $inv['id']) . "</a>";
        $post->content = __('Не использован');
    }
}
$listing->display(__('Список пригласительных пуст'));

$pages->display('?'); // вывод страниц

$doc->ret(__('Личное меню'), '/menu.user.php');
?>