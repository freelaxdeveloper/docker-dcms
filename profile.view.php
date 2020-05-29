<?php

include_once 'sys/inc/start.php';
$doc = new document (); // инициализация документа для браузера
$doc->title = __('Анкета');

if (isset($_GET ['id'])) {
    $ank = new user((int) $_GET ['id']);
} else {
    $ank = $user;
}

if (!$ank->group)
    $doc->access_denied(__('Нет данных'));

if ($user->id && $ank->id == $user->id)
    $doc->title = __('Моя анкета');
else
    $doc->title = __('Анкета "%s"', $ank->login);

$doc->description = __('Анкета "%s"', $ank->login);
$doc->keywords [] = $ank->login;

if ($user->group && $ank->id && $user->id != $ank->id && isset($_GET ['friend'])) {
    // обработка действий с "другом"
    $q = mysql_query("SELECT * FROM `friends` WHERE `id_user` = '$user->id' AND `id_friend` = '$ank->id' LIMIT 1");
    if (mysql_num_rows($q)) {
        $friend = mysql_fetch_assoc($q);
        if ($friend ['confirm']) {
            // если Вы уже являетель другом
            if (isset($_POST ['delete'])) {
                // удаляем пользователя из друзей
                mysql_query("DELETE FROM `friends` WHERE `id_user` = '{$user->id}' AND `id_friend` = '{$ank->id}' OR `id_user` = '{$ank->id}' AND `id_friend` = '{$user->id}'");
                $doc->msg(__('Пользователь успешно удален из друзей'));
            }
        } else {
            // если не являетесь другом
            if (isset($_POST ['no'])) {
                // не принимаем предложение дружбы
                mysql_query("DELETE FROM `friends` WHERE `id_user` = '$user->id' AND `id_friend` = '$ank->id' OR `id_user` = '$ank->id' AND `id_friend` = '$user->id'");
                mysql_query("UPDATE `users` SET `friend_new_count` = `friend_new_count` - '1' WHERE `id` = '{$user->id}' LIMIT 1");

                $doc->msg(__('Предложение дружбы отклонено'));
            } elseif (isset($_POST ['ok'])) {
                // принимаем предложение дружбы
                mysql_query("UPDATE `friends` SET `confirm` = '1' WHERE `id_user` = '$user->id' AND `id_friend` = '$ank->id' LIMIT 1");
                mysql_query("UPDATE `users` SET `friend_new_count` = `friend_new_count` - '1' WHERE `id` = '{$user->id}' LIMIT 1");
                // на всякий случай пытаемся добавить поле (хотя оно уже должно быть), если оно уже есть, то дублироваться не будет
                mysql_query("INSERT INTO `friends` (`confirm`, `id_user`, `id_friend`) VALUES ('1', '$ank->id', '$user->id')");
                $doc->msg(__('Предложение дружбы принято'));
            }
        }
    } else {
        if (isset($_GET ['friend']) && isset($_POST ['add'])) {
            // предлагаем дружбу
            // отметка о запросе дружбы
            mysql_query("INSERT INTO `friends` (`confirm`, `id_user`, `id_friend`) VALUES ('0', '$ank->id', '$user->id'), ('1', '$user->id', '$ank->id')");
            mysql_query("UPDATE `users` SET `friend_new_count` = `friend_new_count` + '1' WHERE `id` = '{$ank->id}' LIMIT 1");

            $doc->msg(__('Предложение дружбы успешно отправлено'));
        }
    }
}

if ($user->group && $ank->id && $user->id != $ank->id) {
    $q = mysql_query("SELECT * FROM `friends` WHERE `id_user` = '$user->id' AND `id_friend` = '$ank->id' LIMIT 1");
    if (mysql_num_rows($q)) {
        $friend = mysql_fetch_assoc($q);
        if ($friend ['confirm']) {
            // пользователь находится в друзьях
            if (isset($_GET ['friend']) && $_GET ['friend'] == 'delete') {
                $form = new design ();
                $form->assign('method', 'post');
                $form->assign('action', "?id={$ank->id}&amp;friend&amp;" . passgen());
                $elements = array();
                $elements [] = array('type' => 'text', 'br' => 1, 'value' => output_text(__('Действительно хотите удалить пользователя "%s" из друзей?', $ank->login))); // правила
                $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'delete', 'value' => __('Да, удалить'))); // кнопка
                $form->assign('el', $elements);
                $form->display('input.form.tpl');
            }

            if (!$ank->is_friend($user))
                echo "<b>" . __('Пользователь еще не подтвердил факт Вашей дружбы') . "</b><br />";
            $doc->act(__('Удалить из друзей'), "?id={$ank->id}&amp;friend=delete");
        } else {
            // пользователь не в друзьях
            $form = new design ();
            $form->assign('method', 'post');
            $form->assign('action', "?id={$ank->id}&amp;friend&amp;" . passgen());
            $elements = array();
            $elements [] = array('type' => 'text', 'br' => 1, 'value' => output_text(__('Пользователь "%s" предлагает Вам дружбу', $ank->login))); // правила
            $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'ok', 'value' => __('Принимаю'))); // кнопка
            $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'no', 'value' => __('Не принимаю'))); // кнопка
            $form->assign('el', $elements);
            $form->display('input.form.tpl');
        }
    } else {
        if (isset($_GET ['friend']) && $_GET ['friend'] == 'add') {
            $form = new design ();
            $form->assign('method', 'post');
            $form->assign('action', "?id={$ank->id}&amp;friend&amp;" . passgen());
            $elements = array();
            $elements [] = array('type' => 'text', 'br' => 1, 'value' => output_text(__('Предложить пользователю "%s" дружбу?', $ank->login))); // правила
            $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'add', 'value' => __('Предложить'))); // кнопка
            $form->assign('el', $elements);
            $form->display('input.form.tpl');
        }
        $doc->act(__('Добавить в друзья'), "?id={$ank->id}&amp;friend=add");
    }
}

