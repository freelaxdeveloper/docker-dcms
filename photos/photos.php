<?php

include_once '../sys/inc/start.php';
$doc = new document ();
$doc->title = __('Фотоальбомы');



if (!empty($_GET ['id'])) {
    $ank = new user((int) $_GET ['id']);
} else {
    $ank = $user;
}

if (!$ank->group) {
    $doc->access_denied(__('Ошибка пользователя'));
}

// папка фотоальбомов пользователей
$photos = new files(FILES . '/.photos');
// папка альбомов пользователя
$albums_path = FILES . '/.photos/' . $ank->id;

if (!@is_dir($albums_path)) {
    if (!$albums_dir = $photos->mkdir($ank->login, $ank->id)) {
        $doc->access_denied(__('Не удалось создать папку под фотоальбомы пользователя'));
    }
    $albums_dir->group_show = 0;
    $albums_dir->group_write = max($ank->group, 2);
    $albums_dir->group_edit = max($ank->group, 4);
    $albums_dir->id_user = $ank->id;
    unset($albums_dir);
}

$albums_dir = new files($albums_path);

if (empty($_GET ['album']) || !$albums_dir->is_dir($_GET ['album'])) {
    $doc->err(__('Запрошеный альбом не существует'));
    $doc->ret(__('К альбомам %s', $name), 'albums.php?id=' . $ank->id);
    header('Refresh: 1; url=albums.php?id=' . $ank->id);
    exit();
}

$album_name = (string) $_GET ['album'];
$album = new files($albums_path . '/' . $album_name);
$doc->title = $album->runame;

$doc->description = __('Фотоальбом пользователя %s:%s', $ank->login, $album->runame);
$doc->keywords [] = $album->runame;
$doc->keywords [] = $ank->login;

if (!empty($_GET ['act']) && $ank->id == $user->id) {
    switch ($_GET ['act']) {
        case 'prop' :
            $doc->title .= ' - ' . __('Параметры');

            $doc->ret(__('В альбом'), '?id=' . $ank->id . '&amp;album=' . urlencode($album->name));
            exit();
        case 'photo_add' :
            $doc->title .= ' - ' . __('Выгрузка фото');

            if (!empty($_FILES ['file'])) {
                if ($_FILES ['file'] ['error']) {
                    $doc->err(__('Ошибка при загрузке'));
                } elseif (!$_FILES ['file'] ['size']) {
                    $doc->err(__('Содержимое файла пусто'));
                } elseif (!preg_match('#\.jpe?g$#ui', $_FILES ['file'] ['name'])) {
                    $doc->err(__('Неверное расширение файла'));
                } elseif (!$img = @imagecreatefromjpeg($_FILES ['file'] ['tmp_name'])) {
                    $doc->err(__('Файл не является изображением JPEG'));
                } elseif (@imagesx($img) < 128) {
                    $doc->err(__('Ширина изображения должна быть не менее 128 px'));
                } elseif (@imagesy($img) < 128) {
                    $doc->err(__('Высота изображения должна быть не менее 128 px'));
                } else {
                    if ($files_ok = $album->filesAdd(array($_FILES ['file'] ['tmp_name'] => $_FILES ['file'] ['name']))) {
                        $files_ok [$_FILES ['file'] ['tmp_name']]->id_user = $ank->id;
                        $files_ok [$_FILES ['file'] ['tmp_name']]->group_edit = max($ank->group, $album->group_write, 2);

                        unset($files_ok);
                        $doc->msg(__('Фотография "%s" успешно добавлена', $_FILES ['file'] ['name']));
                    } else {
                        $doc->err(__('Не удалось сохранить выгруженный файл'));
                    }
                }
            }

            $smarty = new design ();
            $smarty->assign('method', 'post');


            $smarty->assign('files', 1);
            $smarty->assign('action', '?id=' . $ank->id . '&amp;album=' . urlencode($album->name) . '&amp;act=photo_add&amp;' . passgen());
            $elements = array();
            $elements [] = array('type' => 'file', 'title' => __('Фотография') . ' (*.jpg)', 'br' => 1, 'info' => array('name' => 'file'));
            $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('value' => __('Выгрузить'))); // кнопка
            $smarty->assign('el', $elements);
            $smarty->display('input.form.tpl');

            $doc->ret(__('В альбом'), '?id=' . $ank->id . '&amp;album=' . urlencode($album->name));
            exit();
        case 'delete' :
            $doc->title .= ' - Удаление';

            if (!empty($_POST ['delete'])) {

                if (empty($_POST ['captcha']) || empty($_POST ['captcha_session']) || !captcha::check($_POST ['captcha'], $_POST ['captcha_session']))
                    $doc->err(__('Проверочное число введено неверно'));
                elseif ($album->delete()) {
                    $doc->msg(__('Альбом успешно удален'));
                    $doc->ret(__('Альбомы %s', $ank->login), 'albums.php?id=' . $ank->id);
                    header('Refresh: 1; url=albums.php?id=' . $ank->id . '&' . passgen());
                } else {

                    $doc->err(__('Не удалось удалить альбом'));
                    $doc->ret(__('В альбом'), '?id=' . $ank->id . '&amp;album=' . urlencode($album->name));
                    $doc->ret(__('Альбомы %s', $ank->login), 'albums.php?id=' . $ank->id);
                    header('Refresh: 1; url=?id=' . $ank->id . '&album=' . urlencode($album->name) . '&' . passgen());
                }

                exit();
            }

            $smarty = new design ();
            $smarty->assign('method', 'post');
            $smarty->assign('action', '?id=' . $ank->id . '&amp;album=' . urlencode($album->name) . '&amp;act=delete&amp;' . passgen());
            $elements = array();
            $elements [] = array('type' => 'captcha', 'session' => captcha::gen(), 'br' => 1);
            $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'delete', 'value' => __('Удалить альбом'))); // кнопка
            $smarty->assign('el', $elements);
            $smarty->display('input.form.tpl');

            $doc->ret(__('В альбом'), '?id=' . $ank->id . '&amp;album=' . urlencode($album->name));
            exit();
    } // switch
}

