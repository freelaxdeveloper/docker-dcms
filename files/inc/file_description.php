<?php

defined('DCMS') or die;
$pathinfo = pathinfo($abs_path);
$dir = new files($pathinfo['dirname']);

if ($dir->group_show > $user->group) {
    $doc->access_denied(__('У Вас нет прав для просмотра файлов в данной папке'));
}
$access_write_dir = $dir->group_write <= $user->group || ($dir->id_user && $user->id == $dir->id_user);


$order_keys = $dir->getKeys();
if (!empty($_GET['order']) && isset($order_keys[$_GET['order']]))
    $order = $_GET['order'];
else
    $order = 'runame:asc';

$file = new files_file($pathinfo['dirname'], $pathinfo['basename']);

if ($file->group_show > $user->group)
    $doc->access_denied(__('У Вас нет прав для просмотра данного файла'));

$access_edit = $file->group_edit <= $user->group || ($file->id_user && $file->id_user == $user->id);

if ($access_edit && isset($_GET['act']) && $_GET['act'] == 'edit_screens')
    include 'inc/screens_edit.php';


$doc->title = __('Файл %s - скачать', $file->runame);
$doc->description = __('Скачать файл %s (%s)', $file->runame, $file->name);

if ($access_edit)
    include 'inc/file_act.php';

if ($user->group && $file->id_user != $user->id && isset($_POST['rating'])) {
    $my_rating = (int) $_POST['rating'];
    if (isset($file->ratings[$my_rating])) {
        $file->rating_my($my_rating);
        $doc->msg(__('Ваша оценка файла успешно принята'));

        header('Refresh: 1; url=?order=' . $order . '&' . passgen() . SID);
        $doc->ret(__('Вернуться'), '?order=' . $order . '&amp;' . passgen());
        exit;
    } else {
        $doc->err(__('Нет такой оценки файла'));
    }
}