if ($ank->is_ban) {
    $ban_listing = new listing();

    $q = mysql_query("SELECT * FROM `ban` WHERE `id_user` = '$ank->id' AND `time_start` < '" . TIME . "' AND (`time_end` is NULL OR `time_end` > '" . TIME . "') ORDER BY `id` DESC");
    while ($c = mysql_fetch_assoc($q)) {
        $post = $ban_listing->post();


        $adm = new user($c ['id_adm']);

        $post->title = ($adm->group <= $user->group ? '<a href="/profile.view.php?id=' . $adm->id . '">' . $adm->nick . '</a>: ' : '') . for_value($c ['code']);

        if ($c ['time_start'] && TIME < $c ['time_start']) {
            $post->content[] = '[b]' . __('Начало действия') . ':[/b]' . vremja($c ['time_start']) . "\n";
        }
        if ($c['time_end'] === NULL) {
            $post->content[] = '[b]' . __('Пожизненная блокировка') . "[/b]\n";
        } elseif (TIME < $c['time_end']) {
            $post->content[] = __('Осталось: %s', vremja($c['time_end'])) . "\n";
        }
        if ($c['link']) {
            $post->content[] = __('Ссылка на нарушение: %s', $c['link']) . "\n";
        }

        $post->content[] = __('Комментарий: %s', $c['comment']) . "\n";
    }

    $ban_listing->display();
}


$listing = new listing();

// Аватар 
if ($path = $ank->getAvatar($doc->img_max_width())) {
    echo "<img class='DCMS_photo' src='" . $path . "' alt='" . __('Аватар %s', $ank->login) . "' /><br />\n";
}



// статус
if ($ank->group > 1) {
    $post = $listing->post();
    $post->title = $ank->group_name;
    $post->hightlight = true;
    $post->icon($ank->icon());

    //echo "<b>$ank->group_name</b>";

    $q = mysql_query("SELECT `id_adm` FROM `log_of_user_status` WHERE `id_user` = '$ank->id' ORDER BY `id` DESC LIMIT 1");
    if (mysql_num_rows($q)) {
        $adm = new user(mysql_result($q, 0));
        $post->content =  __('Назначил' . ($adm->sex ? '' : 'а')) . ' ' . __($adm->group_name) . ' "' . $adm->nick . '"';
    }
    //echo "<br />\n";
} // VIP статус
elseif ($ank->is_vip) {
    $post = $listing->post();
    $post->title = 'VIP';
    $post->url = '/faq.php?info=vip&amp;return=' . URL;
    $post->icon('vip');

    // echo '<img src="/sys/images/icons/vip.png" alt="VIP" /> <br />';
}

// реальное имя
if ($ank->realname) {
    $post = $listing->post();
    $post->title = __('Имя');
    $post->content = $ank->realname;


    //echo __('Имя') . ": {$ank->realname}<br />\n";
}
// дата рождения и возраст
if ($ank->ank_d_r && $ank->ank_m_r && $ank->ank_g_r) {
    $post = $listing->post();
    $post->title = __('Дата рождения');
    $post->content = $ank->ank_d_r . ' ' . rus_mes($ank->ank_m_r) . ' ' . $ank->ank_g_r;

    $post = $listing->post();
    $post->title = __('Возраст');
    $post->content = misc::get_age($ank->ank_g_r, $ank->ank_m_r, $ank->ank_d_r, true);


    //echo __('Дата рождения') . ': ' . $ank->ank_d_r . ' ' . rus_mes($ank->ank_m_r) . ' ' . $ank->ank_g_r . "<br />\n";
    //echo __('Возраст') . ': ' . misc::get_age($ank->ank_g_r, $ank->ank_m_r, $ank->ank_d_r, true) . "<br />\n";
} elseif ($ank->ank_d_r && $ank->ank_m_r) {

    $post = $listing->post();
    $post->title = __('День рождения');
    $post->content = $ank->ank_d_r . ' ' . rus_mes($ank->ank_m_r);

    //echo __('День рождения') . ': ' . $ank->ank_d_r . ' ' . rus_mes($ank->ank_m_r) . "<br />\n";
}


