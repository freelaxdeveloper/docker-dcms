<?php

/**
 * Список тем оформления
 */
abstract class themes {

    /**
     * Проверка на существование темы оформления
     * @param string $code имя темы оформления (название папки с темой)
     * @param string $type тип темы оформления
     * @return boolean
     */
    static public function exists($code, $type = 'all') {
        $list = self::getList($type);
        return isset($list[$code]);
    }

    /**
     * Получение конфига темы оформления
     * @param string $code имя темы оформления (название папки с темой)
     * @return array
     */
    static public function getConfig($code) {
        if (!self::exists($code)) {
            return false;
        }
        $list = self::getList();
        return $list[$code];
    }

    /**
     * Возвращает список тем оформления
     * @staticvar boolean|array $list
     * @param string $type тип тем
     * @return array
     */
    static public function getList($type = 'all') {
        static $list = false;

        if ($list !== false) {
            return self::filterType($list, $type);
        }

        // получение списка языковых пакетов
        if ($list = cache::get('themes'))
            return self::filterType($list, $type);

        $list = self::getRealList();
        cache::set('themes', $list, 60);

        return self::filterType($list, $type);
    }

    /**
     * Фильтр тем оформления по типу
     * @param array $list список тем
     * @param string $type тип тем
     * @return array
     */
    static protected function filterType($list, $type = 'all') {
        if ($type != 'all') {
            foreach ($list as $dir => $conf) {
                if ($conf['browsers'] && !in_array($type, $conf['browsers'])) {
                    unset($list[$dir]);
                }
            }
        }

        return $list;
    }

    /**
     * Получение списка тем без кэширования
     * @global \dcms $dcms
     * @return array
     */
    static public function getRealList() {
        global $dcms;
        $list = array();

        // получение списка тем оформления минуя кэш
        $lpath = H . '/sys/themes';

        $od = opendir($lpath);
        while ($rd = readdir($od)) {

            if ($rd {0} == '.') {
                continue; // все файлы и папки начинающиеся с точки пропускаем
            }
            if (is_dir($lpath . '/' . $rd)) {
                if (!file_exists($lpath . '/' . $rd . '/config.ini')) {
                    // если нет конфига, то тему оформления тоже пропускаем
                    continue;
                }

                $config = ini::read($lpath . '/' . $rd . '/config.ini', true);

                if (empty($config['CONFIG'])) {
                    // нет конфигурации
                    continue;
                }

                if (empty($config['CONFIG']['version']) || $config['CONFIG']['version'] != $dcms->theme_version) {
                    // тема оформления не соответствует версии

                    continue;
                }

                $list[$rd] = self::properties($config, $rd);
            }
        }
        closedir($od);

        ksort($list);
        reset($list);

        return $list;
    }

    /**
     * Свойства темы оформления из конфига
     * @param array $config
     * @param string $dir
     * @return array
     */
    static protected function properties($config, $dir) {
        if (empty($config['VARS'])) {
            $vars = array();
        } else {
            $vars = $config['VARS'];
        }

        $info = $config['CONFIG'];
        $info['vars'] = &$vars;
        $info['dir'] = $dir;

        if (empty($info['name'])) {
            $info['name'] = $dir;
        }

        if (empty($info['img_width_max'])) {
            $info['img_width_max'] = 300;
        }

        if (empty($info['browsers'])) {
            $info['browsers'] = array();
        } else {
            $info['browsers'] = preg_split('/[\|\,\:\^]/', $info['browsers']);
        }

        if (empty($info['content'])) {
            $info['content'] = 'html';
        }

        if (empty($info['icons'])) {
            $info['icons'] = '/sys/images/icons';
        } else {
            $info['icons'] = '/sys/themes/' . $dir . '/' . $info['icons'];
        }

        return $info;
    }

    /**
     * очистка кэша списка тем
     */
    static public function clearCache() {
        cache::set('themes', false);
    }

}
