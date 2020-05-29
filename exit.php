<?php

$subdomain_theme_redirect_disable = true; // принудительное отключение редиректа на поддомены, соответствующие типу браузера
include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Выход');

$q = mysql_query("SELECT * FROM `users_online` WHERE `id_user` = '{$user->id}' LIMIT 1");
if (mysql_num_rows($q)) {
    $exit_user = mysql_fetch_assoc($q);
    // количество посещений
    unset($user);
    mysql_query("DELETE FROM `users_online` WHERE `id_user` = '{$exit_user['id_user']}' LIMIT 1");
}

setcookie(COOKIE_ID_USER);
setcookie(COOKIE_USER_PASSWORD);
unset($_SESSION);
session_destroy();

/* Инициализация механизма сессий  */
session_name(SESSION_NAME) or die(__('Невозможно инициализировать сессии'));
@session_start() or die(__('Невозможно инициализировать сессии'));

$user = new user();
/*
if (isset($_GET['return'])) {
    header('Refresh: 1; url=' . $_GET['return']);
} else {
    header('Refresh: 1; url=/');
}
*/
$doc->msg(__('Авторизация успешно сброшена'));

echo __("Будем рады видеть Вас снова") . "<br />\n";
?>