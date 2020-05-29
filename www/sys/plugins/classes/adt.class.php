<?php

/**
 * Реклама
 */
class adt extends menu {

    function __construct() {
        parent::__construct();
        $this->menu_arr = array(
            'Под заголовком' => array('id' => 'top', 'url' => '?id=top', 'icon' => 'adt.png'),
            'Низ сайта' => array('id' => 'bottom', 'url' => '?id=bottom', 'icon' => 'adt.png')
        );
    }

    function __get($id) {
        return $this->getArrayAdtForId($id);
    }

    /**
     * Возвращает название рекламной площадки по id, если таковая существует
     * @param type $id
     * @return mixed(string||boolean)
     */
    function getNameById($id) {
        foreach ($this->menu_arr as $key => $value) {
            if (isset($value['id']) && $value['id'] == $id)
                return $key;
        }
        // в случае неудачи возвращаем false
        return false;
    }

    /**
     * Получение рекламной позиции в виде массива
     * @global dcms $dcms
     * @param string $id площадка (top, bottom)
     * @return array
     */
    private function getArrayAdtForId($id) {
        global $dcms;
        $return = array();
        if ($this->getNameById($id)) {
            $target = $dcms->browser_type == 'web' ? ' target="_blank"' : '';
            $q = mysql_query("SELECT * FROM `advertising` WHERE `space` = '" . my_esc($id) . "' AND `" . (IS_MAIN ? 'page_main' : 'page_other') . "` = '1' AND (`time_start` < '" . TIME . "' OR `time_start` = '0') AND (`time_end` > '" . TIME . "' OR `time_end` = '0') ORDER BY `time_start` ASC");
            while ($adt = mysql_fetch_assoc($q)) {
                if ($adt['url_img']) {
                    $return[]['0'] = '<a rel="nofollow" href="http://' . $_SERVER['HTTP_HOST'] . '/link.ext.php?url=' . urlencode($adt['url_link']) . '"' . $target . '><img src="' . $adt['url_img'] . '" alt="' . for_value($adt['name']) . '" /></a>';
                } else {
                    $return[]['0'] = '<a rel="nofollow" ' . ($adt['bold'] ? 'class="DCMS_font_bold"' : '') . ' href="http://' . $_SERVER['HTTP_HOST'] . '/link.ext.php?url=' . urlencode($adt['url_link']) . '"' . $target . '>' . for_value($adt['name']) . '</a>';
                }
            }
            if (!isset($_SESSION['adt'][$id]['time_show']) || $_SESSION['adt'][$id]['time_show'] < TIME - 10) {
                // показ рекламы засчитывается один раз в 10 секунд
                $_SESSION['adt'][$id]['time_show'] = TIME;
                mysql_query("UPDATE `advertising` SET `count_show_" . $dcms->browser_type . "` = `count_show_" . $dcms->browser_type . "` + 1 WHERE `space` = '" . my_esc($id) . "' AND `" . (IS_MAIN ? 'page_main' : 'page_other') . "` = '1' AND (`time_start` < '" . TIME . "' OR `time_start` = '0') AND (`time_end` > '" . TIME . "' OR `time_end` = '0')");
            }
        }
        return $return;
    }

}

?>