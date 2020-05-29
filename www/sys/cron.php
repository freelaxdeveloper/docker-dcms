<?php

if (defined('DCMS')) {
    $cron_pseudo = true;
} else {
    $cron_pseudo = false;
    require_once dirname(__FILE__) . '/inc/start.php';
}

$cron_time = cache_events::get('cron');
if ($cron_time && $cron_time > TIME - 10) {
    misc::log('Слишком частый вызов CRON', 'cron');
} else {
    cache_events::set('cron', TIME, 200);
    misc::log('start', 'cron');

    if (!$cron_pseudo) {
        /**
         * отправка всех писем из очереди
         */
        mail::queue_process(true);
        misc::log('Очередь писем обработана', 'cron');
    }


    /**
     * Подведение итогов посещаемости
     */
    if ($log_of_visits && !cache_events::get('log_of_visits')) {
        cache_events::set('log_of_visits', true, mt_rand(82800, 86400));
        $log_of_visits->tally(); // подведение итогов        
    }
    misc::log('Итоги посещаемости подведены', 'cron');

    /**
     * Автоматическое обновление системы
     * @todo Надо бы выделить в отдельный файл, вызываемый cron`ом
     */
    if ($dcms->update_auto && $dcms->update_auto_time && !cache_events::get('system.update.auto')) {
        cache_events::set('system.update.auto', true, $dcms->update_auto_time);
        include H . '/sys/inc/update.php';

        $update = new update();
        if (version_compare($update->version, $dcms->version, '>')) {

            if ($dcms->update_auto == 2 && @function_exists('ignore_user_abort') && @function_exists('set_time_limit')) {
                if ($update->start()) {
                    // новая версия установлена
                    $mess = __('Обновление DCMS (с %s по %s) успешно выполнено', $dcms->version, $update->version);
                } else {
                    // при установке новой версии возникла ошибка
                    $mess = __('При обновлении DCMS (с %s по %s) произошла ошибка', $dcms->version, $update->version);
                }
            } else {
                $mess = __('Вышла новая версия DCMS: %s. [url=/dpanel/sys.update.php]Обновить[/url]', $update->version);
            }

            $admins = groups::getAdmins();
            foreach ($admins AS $admin) {
                $admin->mess($mess);
            }
        }
    }
    misc::log('Модуль обновления системы отработал', 'cron');

    /**
     * очистка от устаревших временных файлов (чтобы не забивалась папка sys/tmp)
     * @todo Надо бы выделить в отдельный файл, вызываемый cron`ом
     */
    if (!cache_events::get('clear_tmp_dir')) {
        cache_events::set('clear_tmp_dir', true, mt_rand(82800, 86400));
        misc::log('Запускаем удаление временных файлов', 'system.tmp');
        filesystem::deleteOldTmpFiles();
        misc::log('Удаление временных файлов завершено', 'system.tmp');
    }
    misc::log('Удаление временных файлов обработано', 'cron');

    /**
     * удаление пользователей, вышедших из онлайна (раз в 30 сек)
     */
    if (!cache_events::get('clear_users_online')) {
        cache_events::set('clear_users_online', true, 30);
        mysql_query("DELETE FROM `users_online` WHERE `time_last` < '" . (TIME - SESSION_LIFE_TIME) . "'");
    }
    misc::log('Удаление пользователей вышедших из онлайна завершено', 'cron');

    /**
     * очистка от пользователей, которые не подтвердили регистрацию в течении суток.
     * @todo Надо бы выделить в отдельный файл, вызываемый cron`ом
     */
    if ($dcms->clear_users_not_verify && !cache_events::get('clear_users_not_verify')) {
        cache_events::set('clear_users_not_verify', true, mt_rand(82800, 86400));

        $q = mysql_query("SELECT `id` FROM `users` WHERE `a_code` <> '' AND `reg_date` < '" . (TIME - 86400) . "'");
        if ($count_delete = mysql_num_rows($q)) {
            misc::log('Будет удалено неактивированных пользователей: ' . $count_delete, 'system.users');
            while ($u = mysql_fetch_assoc($q)) {
                misc::user_delete($u['id']);
            }
        }
    }

    misc::log('Очистка пользователей, не подтвердивших регистрацию', 'cron');
    misc::log('finish' . "\r\n", 'cron');

    /**
     * Архивация log файлов объемом более 1MB
     */
    if (!cache_events::get('log_archive')) {
        cache_events::set('log_archive', true, mt_rand(82800, 86400));
        $log_files = (array) @glob(H . '/sys/logs/*.log');
        foreach ($log_files AS $path) {
            if (filesize($path) < 1048576)
                continue;
            $filename = basename($path, '.log');
            $zip_file = H . '/sys/logs/' . $filename . '_' . date("Y.m.d_H.i") . '.zip';
            $zip = new PclZip($zip_file);
            $zip->create($path, PCLZIP_OPT_REMOVE_ALL_PATH);
            unset($zip);
            @unlink($path);
        }
    }
}

unset($cron_time);