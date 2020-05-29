<?php

$subdomain_theme_redirect_disable = true; // принудительное отключение редиректа на поддомены, соответствующие типу браузера
include_once 'sys/inc/start.php';
$doc = new document();
$doc->title = __('Авторизация');




if (isset($_GET['redirected_from']) && in_array($_GET['redirected_from'], array('wap', 'pda', 'itouch', 'web'))) {
    $subdomain_var = "subdomain_" . $_GET['redirected_from'];
    if (isset($_GET['return'])) {
        $return = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $dcms->$subdomain_var . '.' . $dcms->subdomain_main . '/login.php?cookie&return=' . urlencode($_GET['return']);
    } else {
        $return = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $dcms->$subdomain_var . '.' . $dcms->subdomain_main . '/login.php?cookie&return=' . urlencode('/');
    }
} else {
    if (isset($_GET['return'])) {
        $return = $_GET['return'];
    } else {
        $return = '/';
    }
}


if ($user->group) {
    if (isset($_GET['auth_key']) && cache::get($_GET['auth_key']) === 'request') {
        cache::set($_GET['auth_key'], array('session' => $_SESSION, 'cookie' => $_COOKIE), 60);
    }

    $doc->clean();
    header('Location: ' . $return, true, 302);
    exit;
}

$need_of_captcha = cache_aut_failture::get($dcms->ip_long);

if ($need_of_captcha && (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session']))) {
    $doc->err(__('Проверочное число введено неверно'));
} elseif (isset($_POST['login']) && isset($_POST['password'])) {
    if (!$_POST['login'])
        $doc->err(__('Введите логин'));
    elseif (!$_POST['password'])
        $doc->err(__('Введите пароль'));
    else {
        $login = (string) $_POST['login'];
        $password = (string) $_POST['password'];

        $q = mysql_query("SELECT `id`, `password` FROM `users` WHERE `login` = '" . my_esc($login) . "' LIMIT 1");

        if (!mysql_num_rows($q))
            $doc->err(__('Логин "%s" не зарегистрирован', $login));

        elseif (crypt::hash($password, $dcms->salt) !== mysql_result($q, 0, 'password')) {
            $need_of_captcha = true;
            cache_aut_failture::set($dcms->ip_long, true, 600); // при ошибке заставляем пользователя проходить капчу
            $id_user = mysql_result($q, 0, 'id');
            mysql_query("INSERT INTO `log_of_user_aut` (`id_user`,`method`,`iplong`, `time`, `id_browser`, `status`) VALUES ('$id_user','post','$dcms->ip_long','" . TIME . "','$dcms->browser_id','0')");
            $doc->err(__('Вы ошиблись при вводе пароля'));
        } else {
            $id_user = mysql_result($q, 0, 'id');
            $user_t = new user($id_user);
            if (!$user_t->group)
                $doc->err(__('Ошибка при получении профиля пользователя'));
            elseif ($user_t->a_code) {
                $doc->err(__('Аккаунт не активирован'));
            } else {
                $user = $user_t;
                cache_aut_failture::set($dcms->ip_long, false, 1);
                mysql_query("INSERT INTO `log_of_user_aut` (`id_user`,`method`,`iplong`, `time`, `id_browser`, `status`) VALUES ('$id_user','post','$dcms->ip_long','" . TIME . "','$dcms->browser_id','1')");

                if ($user->recovery_password) {
                    // если пользователь авторизовался, то ключ для восстановления ему больше не нужен
                    $user->recovery_password = '';
                }
                $_SESSION[SESSION_ID_USER] = $user->id;
                $_SESSION[SESSION_PASSWORD_USER] = $password;
                if (isset($_POST['save_to_cookie']) && $_POST['save_to_cookie']) {
                    setcookie(COOKIE_ID_USER, $user->id, TIME + 60 * 60 * 24 * 365);
                    setcookie(COOKIE_USER_PASSWORD, crypt::encrypt($password, $dcms->salt_user), TIME + 60 * 60 * 24 * 365);
                }
            }
        }
    }
} elseif (!empty($_COOKIE[COOKIE_ID_USER]) && !empty($_COOKIE[COOKIE_USER_PASSWORD])) {
    $tmp_user = new user($_COOKIE[COOKIE_ID_USER]);

    if (crypt::hash(crypt::decrypt($_COOKIE[COOKIE_USER_PASSWORD], $dcms->salt_user), $dcms->salt) === $tmp_user->password) {
        // если пользователь авторизовался, то ключ для восстановления ему больше не нужен
        if ($user->recovery_password)
            $user->recovery_password = '';
        mysql_query("INSERT INTO `log_of_user_aut` (`id_user`, `method`, `iplong`, `time`, `id_browser`, `status`) VALUES ('$tmp_user->id','cookie','$dcms->ip_long','" . TIME . "','$dcms->browser_id','1')");
        $user = $tmp_user;
        $_SESSION[SESSION_ID_USER] = $user->id;
        $_SESSION[SESSION_PASSWORD_USER] = crypt::decrypt($_COOKIE[COOKIE_USER_PASSWORD], $dcms->salt_user);
    } else {
        $need_of_captcha = true;
        cache_aut_failture::set($dcms->ip_long, true, 600); // при ошибке заставляем пользователя проходить капчу
        mysql_query("INSERT INTO `log_of_user_aut` (`id_user`, `method`, `iplong`, `time`, `id_browser`, `status`) VALUES ('$tmp_user->id','cookie','$dcms->ip_long','" . TIME . "','$dcms->browser_id','0')");
        setcookie(COOKIE_ID_USER);
        setcookie(COOKIE_USER_PASSWORD);
    }
}

if ($user->group) {
    // авторизовались успешно
    // удаляем информацию как о госте
    mysql_query("DELETE FROM `guest_online` WHERE `ip_long` = '$dcms->ip_long' AND `browser` = '" . my_esc($dcms->browser) . "'");

    if (isset($_GET['auth_key']) && cache::get($_GET['auth_key']) === 'request') {
        cache::set($_GET['auth_key'], array('session' => $_SESSION, 'cookie' => $_COOKIE), 60);
    }

    $doc->clean();
    header('Location: ' . $return, true, 302);
    exit;
}

if (isset($_GET['return'])) {
    $doc->ret('Вернуться', for_value($return));
}

$form = new form('?' . passgen() . '&amp;return=' . for_value($return));
$form->input('login', __('Логин'));
$form->password('password', __('Пароль') . ' [' . '[url=/pass.php]' . __('забыли') . '[/url]]');
$form->checkbox('save_to_cookie', __('Запомнить меня'));
if ($need_of_captcha)
    $form->captcha();
$form->button(__('Авторизация'));
$form->display();
?>
