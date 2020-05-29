<?php

/**
 * Пользователь
 */
class user extends plugins {

    protected $_update = array();
    protected $_data = array();

    /**
     * 
     * @param boolean|int|array $id_or_arrayToCache Идентификатор пользователя или массив идентификаторов для запроса из базы и помещения в кэш
     */
    function __construct($id_or_arrayToCache = false) {
        if ($id_or_arrayToCache === false) {
            $this->_guest_init();
        } elseif (is_array($id_or_arrayToCache)) {
            $this->_usersFromCache($id_or_arrayToCache);
            $this->_guest_init();
        } else {
            $this->_user_init($id_or_arrayToCache);
        }
    }

    /**
     * Получение данных сразу нескольких пользователей и помещение их в кэш
     * @staticvar array $cache Массив с кэшем данных пользователей
     * @param array $get_users_by_id Массив идентификаторов пользователей
     * @return array Массив данных запрошенных пользователей
     */
    protected function _usersFromCache($get_users_by_id) {
        static $cache = array(); // кэш пользователей
        $get_users_by_id = array_unique((array) $get_users_by_id);

        $users_from_mysql = array(); // пользователи, которые будут запрашиваться из базы (нет в кэше)
        $users_return = array(); // пользователи, которые будут возвращены

        foreach ($get_users_by_id AS $id_user) {
            if (array_key_exists($id_user, $cache))
                $users_return[$id_user] = $cache[$id_user];
            else
                $users_from_mysql[] = (int) $id_user;
        }

        if ($users_from_mysql) {
            $q = mysql_query("SELECT * FROM `users` WHERE `id` IN (" . implode(',', $users_from_mysql) . ")");
            while ($user_data = mysql_fetch_assoc($q)) {
                $id_user = $user_data['id'];
                $users_return[$id_user] = $cache[$id_user] = $user_data;
            }
        }

        return $users_return;
    }

    /**
     * Инициализация данных неавторизованного пользователя (гостя)
     */
    protected function _guest_init() {
        $this->_update = array();
        $this->_data = array();
        $this->_data ['id'] = false;
        $this->_data ['sex'] = 1;
        $this->_data ['group'] = 0;
    }

    /**
     * инициализация данных пользователя
     * @global \dcms $dcms
     * @staticvar array $cache
     * @param int $id
     */
    protected function _user_init($id) {
        $this->_guest_init();

        if ($id === 0) {
            global $dcms;
            // для системных уведомлений
            $this->_data ['id'] = 0;
            $this->_data ['login'] = '[' . $dcms->system_nick . ']';
            $this->_data ['group'] = 6;
            $this->_data ['description'] = __('Системный бот. Создан для уведомлений.');
            return;
        }

        $users = $this->_usersFromCache($id);
        if (array_key_exists($id, $users))
            $this->_data = $users[$id];
    }

    /**
     * проверка бана пользователя
     * @staticvar array $is_ban Массив с кэшем забаненых пользователй
     * @return boolean Забанен ли пользователь
     */
    protected function _is_ban() {
        static $is_ban = array();

        if (!isset($is_ban [$this->_data ['id']])) {
            $is_ban [$this->_data ['id']] = mysql_result(mysql_query("SELECT COUNT(*) FROM `ban` WHERE `id_user` = '{$this->_data['id']}' AND `time_start` < '" . TIME . "' AND (`time_end` is NULL OR `time_end` > '" . TIME . "')"), 0);
        }

        return !empty($is_ban [$this->_data ['id']]);
    }

    /**
     * проверка полного (запрет навигации) бана пользователя
     * @staticvar array $is_ban_full Массив с кэшем забаненых пользователей
     * @return boolean Пользователь забанен с запретом навигации по сайту
     */
    protected function _is_ban_full() {
        static $is_ban_full = array();

        if (!isset($is_ban_full [$this->_data ['id']])) {
            $is_ban_full [$this->_data ['id']] = mysql_result(mysql_query("SELECT COUNT(*) FROM `ban` WHERE `id_user` = '{$this->_data['id']}' AND `access_view` = '0' AND `time_start` < '" . TIME . "' AND (`time_end` is NULL OR `time_end` > '" . TIME . "')"), 0);
        }

        return !empty($is_ban_full [$this->_data ['id']]);
    }

    /**
     * проверяет, находится ли пользователь сейчас в онлайне
     * @staticvar array $online Массив с кэшем пользователей, находящихся в данный момент онлайн
     * @param integer $id_user Идентификатор пользователя
     * @return boolean Пользователь онлайн
     */
    protected function _is_online($id_user) {
        static $online = false;
        if ($online === false) {
            $online = array();
            $q = mysql_query("SELECT `id_user` FROM `users_online`");
            while ($on = mysql_fetch_assoc($q)) {
                $online[$on ['id_user']] = true;
            }
        }
        return isset($online[$id_user]);
    }

    /**
     * Проверка на возможность писать сообщения
     * @global \dcms $dcms
     * @return boolean Пользователь может писать сообщения
     */
    protected function _is_writeable() {
        if ($this->_is_ban())
            return false;

        global $dcms;
        if (!$dcms->user_write_limit_hour) {
            // ограничение не установлено
            return true;
        } elseif ($this->_data['group'] >= 2) {
            // пользователь входит в состав администрации
            return true;
        } elseif ($this->_data['reg_date'] < TIME - $dcms->user_write_limit_hour * 3600) {
            // пользователь преодолел ограничение
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @global \dcms $dcms
     * @param string $n ключ
     * @return mixed значение
     */
    function __get($n) {
        global $dcms;
        switch ($n) {
            case 'language' :
                return empty($this->_data ['language']) ? $dcms->language : $this->_data ['language'];
            case 'is_writeable' :
                return $this->_is_writeable();
            case 'is_ban' :
                return $this->_is_ban();
            case 'is_ban_full' :
                return $this->_is_ban_full();
            case 'online' :
                return (bool) (@$this->_data ['last_visit'] > TIME - SESSION_LIFE_TIME);
            case 'group_name' :
                return groups::name($this->_data ['group']);
            case 'items_per_page' :
                return !empty($this->_data ['items_per_page_' . $dcms->browser_type]) ? $this->_data ['items_per_page_' . $dcms->browser_type] : $dcms->items_per_page;
            case 'theme' :
                return @$this->_data ['theme_' . $dcms->browser_type];
            case 'nick' :
                return @$this->nick();
            default :
                return !isset($this->_data [$n]) ? false : $this->_data [$n];
        }
    }

    /**
     * 
     * @global \dcms $dcms
     * @param string $n ключ
     * @param string $v значение
     */
    function __set($n, $v) {
        if (empty($this->_data ['id']))
            return;
        global $dcms;
        switch ($n) {
            case 'theme' :
                $n .= '_' . $dcms->browser_type;
                break;
            case 'items_per_page' :
                $n .= '_' . $dcms->browser_type;
                break;
        }

        if (isset($this->_data [$n])) {
            $this->_data [$n] = $v;
            $this->_update [$n] = $v;
        } else {
            trigger_error("Поле $n не существует");
        }
    }

    function __destruct() {
        if ($this->_update) {
            $sql = array();
            foreach ($this->_update as $key => $value) {
                $sql [] = "`" . my_esc($key) . "` = '" . my_esc($value) . "'";
            }
            mysql_query("UPDATE `users` SET " . implode(', ', $sql) . " WHERE `id` = '" . $this->_data ['id'] . "' LIMIT 1");
        }
    }
}