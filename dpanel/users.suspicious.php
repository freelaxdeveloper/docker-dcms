<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$doc = new document(4);
$doc->title = __('Подозрительные пользователи');


if (!empty($_GET['approve'])) {
    $app = (int) $_GET['approve'];
    if (@mysql_result(mysql_query("SELECT COUNT(*) FROM `users_suspicion` WHERE `id_user` = '$app'"), 0)) {
        mysql_query("DELETE FROM `users_suspicion` WHERE `id_user` = '$app' LIMIT 1");
        $ank = new user($app);
        $doc->msg(__('Пользователь %s успешно одобрен', $ank->login));
    }
}


if (isset($_GET['id'])) {
    $ank = new user((int) $_GET['id']);
    if (!$ank->id) {
        $doc->err(__('Пользователь не найден'));
        $doc->ret(__('Подозрительные пользователи'), '?');
        $doc->ret(__('Админка'), '/dpanel/');
        exit;
    }


    $q = mysql_query("SELECT *  FROM `users_suspicion` WHERE `id_user` = '$ank->id'");
    if (!mysql_num_rows($q)) {
        $doc->err(__('Выбранный пользователь отсутствует в списке подозрительных'));
        $doc->ret(__('Подозрительные пользователи'), '?');
        $doc->ret(__('Админка'), '/dpanel/');
        exit;
    }
    $sus = mysql_fetch_assoc($q);
    $listing = new listing();

    $post = $listing->post();


    $post->title = $ank->nick();
    $post->icon($ank->icon());
    $post2 = __('E-mail: %s', $ank->reg_mail) . "\n";
    $post2 .= __('Фраза: %s', $sus['text']);
    $post->content = output_text($post2);




    $post = $listing->post();
    $post->icon('approve');
    $post->title = __('Подтвердить регистрацию');
    $post->url = "?approve=$ank->id";


    $post = $listing->post();
    $post->icon('shit');
    $post->title = __('Забанить пользователя');
    $post->url = "user.ban.php?id_ank=$ank->id";

    $post = $listing->post();
    $post->icon('delete');
    $post->title = __('Удалить пользователя');
    $post->url = "user.delete.php?id_ank=$ank->id";



    $listing->display();
    $doc->ret(__('Подозрительные пользователи'), '?');
    $doc->ret(__('Админка'), '/dpanel/');
    exit;
}



$listing = new listing();

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*)  FROM `users_suspicion`"), 0); // количество постов
$pages->this_page(); // получаем текущую страницу

$q = mysql_query("SELECT *  FROM `users_suspicion` ORDER BY `id_user` ASC LIMIT $pages->limit");
while ($sus = mysql_fetch_assoc($q)) {
    $ank = new user($sus['id_user']);

    $post = $listing->post();

    $post->url = '?id=' . $ank->id;
    $post->title = $ank->nick();
    $post->icon($ank->icon());

    $post2 = __('E-mail: %s', $ank->reg_mail) . "\n";
    $post2 .= __('Фраза: %s', $sus['text']);

    $post->content = output_text($post2);
}

$listing->display(__('Нет подозрительных пользователей'));


$pages->display('?'); // вывод страниц
$doc->ret(__('Админка'), '/dpanel/');
?>