if (empty($_GET['act'])) {
    $screens_count = $file->getScreensCount();

    $query_screen = (int) @$_GET['screen_num'];


    if ($screens_count) {
        if ($query_screen < 0 || $query_screen >= $screens_count)
            $query_screen = 0;

        if ($screen = $file->getScreen($doc->img_max_width(), $query_screen)) {
            echo "<img class='DCMS_photo' src='" . $screen . "' alt='" . __('Скриншот') . " $query_screen' /><br />\n";
        }

        if ($screens_count > 1) {
            $select = array();

            for ($i = 0; $i < $screens_count; $i++) {
                $select[] = array('?order=' . $order . '&amp;screen_num=' . $i, $i + 1, $query_screen == $i);
            }

            $show = new design();
            $show->assign('select', $select);
            $show->display('design.select_bar.tpl');
        }
    }


    $listing = new listing();



    if ($description = $file->description) {
        $post = $listing->post();
        $post->title = __('Описание');
        $post->icon('info');
        $post->content[] = $description;
    }

    //echo 'Описание' . ': ' . output_text($description) . "<br />\n";

    if ($title = $file->title) {
        $post = $listing->post();
        $post->title = __('Заголовок');
        $post->content[] = $title;
    }
    //echo 'Заголовок' . ': ' . for_value($title) . "<br />\n";

    if ($artist = $file->artist) {
        $post = $listing->post();
        $post->title = __('Исполнители');
        $post->content[] = $artist;
        //echo 'Исполнители' . ': ' . for_value($artist) . "<br />\n";
        $doc->keywords[] = $artist;
    }

    if ($band = $file->band) {
        $post = $listing->post();
        $post->title = __('Группа');
        $post->content[] = $band;
        //echo 'Группа' . ': ' . for_value($band) . "<br />\n";
        $doc->keywords[] = $band;
    }

    if ($album = $file->album) {
        $post = $listing->post();
        $post->title = __('Альбом');
        $post->content[] = $album;
        //echo 'Альбом' . ': ' . for_value($album) . "<br />\n";
        $doc->keywords[] = $album;
    }

    if ($year = $file->year) {
        $post = $listing->post();
        $post->title = __('Год');
        $post->content[] = $year;
        //echo __('Год') . ': ' . for_value($year) . "<br />\n";
    }
    if ($genre = $file->genre) {
        $post = $listing->post();
        $post->title = __('Жанр');
        $post->content[] = $genre;

        // echo __('Жанр') . ': ' . for_value($genre) . "<br />\n";
    }

    if ($comment = $file->comment) {
        $post = $listing->post();
        $post->title = __('Комментарий');
        $post->content[] = $comment;
        //echo __('Комментарий') . ': ' . output_text($comment) . "<br />\n";
    }

    if ($track_number = (int) $file->track_number) {
        $post = $listing->post();
        $post->title = __('Номер трека');
        $post->content[] = $track_number;
        //echo __('Номер трека') . ': ' . $track_number . "<br />\n";
    }

    if ($language = $file->language) {
        $post = $listing->post();
        $post->title = __('Язык');
        $post->content[] = $language;
        //echo __('Язык') . ': ' . for_value($language) . "<br />\n";
    }

    if ($url = $file->url) {
        $post = $listing->post();
        $post->title = __('Ссылка');
        $post->content[] = $url;
        // echo __('Ссылка') . ': ' . output_text($url) . "<br />\n";
    }

    if ($copyright = $file->copyright) {
        $post = $listing->post();
        $post->title = __('Копирайт');
        $post->content[] = $copyright;
        //echo __('Копирайт') . ': ' . output_text($copyright) . "<br />\n";
    }

    if ($vendor = $file->vendor) {
        $post = $listing->post();
        $post->title = __('Производитель');
        $post->content[] = $vendor;
        // echo __('Производитель') . ': ' . for_value($vendor) . "<br />\n";
    }

    if (($width = (int) $file->width) && ($height = (int) $file->height)) {
        $post = $listing->post();
        $post->title = __('Разрешение');
        $post->content[] = $width . 'x' . $height;
        // echo __('Разрешение') . ': ' . $width . 'x' . $height . "<br />\n";
    }

    if ($frames = (int) $file->frames) {
        $post = $listing->post();
        $post->title = __('Кол-во кадров');
        $post->content[] = $frames;
        //echo __('Кол-во кадров') . ': ' . $frames . "<br />\n";
    }

    if ($playtime_string = $file->playtime_string) {
        $post = $listing->post();
        $post->title = __('Продолжительность');
        $post->content[] = $playtime_string;
        //echo __('Продолжительность') . ': ' . for_value($playtime_string) . "<br />\n";
    }

    if (($video_bitrate = (int) $file->video_bitrate) && ($video_bitrate_mode = $file->video_bitrate_mode)) {
        $post = $listing->post();
        $post->title = __('Видео битрейт');
        $post->content[] = size_data($video_bitrate) . "/s (" . $video_bitrate_mode . ")";
        //echo __('Видео битрейт') . ': ' . size_data($video_bitrate) . "/s (" . $video_bitrate_mode . ")<br />\n";
    }

    if ($video_codec = $file->video_codec) {
        $post = $listing->post();
        $post->title = __('Видео кодек');
        $post->content[] = $video_codec;
        //echo __('Видео кодек') . ': ' . for_value($video_codec) . "<br />\n";
    }

    if ($video_frame_rate = $file->video_frame_rate) {
        $post = $listing->post();
        $post->title = __('Частота');
        $post->content[] = __('%s кадров в секунду', round($video_frame_rate / 60));
        //echo __('Частота') . ': ' . round($video_frame_rate / 60) . " кадр./сек<br />\n";
    }

    if (($audio_bitrate = (int) $file->audio_bitrate) && ($audio_bitrate_mode = $file->audio_bitrate_mode)) {
        $post = $listing->post();
        $post->title = __('Аудио битрейт');
        $post->content[] = size_data($audio_bitrate) . "/s (" . $audio_bitrate_mode . ")";
        //echo __('Аудио битрейт') . ': ' . size_data($audio_bitrate) . "/s (" . $audio_bitrate_mode . ")<br />\n";
    }

    if ($audio_codec = $file->audio_codec) {
        $post = $listing->post();
        $post->title = __('Аудио кодек');
        $post->content[] = $audio_codec;
        //echo __('Аудио кодек') . ': ' . for_value($audio_codec) . "<br />\n";
    }

    if ($file->id_user) {
        $ank = new user($file->id_user);

        $post = $listing->post();
        $post->title = __('Файл добавил');
        $post->content = $ank->nick;
        $post->url = '/profile.view.php?id=' . $ank->id;
        $post->time = vremja($file->time_add);


        //echo __("Файл добавил") . ": <a href='/profile.view.php?id=$ank->id'>{$ank->nick}</a> (" . vremja($file->time_add) . ")<br />\n";
    }

    $post = $listing->post();
    $post->title = __('Кол-во скачиваний');
    $post->content[] = intval($file->downloads) . ' ' . __(misc::number($file->downloads, 'раз', 'раза', 'раз'));


    //echo __("Кол-во скачиваний") . ": " . intval($file->downloads) . ' ' . __(misc::number($file->downloads, 'раз', 'раза', 'раз')) . "<br />\n";

    $post = $listing->post();
    $post->title = __('Размер файла');
    $post->content[] = size_data($file->size);
    //echo __("Размер файла") . ": " . size_data($file->size) . "<br />\n";

    $post = $listing->post();
    $post->title = __('Общая оценка');
    $post->content[] = $file->rating_name . ' (' . round($file->rating, 1) . '/' . $file->rating_count . ")";
    //echo __("Общая оценка") . ': ' . ' ' . $file->rating_name . ' (' . round($file->rating, 1) . '/' . $file->rating_count . ")<br />\n";


    $listing->display();

    if ($user->group && $file->id_user != $user->id) {
        $my_rating = $file->rating_my(); // мой рейтинг
        $form = new design();
        $form->assign('method', 'post');
        $form->assign('action', '?order=' . $order . '&amp;screen_num=' . $query_screen . '&amp;' . passgen());
        $elements = array();
        $options = array();
        foreach ($file->ratings AS $rating => $rating_name) {
            $options[] = array($rating, $rating_name, $rating == $my_rating);
        }
        $elements[] = array('type' => 'select', 'title' => __('Оценка файла'), 'br' => 1, 'info' => array('name' => 'rating', 'options' => $options));
        $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'save', 'value' => __('Оценить'))); // кнопка
        $form->assign('el', $elements);
        $form->display('input.form.tpl');
    }

    $smarty = new design();
    $elements = array();

    $elements[] = array('type' => 'input_text', 'br' => 0, 'title' => __('Скопировать ссылку'), 'info' => array('value' => 'http://' . $_SERVER['HTTP_HOST'] . '/files' . $file->getPath()));
    $smarty->assign('el', $elements);
    $smarty->display('input.form.tpl');

    $smarty = new design();
    $smarty->assign('method', 'get');
    $smarty->assign('action', '/files' . $file->getPath());
    $elements = array();
    $elements[] = array('type' => 'hidden', 'info' => array('name' => 'rnd', 'value' => passgen(6)));
    $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('value' => __('Скачать %s', $file->name))); // кнопка
    $smarty->assign('el', $elements);
    $smarty->display('input.form.tpl');
}


