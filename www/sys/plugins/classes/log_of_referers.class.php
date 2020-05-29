<?php

/**
 * Запись переходов с других сайтов
 */
class log_of_referers {
    var $is_referer = false;
    private $url = array();
    private $referer = null;
    function log_of_referers()
    {
        // массив использованных рефереров
        if (!isset($_SESSION['LAST_REFERER']))$_SESSION['LAST_REFERER'] = array();
        $lr = &$_SESSION['LAST_REFERER'];
        if (!empty($_SERVER['HTTP_REFERER']) && $url = @parse_url($_SERVER['HTTP_REFERER'])) {
            if (!empty($url['host']) && $url['host'] != $_SERVER['HTTP_HOST'] && !in_array($url['host'], $lr)) {
                // защита от накрутки (запись происходит только при следующем обращении)
                $_SESSION['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
                return true;
            }
        }
        if (!empty($_SESSION['HTTP_REFERER']) && $url = @parse_url($_SESSION['HTTP_REFERER'])) {
            $this->referer = $_SESSION['HTTP_REFERER'];
            // уже использованые рефереры
            $lr[] = $url['host'];
            unset($_SESSION['HTTP_REFERER']);
            $this->url = $url;
            $site = $this->id_site();
            $this->add_to_log($site);
        }
    }

    private function id_site()
    {
        $q = mysql_query("SELECT * FROM `log_of_referers_sites` WHERE `domain` = '" . my_esc($this->url['host']) . "' LIMIT 1");
        if (!mysql_num_rows($q)) {
            mysql_query("INSERT INTO `log_of_referers_sites` (`domain`, `time`) VALUES ('" . my_esc($this->url['host']) . "', '" . TIME . "')");
            return mysql_insert_id();
        }
        $id = mysql_result($q, 0, 'id');
        mysql_query("UPDATE `log_of_referers_sites` SET `time` = '" . TIME . "', `count` = `count` + 1 WHERE `id` = '$id' LIMIT 1");
        return $id;
    }

    private function add_to_log($id)
    {
        mysql_query("INSERT INTO `log_of_referers` (`id_site`, `time`, `full_url`) VALUES ('$id', '" . TIME . "', '" . my_esc($this->referer) . "')");
    }
}
