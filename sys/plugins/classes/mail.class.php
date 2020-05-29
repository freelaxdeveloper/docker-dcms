<?php

/**
 * Формирование очереди писем email и поэтапная отправка.
 */
abstract class mail {

    /**
     * отправка писем из очереди
     * @param boolean $all Отправка сразу всех писем
     * @return boolean
     */
    static function queue_process($all = false) {
        // кто-то уже занялся отправкой сообщений
        if (!$all && cache_events::get('mail.send_is_process')) {
            return false;
        }
        // остальные запросы пусть пропускают отправку
        cache_events::set('mail.send_is_process', true, 5);

        $limit = $all ? '' : ' LIMIT 10';
        $q = mysql_query("SELECT * FROM `mail_queue`" . $limit);
        if (!mysql_num_rows($q)) {
            return false;
        }

        while ($queue = mysql_fetch_assoc($q)) {
            if (function_exists('set_time_limit')) {
                @set_time_limit(30);
            }

            // другие запросы не должны мешать отправке текущих сообщений
            cache_events::set('mail.send_is_process', true, 30);
            if (mail::send($queue ['to'], $queue ['title'], $queue ['content'])) {
                mysql_query("DELETE FROM `mail_queue` WHERE `id` = '{$queue['id']}' LIMIT 1");
            }
        }
        // разрешаем другим запросам отправлять сообщения
        cache_events::set('mail.send_is_process', false);
        return true;
    }

    /**
     * Отправка Email или поставнока в очередь, если писем несколько
     * @param mixed $toi Адресат или массив адресатов
     * @param string $title заголовок сообщения
     * @param string $content Содержимое письма
     * @return boolean
     */
    static function send($toi, $title, $content) {
        // если сообщение одно, то отправляем сразу
        if (is_string($toi)) {
            return self::sendOfMail($toi, $title, $content);
        }


        // если сообщений несколько, то ставим в очередь
        $toi = (array) $toi;

        if (!$toi) {
            return false;
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(min(600, max(30, count($toi) / 2)));
        }
        foreach ($toi as $to) {
            mysql_query("INSERT INTO `mail_queue` (`to`, `title`, `content`) VALUES ('" . my_esc($to) . "', '" . my_esc($title) . "', '" . my_esc($content) . "')");
        }


        return true;
    }

    /**
     * Непосредственная отправка сообщения
     * @global dcms $dcms
     * @param string $to
     * @param string $title
     * @param string $content
     * @return boolean
     */
    static function sendOfMail($to, $title, $content) {
        global $dcms;
        // отправка сообщения функцией mail
        $EOL = "\r\n";
        $headers = "From: \"" . $dcms->sitename . "\" <dcms@{$_SERVER['HTTP_HOST']}>$EOL";
        $headers .= "Subject: $title$EOL";
        $headers .= "Mime-Version: 1.0$EOL";
        $headers .= "Content-Type: text/html; charset=\"utf-8\"$EOL";
        return mail($to, '=?utf-8?B?' . base64_encode($title) . '?=', $content, $headers);
    }

}

?>