$can_write = true;
if (!$user->is_writeable) {
    $doc->msg(__('Писать запрещено'), 'write_denied');
    $can_write = false;
}
















// комменты к файлу
if ($can_write && isset($_POST['send']) && isset($_POST['message']) && $user->group) {
    $message = (string) $_POST['message'];
    $users_in_message = text::nickSearch($message);
    $message = text::input_text($message);

    if ($file->id_user && $file->id_user != $user->id && (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session']))) {
        $doc->err(__('Проверочное число введено неверно'));
    } elseif ($dcms->censure && $mat = is_valid::mat($message)) {
        $doc->err(__('Обнаружен мат: %s', $mat));
    } elseif ($message) {
        $user->balls++;
        mysql_query("INSERT INTO `files_comments` (`id_file`, `id_user`, `time`, `text`) VALUES ('$file->id','$user->id', '" . TIME . "', '" . my_esc($message) . "')");
        $doc->msg(__('Комментарий успешно оставлен'));

        $id_message = mysql_insert_id();
        if ($users_in_message) {
            for ($i = 0; $i < count($users_in_message) && $i < 20; $i++) {
                $user_id_in_message = $users_in_message[$i];
                if ($user_id_in_message == $user->id || ($file->id_user && $file->id_user == $user_id_in_message)) {
                    continue;
                }
                $ank_in_message = new user($user_id_in_message);
                if ($ank_in_message->notice_mention) {
                    $ank_in_message->mess("[user]{$user->id}[/user] упомянул" . ($user->sex ? '' : 'а') . " о Вас в комментарии к файлу [url=/files{$file->getPath()}.htm#comment{$id_message}]$file->runame[/url]");
                }
            }
        }



        $file->comments++;

        if ($file->id_user && $file->id_user != $user->id) { // уведомляем автора о комментарии
            $ank = new user($file->id_user);
            $ank->mess("[user]{$user->id}[/user] оставил" . ($user->sex ? '' : 'а') . " комментарий к Вашему файлу [url=/files{$file->getPath()}.htm]$file->runame[/url]");
        }
    } else {
        $doc->err(__('Комментарий пуст'));
    }
}

if (empty($_GET['act'])) {
    // комменты будут отображаться только когда над файлом не производится никаких действий
    if ($can_write && $user->group) {
        $smarty = new design();
        $smarty->assign('method', 'post');
        $smarty->assign('action', '?order=' . $order . '&amp;screen_num=' . $query_screen . '&amp;' . passgen());
        $elements = array();
        $elements[] = array('type' => 'textarea', 'title' => __('Комментарий'), 'br' => 1, 'info' => array('name' => 'message'));

        if ($file->id_user && $file->id_user != $user->id)
            $elements[] = array('type' => 'captcha', 'session' => captcha::gen(), 'br' => 1);

        $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'send', 'value' => __('Отправить'))); // кнопка
        $smarty->assign('el', $elements);
        $smarty->display('input.form.tpl');
    }

    if (!empty($_GET['delete_comm']) && $user->group >= $file->group_edit) {
        $delete_comm = (int) $_GET['delete_comm'];
        if (mysql_result(mysql_query("SELECT COUNT(*) FROM `files_comments` WHERE `id` = '$delete_comm' AND `id_file` = '$file->id' LIMIT 1"), 0)) {
            mysql_query("DELETE FROM `files_comments` WHERE `id` = '$delete_comm' LIMIT 1");
            $file->comments--;
            $doc->msg(__('Комментарий успешно удален'));
        }else
            $doc->err(__('Комментарий уже удален'));
    }

    //$posts = array();
    $listing = new listing();
    $pages = new pages;
    $pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `files_comments` WHERE `id_file` = '$file->id'"), 0); // количество сообщений
    $pages->this_page(); // получаем текущую страницу

    $q = mysql_query("SELECT * FROM `files_comments` WHERE `id_file` = '$file->id' ORDER BY `id` DESC LIMIT $pages->limit");
    while ($comment = mysql_fetch_assoc($q)) {
        $ank = new user($comment['id_user']);

        $post = $listing->post();
        $post->url = '/profile.view.php?id=' . $ank->id;
        $post->title = $ank->nick();
        $post->time = vremja($comment['time']);
        $post->post = output_text($comment['text']);
        $post->icon($ank->icon());

        if ($user->group >= $file->group_edit) {
            $post->action('delete', '?order=' . $order . '&amp;screen_num=' . $query_screen . '&amp;delete_comm=' . $comment['id']);
        }
    }
    $listing->display(__('Комментарии отсутствуют'));

    $pages->display('?order=' . $order . '&amp;screen_num=' . $query_screen . '&amp;'); // вывод страниц
}












