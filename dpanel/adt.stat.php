<?php

include_once '../sys/inc/start.php';
$doc = new document(5);
$doc->title = __('Статистика по рекламе');
$browser_types = array('wap', 'pda', 'itouch', 'web');


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=adt.settings.php');
    $doc->ret(__('Реклама и баннеры'), 'adt.php');
    $doc->ret(__('Админка'), '/dpanel/');
    $doc->err(__('Ошибка выбора рекламы'));
    exit;
}
$id_adt = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `advertising` WHERE `id` = '$id_adt'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=adt.php?id=' . $id_adt);
    $doc->ret(__('Вернуться'), 'adt.php?id=' . $id_adt);
    $doc->ret(__('Реклама и баннеры'), 'adt.php');
    $doc->ret(__('Админка'), '/dpanel/');
    $doc->err(__('Рекламная позиция не найдена'));
    exit;
}

$adt = mysql_fetch_assoc($q);


$listing = new listing();

$post = $listing->post();
$post->icon('adt');
$post->title = for_value($adt['name']);
$post->hightlight = true;


if ($adt['time_create']) {
    $post = $listing->post();
    $post->title = __('Дата создания');
    $post->content = vremja($adt['time_create']);
}

$post = $listing->post();
$post->title = __('Начало показа');
if (!$adt['time_start']) {
    $post->content = __('Нет данных');
} elseif ($adt['time_start'] > TIME) {
    $post->hightlight = true;
    $post->content = vremja($adt['time_start']);
} else {
    $post->content = vremja($adt['time_start']);
}

$post = $listing->post();
$post->title = __('Конец показа');
if (!$adt['time_end'])
    $post->content = __('Бесконечный показ');
else
    $post->content = vremja($adt['time_end']);

$listing->display();


$listing = new listing();
$post = $listing->post();
$post->title = __('Показы (с времени запуска)');
$post->icon('info');
$post->hightlight = true;

foreach ($browser_types AS $b_type) {
    $key = 'count_show_' . $b_type;
    $post = $listing->post();
    $post->title = strtoupper($b_type);
    $post->content = __('%s показ' . misc::number($adt[$key], '', 'а', 'ов'), $adt[$key]);
}

$listing->display();


$listing = new listing();
$post = $listing->post();
$post->title = __('Переходы (с времени запуска)');
$post->icon('info');
$post->hightlight = true;

foreach ($browser_types AS $b_type) {
    $key = 'count_out_' . $b_type;
    $post = $listing->post();
    $post->title = strtoupper($b_type);
    $post->content = __('%s переход' . misc::number($adt[$key], '', 'а', 'ов'), $adt[$key]);
}

$listing->display();


$doc->ret(__('Вернуться'), "adt.php?id=$adt[space]");
$doc->ret(__('Рекламные площадки'), 'adt.php');
$doc->ret(__('Админка'), '/dpanel/');
?>
