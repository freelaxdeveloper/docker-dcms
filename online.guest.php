<?php

include_once 'sys/inc/start.php';
$doc = new document();
$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest_online` WHERE `conversions` >= '5'"), 0);
$pages->this_page(); // получаем текущую страницу

$doc->title = __('Гости на сайте (%s)', $pages->posts);

$q = mysql_query("SELECT * FROM `guest_online` WHERE `conversions` >= '5' ORDER BY `time_start` DESC LIMIT $pages->limit");

$listing = new listing();
while ($ank = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $post->icon('guest');
    $post->title = __('Гость');
    $post->content = __("Переходов") . ": $ank[conversions]<br />";
    $post->content .= __("Браузер") . ": $ank[browser]<br />";
    $post->content .= __("IP-адрес") . ": " . long2ip($ank['ip_long']);
}
$listing->display(__('Нет гостей'));

$pages->display('?'); // вывод страниц
?>
