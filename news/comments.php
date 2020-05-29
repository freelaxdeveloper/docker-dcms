<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Комментарии к новости');
$doc->ret(__('Все новости'), './');

$id = (int) @$_GET['id'];

$q = mysql_query("SELECT * FROM `news` WHERE `id` = '$id' LIMIT 1");

if (!mysql_num_rows($q))
    $doc->access_denied(__('Новость не найдена или удалена'));

$news = mysql_fetch_assoc($q);


$listing = new listing();
$post = $listing->post();
$ank = new user((int) $news['id_user']);


$post->icon('news');
$post->content = output_text($news['text']);
$post->title = for_value($news['title']);
$post->time = vremja($news['time']);
$post->bottom = '<a href="/profile.view.php?id=' . $news['id_user'] . '">' . $ank->nick() . '</a>';

if ($user->group >= max($ank->group, 4)) {
    if (!$news['sended']) {
        $post->action('send', "news.send.php?id=$news[id]");
    }
    $post->action('edit', "news.edit.php?id=$news[id]"); // редактирование
    $post->action('delete', "news.delete.php?id=$news[id]"); // удаление
}
$listing->display();

$ank = new user($news['id_user']);

$can_write = true;
if (!$user->is_writeable) {
    $doc->msg(__('Писать запрещено'), 'write_denied');
    $can_write = false;
}

$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `news_comments` WHERE `id_news` = '$news[id]'"), 0); // количество сообщений
$pages->this_page(); // получаем текущую страницу


if ($can_write) {

    if (isset($_POST['send']) && isset($_POST['comment']) && $user->group) {

        $text = (string) $_POST['comment'];
        $users_in_message = text::nickSearch($text);
        $text = text::input_text($text);


        if ($dcms->censure && $mat = is_valid::mat($text))
            $doc->err(__('Обнаружен мат: %s', $mat));
        elseif ($text) {
            $user->balls++;
            mysql_query("INSERT INTO `news_comments` (`id_news`, `id_user`, `time`, `text`) VALUES ('$news[id]', '$user->id', '" . TIME . "', '" . my_esc($text) . "')");
            header('Refresh: 1; url=?id=' . $id . '&' . passgen());
            $doc->ret(__('Вернуться'), '?id=' . $id . '&amp;' . passgen());
            $doc->msg(__('Комментарий успешно отправлен'));

            $id_message = mysql_insert_id();


            if ($users_in_message) {
                for ($i = 0; $i < count($users_in_message) && $i < 20; $i++) {
                    $user_id_in_message = $users_in_message[$i];
                    if ($user_id_in_message == $user->id) {
                        continue;
                    }
                    $ank_in_message = new user($user_id_in_message);
                    if ($ank_in_message->notice_mention) {
                        $ank_in_message->mess("[user]{$user->id}[/user] упомянул" . ($user->sex ? '' : 'а') . " о Вас в [url=/news/comments.php?id={$news['id']}#comment{$id_message}]комментарии[/url] к новости");
                    }
                }
            }



            exit;
        } else {
            $doc->err(__('Комментарий пуст'));
        }
    }

    if ($user->group) {
        $smarty = new design();
        $smarty->assign('method', 'post');
        $smarty->assign('action', '?id=' . $id . '&amp;page=' . $pages->this_page . '&amp;' . passgen());
        $elements = array();
        $elements[] = array('type' => 'textarea', 'title' => __('Комментарий'), 'br' => 1, 'info' => array('name' => 'comment'));
        $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'send', 'value' => __('Отправить'))); // кнопка
        $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'refresh', 'value' => __('Обновить'))); // кнопка
        $smarty->assign('el', $elements);
        $smarty->display('input.form.tpl');
    }
}


$q = mysql_query("SELECT * FROM `news_comments` WHERE `id_news` = '$news[id]' ORDER BY `id` DESC LIMIT $pages->limit");

$listing = new listing();
while ($message = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $ank = new user($message['id_user']);
    $post->title = $ank->nick();
    $post->url = '/profile.view.php?id=' . $ank->id;
    $post->icon($ank->icon());
    $post->time = vremja($message['time']);

    if ($user->group >= 2) {
        $post->action('delete', "comment.delete.php?id=$message[id]&amp;return=" . URL);
    }

    $post->content = output_text($message['text']);
}

$listing->display(__('Комментарии отсутствуют'));

$pages->display('?id=' . $id . '&amp;'); // вывод страниц
?>