// переход к рядом лежащим файлам в папке
$content = $dir->getList($order);
$files = &$content['files'];
$count = count($files);

if ($count > 1) {
    for ($i = 0; $i < $count; $i++) {
        if ($file->name == $files[$i]->name)
            $fileindex = $i;
    }

    if (isset($fileindex)) {
        $select = array();

        if ($fileindex >= 1) {
            $last_index = $fileindex - 1;
            $select[] = array('./' . urlencode($files[$last_index]->name) . '.htm?order=' . $order, for_value($files[$last_index]->runame));
        }

        $select[] = array('?order=' . $order, for_value($file->runame), true);

        if ($fileindex < $count - 1) {
            $next_index = $fileindex + 1;
            $select[] = array('./' . urlencode($files[$next_index]->name) . '.htm?order=' . $order, for_value($files[$next_index]->runame));
        }

        $show = new design();
        $show->assign('select', $select);
        $show->display('design.select_bar.tpl');
    }
}

$doc->ret($dir->runame, './?order=' . $order); // возвращение в папку
$return = $dir->ret(5); // последние 5 ссылок пути
for ($i = 0; $i < count($return); $i++) {
    $doc->ret($return[$i]['runame'], '/files' . $return[$i]['path']);
}

if ($access_edit)
    include 'inc/file_form.php';
exit;
?>