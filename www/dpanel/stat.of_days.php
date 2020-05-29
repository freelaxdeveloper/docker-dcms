<?php

include_once '../sys/inc/start.php';
$doc = new document(5);
$doc->title = __('Статистика (по дням)');

if (!$dcms->log_of_visits) {
    $doc->err(__('Служба ведения статистики отключена'));
}

if (isset($log_of_visits) && mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` <> '" . DAY_TIME . "' LIMIT 1"), 0)) {
    $log_of_visits->tally();
}

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_for_days`"), 0); // количество сообщений
$pages->this_page(); // получаем текущую страницу

$listing = new listing();
$q = mysql_query("SELECT * FROM `log_of_visits_for_days` ORDER BY `time_day` DESC LIMIT $pages->limit");
while ($st = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $post->title = date('d-m-Y', $st['time_day']);
    $post->icon('statistics');
    $post->content = "<table border='1' style='border-collapse: collapse'>\n";
    $post->content .= "<tr><td></td><td>WAP</td><td>PDA</td><td>iTouch</td><td>WEB</td><td>" . __('В сумме') . "</td></tr>\n";
    $post->content .= "<tr><td>" . __('Хосты') . "</td><td>$st[hosts_wap]</td><td>$st[hosts_pda]</td><td>$st[hosts_itouch]</td><td>$st[hosts_web]</td><td>" . ($st['hosts_wap'] + $st['hosts_pda'] + $st['hosts_itouch'] + $st['hosts_web']) . "</td></tr>\n";
    $post->content .= "<tr><td>" . __('Хиты') . "</td><td>$st[hits_wap]</td><td>$st[hits_pda]</td><td>$st[hits_itouch]</td><td>$st[hits_web]</td><td>" . ($st['hits_wap'] + $st['hits_pda'] + $st['hits_itouch'] + $st['hits_web']) . "</td></tr>\n";
    $post->content .= "</table>\n";
}
$listing->display(__('Сообщения отсутствуют'));

$pages->display('?');

if (!$dcms->log_of_visits) {
    $doc->act(__('Управление службами'), 'sys.settings.daemons.php');
}

$doc->ret(__('Админка'), './');
?>
