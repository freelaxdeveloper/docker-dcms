<?php

include_once '../sys/inc/start.php';
$doc = new document(5);
$doc->title = __('Статистика (сегодня)');

$browser_types = array('wap', 'pda', 'itouch', 'web');

if (!$dcms->log_of_visits) {
    $doc->err(__('Служба ведения статистики отключена'));
    $doc->act(__('Управление службами'), 'sys.settings.daemons.php');
}


$hits = array();
$hits['wap'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` = '" . DAY_TIME . "' AND `browser_type` = 'wap'"), 0);
$hits['pda'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` = '" . DAY_TIME . "' AND `browser_type` = 'pda'"), 0);
$hits['itouch'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` = '" . DAY_TIME . "' AND `browser_type` = 'itouch'"), 0);
$hits['web'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` = '" . DAY_TIME . "' AND `browser_type` = 'web'"), 0);


$hosts = array();
$hosts['wap'] = mysql_result(mysql_query("SELECT COUNT(DISTINCT `iplong` , `id_browser`) FROM `log_of_visits_today` WHERE `time` = '" . DAY_TIME . "' AND `browser_type` = 'wap'"), 0);
$hosts['pda'] = mysql_result(mysql_query("SELECT COUNT(DISTINCT `iplong` , `id_browser`) FROM `log_of_visits_today` WHERE `time` = '" . DAY_TIME . "' AND `browser_type` = 'pda'"), 0);
$hosts['itouch'] = mysql_result(mysql_query("SELECT COUNT(DISTINCT `iplong` , `id_browser`) FROM `log_of_visits_today` WHERE `time` = '" . DAY_TIME . "' AND `browser_type` = 'itouch'"), 0);
$hosts['web'] = mysql_result(mysql_query("SELECT COUNT(DISTINCT `iplong` , `id_browser`) FROM `log_of_visits_today` WHERE `time` = '" . DAY_TIME . "' AND `browser_type` = 'web'"), 0);

if (isset($log_of_visits) && mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` <> '" . DAY_TIME . "' LIMIT 1"), 0)) {
    $log_of_visits->tally();
}

$listing = new listing();
$post = $listing->post();
$post->title = __('Кол-во переходов');
$post->icon('info');
$post->hightlight = true;

foreach ($browser_types AS $b_type) {
    $post = $listing->post();
    $post->title = strtoupper($b_type);
    $post->content = __('%s переход' . misc::number($hits[$b_type], '', 'а', 'ов'), $hits[$b_type]);
}

$post = $listing->post();
$post->title = __('Всего переходов');
$post->content = __('%s переход' . misc::number(array_sum($hits), '', 'а', 'ов'), array_sum($hits));


$listing->display();


$listing = new listing();
$post = $listing->post();
$post->title = __('Уникальные посетители');
$post->icon('info');
$post->hightlight = true;

foreach ($browser_types AS $b_type) {
    $post = $listing->post();
    $post->title = strtoupper($b_type);
    $post->content = __('%s посетител' . misc::number($hosts[$b_type], 'ь', 'я', 'ей'), $hosts[$b_type]);
}

$post = $listing->post();
$post->title = __('Всего посетителей');
$post->content = __('%s посетител' . misc::number(array_sum($hosts), 'ь', 'я', 'ей'), array_sum($hosts));



$listing->display();

$doc->ret(__('Админка'), './');
?>
