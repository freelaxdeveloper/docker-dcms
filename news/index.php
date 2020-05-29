<?php

include_once '../sys/inc/start.php';
$doc = new document();

$doc->title = __('Наши новости');

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `news`"), 0); // количество сообщений
$pages->this_page(); // получаем текущую страницу

$q = mysql_query("SELECT * FROM `news` ORDER BY `id` DESC LIMIT $pages->limit");


$listing = new listing();
while ($news = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $ank = new user((int) $news['id_user']);


    $post->icon('news');
    $post->content = output_text($news['text']);
    $post->title = for_value($news['title']);
    $post->url = 'comments.php?id=' . $news['id'];
    $post->time = vremja($news['time']);
    $post->bottom = '<a href="/profile.view.php?id=' . $news['id_user'] . '">' . $ank->nick() . '</a>';

    if ($user->group >= max($ank->group, 4)) {
        if (!$news['sended']) {
            $post->action('send', "news.send.php?id=$news[id]");
        }
        $post->action('edit', "news.edit.php?id=$news[id]"); // редактирование
        $post->action('delete', "news.delete.php?id=$news[id]"); // удаление
    }
}

$listing->display(__('Новости отсутствуют'));

$pages->display('?'); // вывод страниц

if ($user->group >= 4) {
    $doc->act(__('Добавить новость'), 'news.add.php');
}
?>
