<?php

/**
 * Запись посещений
 */
class log_of_visits {

    function __construct() {
        global $dcms;
        if (!cache_log_of_visits::get($dcms->ip_long)) {
            mysql_query("INSERT INTO `log_of_visits_today` (`time`, `browser_type`, `id_browser`, `iplong`) VALUES ('" . DAY_TIME . "', '{$dcms->browser_type}', '{$dcms->browser_id}', '{$dcms->ip_long}')");
            cache_log_of_visits::set($dcms->ip_long, true, 1);
        }
    }

    // подведение итогов посещений по дням
    function tally() {
        mysql_query("LOCK TABLES `log_of_visits_today` WRITE READ, `log_of_visits_for_days` WRITE READ");
        // запрашиваем дни, которые есть в базе исключая текущий
        $q = mysql_query("SELECT DISTINCT `time`  FROM `log_of_visits_today` WHERE `time` <> '" . DAY_TIME . "'");
        while ($day = mysql_fetch_assoc($q)) {
            $hits['wap'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` = '$day[time]' AND `browser_type` = 'wap'"), 0);
            $hits['pda'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` = '$day[time]' AND `browser_type` = 'pda'"), 0);
            $hits['itouch'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` = '$day[time]' AND `browser_type` = 'itouch'"), 0);
            $hits['web'] = mysql_result(mysql_query("SELECT COUNT(*) FROM `log_of_visits_today` WHERE `time` = '$day[time]' AND `browser_type` = 'web'"), 0);

            $hosts['wap'] = mysql_result(mysql_query("SELECT COUNT(DISTINCT `iplong` , `id_browser`) FROM `log_of_visits_today` WHERE `time` = '$day[time]' AND `browser_type` = 'wap'"), 0);
            $hosts['pda'] = mysql_result(mysql_query("SELECT COUNT(DISTINCT `iplong` , `id_browser`) FROM `log_of_visits_today` WHERE `time` = '$day[time]' AND `browser_type` = 'pda'"), 0);
            $hosts['itouch'] = mysql_result(mysql_query("SELECT COUNT(DISTINCT `iplong` , `id_browser`) FROM `log_of_visits_today` WHERE `time` = '$day[time]' AND `browser_type` = 'itouch'"), 0);

            $hosts['web'] = mysql_result(mysql_query("SELECT COUNT(DISTINCT `iplong` , `id_browser`) FROM `log_of_visits_today` WHERE `time` = '$day[time]' AND `browser_type` = 'web'"), 0);
            mysql_query("INSERT INTO `log_of_visits_for_days` (`time_day`, `hits_web`,`hosts_web`,`hits_wap`,`hosts_wap`,`hits_pda`,`hosts_pda`,`hits_itouch`,`hosts_itouch`) VALUES ('$day[time]','$hits[web]','$hosts[web]','$hits[wap]','$hosts[wap]','$hits[pda]','$hosts[pda]','$hits[itouch]','$hosts[itouch]')");
        }
        mysql_query("DELETE FROM `log_of_visits_today` WHERE `time` <> '" . DAY_TIME . "'");
        // оптимизация таблиц после удаления данных
        mysql_query("OPTIMIZE TABLE `log_of_visits_today`");
        // разблокируем таблицы
        mysql_query("UNLOCK TABLES");
    }

}

?>