if ($ank->id) {

// папка фотоальбомов пользователей
    $photos = new files(FILES . '/.photos');
// папка альбомов пользователя
    $albums_path = FILES . '/.photos/' . $ank->id;

    if (!@is_dir($albums_path)) {
        if ($albums_dir = $photos->mkdir($ank->login, $ank->id)) {
            $albums_dir->group_show = 0;
            $albums_dir->group_write = min($ank->group, 2);
            $albums_dir->group_edit = max($ank->group, 4);
            $albums_dir->id_user = $ank->id;
            unset($albums_dir);
        }
    }

    $albums_dir = new files($albums_path);

    $photos_count ['all'] = $albums_dir->count();
    if ($photos_count ['all']) {
        $photos_count ['new'] = $albums_dir->count(NEW_TIME);

        $post = $listing->post();
        $post->title = __('Фотографии');
        $post->icon('photos');
        $post->url = '/photos/albums.php?id=' . $ank->id;
        if ($photos_count ['new'])
            $post->counter = '+' . $photos_count ['new'];


        // echo '<img src="/sys/images/icons/photos.png" alt="" /><a href="/photos/albums.php?id=' . $ank->id . '">' . __('Фотографии') . '</a> (' . $photos_count ['all'] . ')' . ($photos_count ['new'] ? ' +' . $photos_count ['new'] : null) . '<br />';
    }
}


// аська
if ($ank->icq_uin) {
    if ($ank->is_friend($user) || $ank->vis_icq) {
        $post = $listing->post();
        $post->title = 'ICQ UIN';
        $post->content = $ank->icq_uin;
        $post->icon = 'http://wwp.icq.com/scripts/online.dll?icq=' . $ank->icq_uin . '&amp;img=27';


        // echo "<img src='http://wwp.icq.com/scripts/online.dll?icq={$ank->icq_uin}&amp;img=27' alt='" . __('ICQ UIN') . "' /> {$ank->icq_uin}<br />";
    } else {

        $post = $listing->post();
        $post->title = 'ICQ UIN';
        $post->url = '/faq.php?info=hide&amp;return=' . URL;
        $post->content = __('Информация скрыта');

        // echo __('ICQ UIN') . ': <a href="/faq.php?info=hide&amp;return=' . URL . '">?</a><br />';
    }
}
// аська
if ($ank->skype) {
    if ($ank->is_friend($user) || $ank->vis_skype) {

        $post = $listing->post();
        $post->title = 'Skype';
        $post->content = $ank->skype;
        $post->icon = 'http://mystatus.skype.com/smallicon/' . $ank->skype;
        $post->url = 'skype:' . $ank->skype . '?chat';


        //echo "<img src=\"http://mystatus.skype.com/smallicon/{$ank->skype}\" width=\"16\" height=\"16\" alt=\"" . __("Мой статус") . "\" /> <a href=\"skype:{$ank->skype}?chat\">{$ank->skype}</a><br />";
    } else {

        $post = $listing->post();
        $post->title = 'Skype';
        $post->url = '/faq.php?info=hide&amp;return=' . URL;
        $post->content = __('Информация скрыта');
        //echo __('Skype') . ': <a href="/faq.php?info=hide&amp;return=' . URL . '">?</a><br />';
    }
}
// мыло
if ($ank->email) {
    $doc->keywords [] = $ank->email;

    if ($ank->is_friend($user) || $ank->vis_email) {

        $post = $listing->post();
        $post->title = 'E-mail';
        $post->content = $ank->email;
        if (preg_match("#\@(mail|bk|inbox|list)\.ru$#i", $ank->email))
            $post->icon = 'http://status.mail.ru/?' . $ank->email;
        $post->url = 'mailto:' . $ank->email;


        /*
          if (preg_match("#\@(mail|bk|inbox|list)\.ru$#i", $ank->email)) {
          echo "<img src='http://status.mail.ru/?{$ank->email}' width='13' height='13' alt='' /> <a href='mailto:{$ank->email}'>{$ank->email}</a><br />";
          } else {
          echo __("E-mail") . ": <a href='mailto:{$ank->email}'>{$ank->email}</a><br />";
          }
         */
    } else {


        $post = $listing->post();
        $post->title = 'E-mail';
        $post->url = '/faq.php?info=hide&amp;return=' . URL;
        $post->content = __('Информация скрыта');

        //echo __('E-mail') . ': <a href="/faq.php?info=hide&amp;return=' . URL . '">?</a><br />';
    }
}
// Регистрационный email
if ($ank->reg_mail) {
    if ($user->group > $ank->group) {


        $post = $listing->post();
        $post->title = __('Регистрационный E-mail');
        $post->content = $ank->reg_mail;
        if (preg_match("#\@(mail|bk|inbox|list)\.ru$#i", $ank->reg_mail))
            $post->icon = 'http://status.mail.ru/?' . $ank->reg_mail;
        $post->url = 'mailto:' . $ank->reg_mail;


        //echo __("Регистрационный E-mail") . ": <a href='mailto:{$ank->reg_mail}'>{$ank->reg_mail}</a><br />";
    }
}