$list = $album->getList('time_add:desc'); // получение содержимого папки альбома
$files = $list ['files']; // получение только файлов

$pages = new pages ();
$pages->posts = count($files);
$pages->this_page();
$start = $pages->my_start();
$end = $pages->end();


$listing = new listing();
for ($i = $start; $i < $end && $i < $pages->posts; $i++) {
    $post = $listing->post();
    $post->image = $files [$i]->image();
    $post->url = "photo.php?id=$ank->id&amp;album=" . urlencode($album->name) . "&amp;photo=" . urlencode($files [$i]->name);
    $post->title = for_value($files [$i]->runame);


    if ($comments = $files [$i]->comments) {
        $post->content .= __('%s комментари' . misc::number($comments, 'й', 'я', 'ев'), $comments) . "\n";
    }

    if ($properties = $files [$i]->properties) {
        // Параметры файла (только основное)
        $post->content .= $properties . "\n";
    }

    $post->content = output_text($post->content);
}

$listing->display(__('Фотографии отсутствуют'));
$pages->display('?id=' . $ank->id . '&amp;album=' . urlencode($album->name) . '&amp;'); // вывод страниц




if ($ank->id == $user->id) {
    $doc->act(__('Выгрузить фото'), '?id=' . $ank->id . '&amp;album=' . urlencode($album->name) . '&amp;act=photo_add');
    $doc->act(__('Параметры'), '?id=' . $ank->id . '&amp;album=' . urlencode($album->name) . '&amp;act=prop');
    $doc->act(__('Удалить альбом'), '?id=' . $ank->id . '&amp;album=' . urlencode($album->name) . '&amp;act=delete');
}

$doc->ret(__('Альбомы %s', $ank->login), 'albums.php?id=' . $ank->id);
?>