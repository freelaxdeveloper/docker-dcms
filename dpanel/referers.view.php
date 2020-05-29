<?php

include_once '../sys/inc/start.php';
$doc = new document(5);
$doc->title = __('Рефералы');

if (isset($_GET['id_site'])) {
    $id = (string) $_GET['id_site'];

    $q = mysql_query("SELECT * FROM `log_of_referers_sites` WHERE `id` = '$id' LIMIT 1");

    if (!mysql_num_rows($q)) {
        header('Refresh: 1; url=?');
        $doc->ret('Вернуться', '?');
        $doc->err(__('Данные о сайте отсутствуют'));
        exit;
    }

    $site = mysql_fetch_assoc($q);

    $doc->title = __('Рефералы с сайта "%s"', $site['domain']);

    $listing = new listing();
    $pages = new pages;
    $pages->posts = mysql_result(mysql_query("SELECT COUNT(DISTINCT `full_url`) FROM `log_of_referers` WHERE `id_site` = '$id'"), 0);
    $pages->this_page(); // получаем текущую страницу
    $q = mysql_query("SELECT `full_url`, COUNT(*) AS `count`, MAX(`time`) AS `time` FROM `log_of_referers` WHERE `id_site` = '$id' GROUP BY `full_url` ORDER BY `time` DESC LIMIT $pages->limit");
    while ($ref = mysql_fetch_assoc($q)) {
        $post = $listing->post();
        $post->title = vremja($ref['time']);
        $post->content[] = $ref['full_url'];
        $post->counter = $ref['count'];
    }
    $listing->display(__('Рефералы отсутствуют'));

    $pages->display("?id_site=$id&amp;"); // вывод страниц
    $doc->ret(__('Все рефералы'), '?');
    $doc->ret(__('Админка'), '/dpanel/');
    exit;
}

if (!$dcms->log_of_referers)
    $doc->err(__('Служба записи рефералов не запущена'));

switch (@$_GET['order']) {
    case 'count':$filter = 'count';
        $order = "`count` DESC";
        break;
    case 'domain':$filter = 'domain';
        $order = "`domain` ASC";
        break;
    default:$filter = 'time';
        $order = '`time` DESC';
        break;
}

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_referers_sites`"), 0);
$pages->this_page(); // получаем текущую страницу
//
//
// меню сортировки
$ord = array();
$ord[] = array("?order=time&amp;page={$pages->this_page}", __('Последние'), $filter == 'time');
$ord[] = array("?order=count&amp;page={$pages->this_page}", __('Переходы'), $filter == 'count');
$ord[] = array("?order=domain&amp;page={$pages->this_page}", __('Адрес'), $filter == 'domain');
$or = new design();
$or->assign('order', $ord);
$or->display('design.order.tpl');

$listing = new listing();

$q = mysql_query("SELECT * FROM `log_of_referers_sites` ORDER BY $order LIMIT $pages->limit");
while ($ref = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $post->title = output_text($ref['domain']);
    $post->url = '?id_site=' . $ref['id'];
    $post->time = vremja($ref['time']);
    $post->counter = $ref['count'];
}

$listing->display(__('Рефералы отсутствуют'));

$pages->display("?order=$filter&amp;"); // вывод страниц

if (!$dcms->log_of_referers) {
    $doc->act(__('Управление службами'), 'sys.daemons.php');
}
$doc->ret(__('Админка'), './');
?>