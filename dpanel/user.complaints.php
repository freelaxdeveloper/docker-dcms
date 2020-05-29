<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$doc = new document(2);
$doc->title = __('Жалобы');

if (!empty($_GET['id_ank']) && !empty($_GET['code'])) {
    $ank = new user($_GET['id_ank']);
    $code = (string) $_GET['code'];

    $codes = new menu_code('code');

    if (!$ank->id)
        $doc->err(__('Не выбран пользователь'));
    elseif (!isset($codes->menu_arr[$code]))
        $doc->err(__('Не выбрано нарушение'));
    else {
        $doc->title = __('Жалобы на "%s"', $ank->login);

        if (isset($_GET['delete'])) {
            mysql_query("UPDATE `complaints` SET `processed` = '1'  WHERE `id_ank` = '$ank->id' AND `code` = '" . my_esc($code) . "'");

            $doc->msg(__('Нарушение помечено как обработанное'));
        }


        $listing = new listing();

        $pages = new pages;
        $pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `complaints` WHERE `processed` = '0' AND `id_ank` = '$ank->id' AND `code` = '" . my_esc($code) . "'"), 0);
        $pages->this_page(); // получаем текущую страницу

        $q = mysql_query("SELECT `comment`, `link`, COUNT(*) as `count`, MAX(`time`) as `time` FROM `complaints` WHERE `processed` = '0' AND `id_ank` = '$ank->id' AND `code` = '" . my_esc($code) . "' GROUP BY `link` ORDER BY `count` DESC LIMIT $pages->limit");
        while ($c = mysql_fetch_assoc($q)) {
            $post = $listing->post();
            $post->url = 'user.ban.php?id_ank=' . $ank->id . '&amp;code=' . urlencode($code) . '&amp;link=' . urlencode($c['link']);
            $post->title = $c['count'] . ' ' . misc::number($c['count'], 'жалоба', 'жалобы', 'жалоб');
            $post->time = vremja($c['time']);
            $p = __('Ссылка на нарушение:') . ' <a' . ($dcms->browser_type == 'web' ? ' target="_blank"' : null) . ' href="' . for_value($c['link']) . '">' . for_value($c['link']) . '</a><br />';
            $p .= output_text($c['comment']);
            $post->content = $p;
            $post->action('delete', '?id_ank=' . $ank->id . '&amp;code=' . urlencode($code) . '&amp;link=' . urlencode($c['link']) . '&amp;delete');
        }

        $listing->display(__('Жалобы отсутствуют'));


        $pages->display("?id_ank=$ank->id&amp;code=" . urlencode($c['code']) . '&amp;'); // вывод страниц

        $doc->ret(__('Все жалобы'), '?');
        $doc->ret(__('Админка'), './');
        exit;
    }
}

$listing = new listing();

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(DISTINCT `id_ank`, `code`) FROM `complaints` WHERE `processed` = '0'"), 0);
$pages->this_page(); // получаем текущую страницу

$q = mysql_query("SELECT *, COUNT(*) as `count` FROM `complaints` WHERE `processed` = '0' GROUP BY `id_ank`, `code` ORDER BY `count` DESC LIMIT $pages->limit");
while ($c = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $ank = new user($c['id_ank']);
    $post->title = $ank->nick();
    $post->counter = $c['count'];
    $post->url = "?id_ank=$c[id_ank]&amp;code=" . urlencode($c['code']);
    $post->content[] = $c['code'];
    $post->content[] = __('Жалоба от %s', '[user]' . $c['id_user'] . '[/user]');
}
$listing->display(__('Жалобы отсутствуют'));

$pages->display("?");
$doc->ret(__('Админка'), './');
?>