if ($ank->wmid) {

    $post = $listing->post();
    $post->title = 'WMID';
    $post->content = $ank->wmid;
    $post->url = 'http://passport.webmoney.ru/asp/certview.asp?wmid=' . $ank->wmid;
    $post->image = 'http://stats.wmtransfer.com/Levels/pWMIDLevel.aspx?wmid=' . $ank->wmid . '&amp;w=35&amp;h=16';

    //echo __("WMID") . ": <a" . ($dcms->browser_type == 'web' ? " target='_blank'" : null) . " href='http://passport.webmoney.ru/asp/certview.asp?wmid=$ank->wmid'>$ank->wmid</a> BL:<img src=\"http://stats.wmtransfer.com/Levels/pWMIDLevel.aspx?wmid=$ank->wmid&amp;w=35&amp;h=16\" width=\"35\" height=\"16\" alt=\"BL\" /><br />";
}

if ($ank->is_friend($user) || $ank->vis_friends) {
    $k_friends = mysql_result(mysql_query("SELECT COUNT(*) FROM `friends` WHERE `id_user` = '$ank->id' AND `confirm` = '1'"), 0);

    $post = $listing->post();
    $post->title = __('Друзья');
    $post->url = $ank->id == $user->id ? "/my.friends.php" : "/profile.friends.php?id={$ank->id}";
    $post->counter = $k_friends;


    //echo "<a href='" . ($ank->id == $user->id ? "/my.friends.php" : "/profile.friends.php?id={$ank->id}") . "'>" . __('Друзья') . ": " . $k_friends . '</a><br />';
} else {

    $post = $listing->post();
    $post->title = __('Друзья');
    $post->url = '/faq.php?info=hide&amp;return=' . URL;
    $post->content = __('Информация скрыта');

    //echo __('Друзья') . ': <a href="/faq.php?info=hide&amp;return=' . URL . '">?</a><br />';
}

$post = $listing->post();
$post->title = __('Рейтинг');
$post->url = '/profile.reviews.php?id=' . $ank->id;
$post->counter = $ank->rating;


//echo "<a href='/profile.reviews.php?id={$ank->id}'>" . __('Рейтинг') . ": " . $ank->rating . '</a><br />';

$post = $listing->post();
$post->title = __('Баллы');
$post->counter = $ank->balls;

//echo __("Баллы") . ": {$ank->balls}<br />";

if ($ank->description) {
    $post = $listing->post();
    $post->title = __('О себе');
    $post->content[] = $ank->description;
//echo __('О себе') . ': ' . output_text($ank->description) . "<br />";
}


$post = $listing->post();
$post->title = __('Последний визит');
$post->content = vremja($ank->last_visit);

//echo __('Последний визит') . ': ' . vremja($ank->last_visit) . '<br />';

$post = $listing->post();
$post->title = __('Всего переходов');
$post->content = $ank->conversions;

//echo __("Всего переходов") . ": {$ank->conversions}<br />";

$post = $listing->post();
$post->title = __('Дата регистрации');
$post->content = date('d-m-Y', $ank->reg_date);

//echo __('Дата регистрации') . ': ' . date('d-m-Y', $ank->reg_date) . '<br />';

$q = mysql_query("SELECT `id_user` FROM `invations` WHERE `id_invite` = '$ank->id' LIMIT 1");
if (mysql_num_rows($q)) {
    $inv = new user(mysql_result($q, 0, 'id_user'));

    $post = $listing->post();
    $post->title = output_text(__('По приглашению от %s', '[user]' . $inv->id . '[/user]'));


    // echo output_text(__('По приглашению от %s', '[user]' . $inv->id . '[/user]'));
}


$listing->display();


if ($user->group && $ank->id != $user->id) {
    $doc->act(__('Написать сообщение'), "my.mail.php?id={$ank->id}");

    if ($user->group > $ank->group) {
        $doc->act(__('Доступные действия'), "/dpanel/user.actions.php?id={$ank->id}");
    }
}
if ($user->group)
    $doc->ret(__('Личное меню'), '/menu.user.php');
?>