<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Журнал авторизаций');

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_user_aut` WHERE `id_user` = '$user->id'"), 0);
$pages->this_page(); // получаем текущую страницу
$q = mysql_query("SELECT
        `log_of_user_aut`.`time` AS `time`,
        `log_of_user_aut`.`method` AS `method`,
        `log_of_user_aut`.`status` AS `status`,
        `log_of_user_aut`.`iplong` AS `iplong`,
        `browsers`.`name` AS `browser`
        FROM `log_of_user_aut`
LEFT JOIN `browsers` ON `browsers`.`id` = `log_of_user_aut`.`id_browser`
WHERE `log_of_user_aut`.`id_user` = '$user->id'
ORDER BY `time` DESC
LIMIT $pages->limit");


$listing = new listing();
while ($log = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $post->title = $log['method'] . ': ' . __($log['status'] ? 'Удачно' : 'Не удачно');
    $post->hightlight = !$log['status'];
    $post->content = output_text($log['browser'] . "\n" . long2ip($log['iplong']));
    $post->time = vremja($log['time']);
}
$listing->display(__('Журнал пуст'));

$pages->display('?'); // вывод страниц

$doc->ret(__('Личное меню'), '/menu.user.php');
?>
