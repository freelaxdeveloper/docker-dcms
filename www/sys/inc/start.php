<?php

// Проверяем версию PHP
version_compare(PHP_VERSION, '5.2', '>=') or die('Требуется PHP >= 5.2');

/**
 * Константы и функции, необходимые для работы движка.
 * Выделены в отдельный файл чтобы избежать дублирования кода в инсталляторе
 */
require_once dirname(__FILE__) . '/initialization.php';

/**
 * во время автоматического обновления не должно быть запросов со стороны пользователя
 */
if (cache_events::get('system.update.work')) {
    exit('Выполняется обновление системы. Пожалуйста, обновите страницу позже.');
}

/**
 * загрузка системных параметров
 * @global \dcms $dcms Основной объект системы
 */
$dcms = new dcms();

/**
 *  проверка доступности поддомена.
 *  используется при включении поддомена для определенного типа браузера
 */
if (isset($_GET['check_domain_work'])) {
    echo $dcms->check_domain_work;
    exit;
}

/**
 * переадресация на поддомен, соответствующий типу браузера
 */
if ($dcms->subdomain_theme_redirect && empty($subdomain_theme_redirect_disable)) {
    if ($_SERVER['HTTP_HOST'] === $dcms->subdomain_main) {
        // проверяем, что мы находимся на главном домене, а не на поддомене
        // свойство, в котором хранится значение поддомена для данного типа браузера
        $subdomain_var = "subdomain_" . $dcms->browser_type_auto;
        // свойство, в котором хранится парметр, отвечающий за работоспособность домена
        $subdomain_enable = "subdomain_" . $dcms->browser_type_auto . "_enable";

        if ($dcms->$subdomain_enable) {
            // проверяем, включен ли поддомен для данного типа браузера
            // переадресовываем на соответствующий поддомен
            header('Location: ' . (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $dcms->$subdomain_var . '.' . $dcms->subdomain_main . $_SERVER ['REQUEST_URI']);
            exit;
        }
    }
}


if ($_SESSION['language'] && languages::exists($_SESSION['language'])) {
    // языковой пакет из сессии
    $user_language_pack = new language_pack($_SESSION['language']);
} else if ($dcms->language && languages::exists($dcms->language)) {
    // системный языковой пакет
    $user_language_pack = new language_pack($dcms->language);
}

// этот параметр будут влиять на счетчики
if ($dcms->new_time_as_date) {
    // новые файлы, темы и т.д. будут отображаться за текущее число
    define('NEW_TIME', DAY_TIME);
} else {
    // новые файлы, темы и т.д. будут отображаться за последние 24 часа
    define('NEW_TIME', TIME - 86400);
}

/**
 * Подключение к базе данных
 */
@mysql_connect($dcms->mysql_host, $dcms->mysql_user, $dcms->mysql_pass) or die('Нет соединения с MySQL сервером');
@mysql_select_db($dcms->mysql_base) or die('Нет доступа к выбранной базе данных');
mysql_query('SET NAMES "utf8"');

if ($_SERVER['SCRIPT_NAME'] != '/sys/cron.php') {
    /**
     * Поэтапная отправка писем из очереди
     */
    mail::queue_process();

    /**
     * Запись переходов со сторонних сайтов
     * @global \log_of_referers $log_of_referers
     */
    if ($dcms->log_of_referers) {
        $log_of_referers = new log_of_referers();
    }

    /**
     * Запись посещений
     * @global log_of_visits $log_of_visits
     */
    if ($dcms->log_of_visits) {
        $log_of_visits = new log_of_visits();
    }

    /**
     * авторизация пользователя
     * @global \user $user
     */
    if (!empty($_SESSION [SESSION_ID_USER])) {
        // авторизация по сессии
        $user = new user($_SESSION [SESSION_ID_USER]);
        if ($user->password !== crypt::hash($_SESSION[SESSION_PASSWORD_USER], $dcms->salt)) {
            $user = new user(false);
            unset($_SESSION[SESSION_ID_USER]);
            unset($_SESSION[SESSION_PASSWORD_USER]);
        }
    } elseif (!empty($_COOKIE [COOKIE_ID_USER]) && !empty($_COOKIE [COOKIE_USER_PASSWORD]) && $_SERVER ['SCRIPT_NAME'] !== '/login.php' && $_SERVER ['SCRIPT_NAME'] !== '/captcha.php') {
        // авторизация по COOKIE (получение сессии, по которой пользователь авторизуется)
        header('Location: /login.php?cookie&return=' . URL);
        exit;
    } else {
        // пользователь будет являться гостем
        $user = new user(false);
    }

    /**
     * удаляем сессию пользователя, если по ней не удалось авторизоваться
     */
    if ($user->id === false && isset($_SESSION [SESSION_ID_USER])) {
        unset($_SESSION [SESSION_ID_USER]);
    }


    /**
     * обработка данных пользователя
     */
    if ($user->id !== false) {
        $user->last_visit = TIME; // запись последнего посещения
        if (AJAX) {
            // при AJAX запросе только обновляем сведения о времени последнего посещения, чтобы пользователь оставался в онлайне
            mysql_query("UPDATE `users_online` SET `time_last` = '" . TIME . "' WHERE `id_user` = '$user->id' LIMIT 1");
        } else {

            $user->conversions++; // счетчик переходов

            $q = mysql_query("SELECT * FROM `users_online` WHERE `id_user` = '{$user->id}' LIMIT 1");
            if (mysql_num_rows($q)) {
                mysql_query("UPDATE `users_online` SET `conversions` = `conversions` + '1' , `time_last` = '" . TIME . "', `id_browser` = '$dcms->browser_id', `ip_long` = '$dcms->ip_long', `request` = '" . my_esc($_SERVER ['REQUEST_URI']) . "' WHERE `id_user` = '$user->id' LIMIT 1");
            } else {
                mysql_query("INSERT INTO `users_online` (`id_user`, `time_last`, `time_login`, `request`, `id_browser`, `ip_long`) VALUES ('$user->id', '" . TIME . "', '" . TIME . "', '" . my_esc($_SERVER ['REQUEST_URI']) . "', '$dcms->browser_id', '$dcms->ip_long')");
                $user->count_visit++; // счетчик посещений
            }
        }
    } else {
        // обработка гостя
        // зачистка гостей, вышедших из онлайна
        mysql_query("DELETE FROM `guest_online` WHERE `time_last` < '" . (TIME - SESSION_LIFE_TIME) . "'");

        if (!AJAX) {
            // при ajax запросе данные о переходе засчитывать не будем

            $q = mysql_query("SELECT * FROM `guest_online` WHERE `ip_long` = '{$dcms->ip_long}' AND `browser` = '" . my_esc($dcms->browser_name) . "' LIMIT 1");
            if (mysql_num_rows($q)) {
                // повторные переходы гостя
                mysql_query("UPDATE `guest_online` SET `time_last` = '" . TIME . "', `request` = '" . my_esc($_SERVER ['REQUEST_URI']) . "', `conversions` = `conversions` + 1 WHERE  `ip_long` = '{$dcms->ip_long}' AND `browser` = '{$dcms->browser_name}' LIMIT 1");
            } else {
                // новый гость
                mysql_query("INSERT INTO `guest_online` (`ip_long`, `browser`, `time_last`, `time_start`, `request` ) VALUES ('{$dcms->ip_long}', '" . my_esc($dcms->browser_name) . "', '" . TIME . "', '" . TIME . "', '" . my_esc($_SERVER ['REQUEST_URI']) . "')");
            }
        }
    }

    $cron_time = cache_events::get('cron');
    if ($cron_time < TIME - 180) {
        misc::log('cron не настроен на сервере. вызываем вручную', 'cron');
        include H . '/sys/cron.php';
    }
    unset($cron_time);

    /**
     * при полном бане никуда кроме страницы бана нельзя
     */
    if ($user->is_ban_full && $_SERVER['SCRIPT_NAME'] != '/ban.php') {
        header('Location: /ban.php?' . SID);
        exit;
    }

    /**
     * включаем полный показ ошибок для создателя, если включено в админке
     */
    if ($dcms->debug && $user->group == groups::max() && @function_exists('ini_set')) {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', true);
    }

    /**
     * пользовательский языковой пакет
     */
    if ($user->group && $user->language != $user_language_pack->code && languages::exists($user->language)) {
        $user_language_pack = new language_pack($user->language);
    }
}
