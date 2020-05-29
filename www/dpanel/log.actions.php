<?php

include_once '../sys/inc/start.php';
$doc = new document(2);
$doc->title = __('Лог действий');

if (isset($_GET['id_user'])) {
    $id_user = 'all';
    $sql_where = ' WHERE 1 = 1';

    if ($_GET['id_user'] !== 'all') {
        $ank = new user($_GET['id_user']);
        $doc->title .= ' "' . $ank->login . '"';
        $id_user = $ank->id;
        $sql_where = " WHERE `id_user` = '$ank->id'";
    }

    if (!empty($_GET['module'])) {
        $module = (string) $_GET['module'];
        // вывод списка действий по модулю
        $listing = new listing();
        $pages = new pages;
        $pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `action_list_administrators`$sql_where AND `module` = '" . my_esc($module) . "'"), 0); // количество
        $pages->this_page(); // получаем текущую страницу
        $q = mysql_query("SELECT * FROM `action_list_administrators`$sql_where AND `module` = '" . my_esc($module) . "' ORDER BY `id` DESC LIMIT $pages->limit");
        while ($action = mysql_fetch_assoc($q)) {
            $ank = new user($action['id_user']);
            $post = $listing->post();
            $post->title = $ank->nick();
            $post->time = vremja($action['time']);
            $post->content = output_text($action['description']);
        }
        $listing->display(__('Действия отсутствуют'));

        $pages->display('?id_user=' . $id_user . '&amp;module=' . urlencode($module) . '&amp;'); // вывод страниц
        $doc->ret(__('К модулям'), '?id_user=' . $id_user . '&amp;' . passgen());
        $doc->ret(__('Администраторы'), '?' . passgen());

        exit;
    }
    // вывод списка модулей

    $listing = new listing();

    $pages = new pages;
    $pages->posts = mysql_result(mysql_query("SELECT COUNT(DISTINCT(`module`)) FROM `action_list_administrators`$sql_where"), 0); // количество модулей
    $pages->this_page(); // получаем текущую страницу
    $q = mysql_query("SELECT `module` FROM `action_list_administrators`$sql_where GROUP BY `module` LIMIT $pages->limit");
    while ($module = mysql_fetch_assoc($q)) {
        $post = $listing->post();
        $post->title = __($module['module']);
        $post->url = '?id_user=' . $id_user . '&amp;module=' . urlencode($module['module']);
    }

    $listing->display(__('Модули отсутствуют'));

    $pages->display('?id_user=' . $id_user); // вывод страниц
    $doc->ret(__('Администраторы'), '?' . passgen());
    exit;
}
// вывод списка администраторов

$listing = new listing();
$month_time = mktime(0, 0, 0, date('n'), 0); // начало текущего месяца
$q = mysql_query("SELECT *, COUNT(`id`) AS `count` FROM `action_list_administrators` WHERE `time` > '$month_time' GROUP BY `id_user` ORDER BY `count` DESC");

$post = $listing->post();
$post->title = __('Все администраторы');
$post->url = '?id_user=all';

while ($ank_q = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $ank = new user($ank_q['id_user']);
    $post->title = $ank->nick();
    $post->counter = $ank_q['count'];
    $post->url = '?id_user=' . $ank->id;
    $post->icon($ank->icon());
}
$listing->display(__('Нет администрации'));

$doc->ret(__('Админка'), './');
?>