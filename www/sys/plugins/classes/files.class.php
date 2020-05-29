<?php

/**
 * Работа с директориями загруз-центра
 */
class files {

    protected $_config_file_name = '.!config.dir.ini'; // название конфиг-файла
    protected $_data = array(); // параметры директории
    protected $_need_save = false; // необходимость сохранения параметров директории
    protected $_screens = array(); // список скриншотов
    protected $_keys = array(); // ключи, доступные для сортировки
    public $user_sort = 'position';
    public $error; // последняя ошибка
    public $name;

    /**
     * Работа с директорией загруц-центра
     * @param string $path_abs Абсолютный путь к папке загруз-центра
     */
    function __construct($path_abs) {
        $path_abs = realpath($path_abs);
        $this->type = 'folder'; // тип содержимого для иконки (по-умолчанию: папка)
        $this->runame = convert::to_utf8(basename($path_abs)); // отображаемое название папки
        $this->group_show = 2; // группа пользователей, которой разрешен просмотр папки
        $this->group_write = 3; // группа пользователей, которой разрешено создание вложенных файлов и папок
        $this->group_edit = groups::max(); // группа пользователей, которой разрешено изменение параметров данной папки
        $this->position = 1; // позиция. Учитывается при сортировке
        $this->id_user = 0; // создатель папки
        $this->description = ''; // описание папки
        $this->time_last = 0; // время последних действий
        $this->sort_default = 'runame::asc'; // сортировка по-умолчанию

        if ($cfg_ini = ini::read($path_abs . '/' . $this->_config_file_name, true)) {
            // загружаем конфиг
            $this->_data = array_merge($this->_data, (array) @$cfg_ini ['CONFIG']);
            $this->_screens = array_merge($this->_screens, (array) @$cfg_ini ['SCREENS']);
            $this->_keys = array_merge($this->_keys, (array) @$cfg_ini ['ADDKEYS']);
        } else {
            $this->time_create = TIME; // время создания
        }
        // настоящее имя папки
        $this->name = basename($path_abs);
        // получение путей на основе абсолютного пути
        $this->_setPathes($path_abs);
    }

    /**
     * Импорт файла
     * @param string $url
     * @return boolean|\files_file
     */
    public function fileImport($url) {
        if (function_exists('set_time_limit')) {
            set_time_limit(30);
        }
        // Импортировать файл
        $this->error = false;

        misc::log($url, 'loads.import');

        $purl = parse_url($url);

        if (empty($purl ['path'])) {
            misc::log('Путь: ERR', 'loads.import');
            $this->error = __('Путь к файлу не распознан');
            return false;
        } else {
            misc::log('Путь: OK', 'loads.import');
        }


        if (!$fname = basename($purl ['path'])) {
            misc::log('Имя файла: ERR', 'loads.import');
            $this->error = __('Не удалось получить имя файла из пути');
            return false;
        } else {
            misc::log('Имя файла: OK', 'loads.import');
        }

        $curl = new http_client($url);
        $name = text::for_filename($fname);

        if (!$headers = $curl->get_headers()) {
            misc::log('Headers: ERR', 'loads.import');
            $this->error = __('Не удалось получить заголовок');
            return false;
        } else {
            misc::log('Headers: OK', 'loads.import');
        }

        if (!preg_match('#^Content-Length: ([0-9]+)#uim', $headers)) {
            misc::log('Размер файла: ERR', 'loads.import');
            $this->error = __('В заголовке не указан размер');
            return false;
        } else {
            misc::log('Размер файла: OK', 'loads.import');
        }

        if (file_exists($this->path_abs . '/' . $name)) {
            misc::log('Проверка на существование файла: ERR (файл с таким названием уже существует)', 'loads.import');
            $this->error = __('В данной папке уже имеется файл с именем %s', $name);
            return false;
        } else {
            misc::log('Проверка на существование файла: OK', 'loads.import');
        }

        if (!$curl->save_content($this->path_abs . '/' . $name)) {
            misc::log('Сохранение файла: ERR', 'loads.import');
            $this->error = __('Не удалось сохранить файл %s', $name);
            return false;
        } else {
            misc::log('Сохранение файла: OK', 'loads.import');
        }

        @chmod($this->path_abs . '/' . $name, filesystem::getChmodToWrite());
        $f_obj = new files_file($this->path_abs, $name);
        $f_obj->time_add = TIME;

        if (!$f_obj->size) {
            $f_obj->delete();
            misc::log('Размер файла = 0: ERR (файл будет удален)', 'loads.import');
            $this->error = __('Скачан файл с нулевым размером');
            return false;
        } else {
            misc::log('Проверка файла: OK', 'loads.import');
        }

        // очистка кэша
        $this->cacheClear();
        misc::log('Очистка кэша: OK', 'loads.import');
        misc::log('Импорт успешно завершен', 'loads.import');
        return $f_obj;
    }

    /**
     * Добавление локальных(или выгруженных) файлов в папку
     * @param type $files
     * @return \files_file
     */
    public function filesAdd($files) {
        // добавление файлов в папку
        $ok = array();
        $files = (array) $files;
        foreach ($files as $path => $runame) {
            $name = text::for_filename($runame);

            if (@move_uploaded_file($path, $this->path_abs . '/' . $name)) {
                @chmod($this->path_abs . '/' . $name, filesystem::getChmodToWrite());

                $ok [$path] = $f_obj = new files_file($this->path_abs, $name);
                $f_obj->runame = $runame;
                $f_obj->time_add = TIME;
            } elseif (@copy($path, $this->path_abs . '/' . $name)) {
                @chmod($this->path_abs . '/' . $name, filesystem::getChmodToWrite());

                $ok [$path] = $f_obj = new files_file($this->path_abs, $name);
                $f_obj->runame = $runame;
                $f_obj->time_add = TIME;
            }
        }
        // очистка кэша


        $this->cacheClear();
        return $ok;
    }

    /**
     * Очистка кэша листинга директории
     */
    public function cacheClear() {
        // очистка кэша директории (а также проверка соответствия записей в базе реальным файлам)
        $path_rel_ru = convert::to_utf8($this->path_rel);
        $q = mysql_query("SELECT * FROM `files_cache` WHERE `path_file_rel` LIKE '" . my_esc($path_rel_ru) . "/%'");
        while ($files = @mysql_fetch_assoc($q)) {
            $abs_path = FILES . convert::of_utf8($files ['path_file_rel']);
            if (is_file($abs_path)) {
                continue;
                // если файл существует, то все ОК, пропускаем
            }
            // НО!!! Если файла нет, то это лишняя запись в базе, которую необходимо "похерить"
            // удаление файла из кэша базы
            mysql_query("DELETE FROM `files_cache` WHERE `id` = '" . intval($files ['id']) . "' LIMIT 1");
            // удаление комментов к файлу
            mysql_query("DELETE FROM `files_comments` WHERE `id_file` = '" . intval($files ['id']) . "'");
            // удаление рейтингов файла
            mysql_query("DELETE FROM `files_rating` WHERE `id_file` = '" . intval($files ['id']) . "'");
        }
        // а так же очистка кэша содержимого папки
        cache::set('files.' . $this->path_rel, false, 1);
    }

    /**
     * Создает папку в данной папке
     * @param type $runame
     * @param type $name
     * @return boolean|\files
     */
    public function mkdir($runame, $name = false) {
        // создание папки
        if ($name)
            $name = text::for_filename($name);
        else
            $name = text::for_filename($runame);

        if (!filesystem::mkdir($this->path_abs . '/' . $name)) {
            return false;
        }
        $new_dir = new files($this->path_abs . '/' . $name);
        $new_dir->runame = $runame;
        $new_dir->group_show = $this->group_show;
        $new_dir->group_write = $this->group_write;
        $new_dir->group_edit = $this->group_edit;
        $new_dir->time_create = TIME;

        $this->cacheClear();
        return $new_dir;
    }

    /**
     * Удаление текущей папки со всем содержимым
     * @return boolean
     */
    public function delete() {
        // удаление папки со всем содержимым
        // если папки не существует, то и удалять ее не можем.
        if (!is_dir($this->path_abs))
            return false;
        // папки и файлы с точкой являются системными и их случайное удаление крайне нежелательно
        if ($this->name {0} === '.')
            return false;
        $od = opendir($this->path_abs);
        while ($rd = readdir($od)) {
            if ($rd {0} == '.')
                continue;

            if (is_dir($this->path_abs . '/' . $rd)) {
                if (function_exists('set_time_limit')) {
                    set_time_limit(30);
                }

                $dir = new files($this->path_abs . '/' . $rd);
                $dir->delete(); // "Правильное" рекурсивное удаление папки
            } elseif (is_file($this->path_abs . '/' . $rd)) {
                $file = new files_file($this->path_abs, $rd);
                $file->delete(); // "Правильное" удаление файла
            }
        }

        closedir($od);

        if (function_exists('set_time_limit'))
            set_time_limit(30);
        if (filesystem::rmdir($this->path_abs)) {
            $ld = pathinfo($this->path_abs);
            $last_dir = new files($ld ['dirname']);
            $last_dir->cacheClear();

            $this->__destruct();
            return true;
        }
    }

    /**
     * Возвращает массив новых файлов
     * @global \user $user
     * @return \files_file
     */
    public function getNewFiles() {
        $time = NEW_TIME;
        global $user;
        $content = array('dirs' => array(), 'files' => array());
        $path_rel_ru = convert::to_utf8($this->path_rel);
        $q = mysql_query("SELECT * FROM `files_cache` WHERE `group_show` <= '" . intval($user->group) . "' AND `path_file_rel` LIKE '" . my_esc($path_rel_ru) . "/%' AND `path_file_rel` NOT LIKE '" . my_esc($path_rel_ru) . "/.%' AND `time_add` > '$time' ORDER BY `time_add` DESC");
        while ($files = mysql_fetch_assoc($q)) {
            $abs_path = FILES . convert::of_utf8($files['path_file_rel']);
            $pathinfo = pathinfo($abs_path);
            $file = new files_file($pathinfo ['dirname'], $pathinfo ['basename']);

            if (!is_file($abs_path)) {
                $file->delete(); // если файл не существует, то удаляем всю информацию о нем
                continue;
            }
            $content ['files'] [] = $file;
        }

        return $content;
    }

    /**
     * Поиск файлов в данной папке и во всех вложенных папках
     * @global \user $user
     * @param string $search часть имени файла
     * @return \files_file
     */
    protected function _search($search) {
        global $user;
        $content = array('dirs' => array(), 'files' => array());
        $path_rel_ru = convert::to_utf8($this->path_rel);
        $q = mysql_query("SELECT * FROM `files_cache` WHERE `group_show` <= '" . intval($user->group) . "' AND `path_file_rel` LIKE '" . my_esc($path_rel_ru) . "/%' AND `path_file_rel` NOT LIKE '" . my_esc($path_rel_ru) . "/.%' AND `runame` LIKE '%" . my_esc($search) . "%'");
        while ($files = mysql_fetch_assoc($q)) {
            $abs_path = FILES . convert::of_utf8($files ['path_file_rel']);
            $pathinfo = pathinfo($abs_path);
            $file = new files_file($pathinfo ['dirname'], $pathinfo ['basename']);

            if (!is_file($abs_path)) {
                $file->delete(); // если файл не существует, то удаляем всю информацию о нем
                continue;
            }
            $content ['files'] [] = $file;
        }

        return $content;
    }

    /**
     * Кол-во файлов в папке
     * @global \user $user
     * @param boolean $is_new считать только новые файлы
     * @return int
     */
    public function count($is_new = false) {
        if ($is_new) {
            $time = NEW_TIME;
        } else {
            $time = 0;
        }

        global $user;
        $group = (int) $user->group;
        if ($count = cache_counters::get('files.' . $this->path_rel . '.' . (int) $is_new . '.' . $group)) {
            return $count;
        }

        $path_rel_ru = convert::to_utf8($this->path_rel);
        $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `files_cache` WHERE `group_show` <= '$group' AND `time_add` > '$time' AND `path_file_rel` LIKE '" . my_esc($path_rel_ru) . "/%' AND `path_file_rel` NOT LIKE '" . my_esc($path_rel_ru) . "/.%'"), 0);

        cache_counters::set('files.' . $this->path_rel . '.' . (int) $is_new . '.' . $group, $count, 600);
        return $count;
    }

    /**
     * Возможные ключи для сортировки файлов в данной папке
     * @return array
     */
    public function getKeys() {
        // получение возможных ключей для сортировки папки
        $keys = array();
        $keys ['runame:asc'] = __('Название');
        $keys ['size:desc'] = __('Размер');
        $keys ['time_add:desc'] = __('Время добавления');

        return array_merge($keys, $this->_keys);
    }

    /**
     * Содержимое данной директории
     * @return \files_file
     */
    protected function _getListFull() {

        if ($content = cache::get('files.' . $this->path_rel)) {
            return $content;
        }
        $this->_keys = array();
        $content = array('dirs' => array(), 'files' => array());
        $od = opendir($this->path_abs);
        while ($rd = readdir($od)) {
            if ($rd {0} == '.')
                continue; // все файлы и папки начинающиеся с точки пропускаем
            if (is_dir($this->path_abs . '/' . $rd)) {
                $content ['dirs'] [] = new files($this->path_abs . '/' . $rd);
            } elseif (is_file($this->path_abs . '/' . $rd)) {
                $content ['files'] [] = $file = new files_file($this->path_abs, $rd);
                $this->_keys = array_merge($this->_keys, $file->getKeys());
            }
        }
        // если файлов и папок мало, то нет необходимости в испозовании кэша
        if (count($content ['dirs']) + count($content ['files']) > 30) {
            cache::set('files.' . $this->path_rel, $content, 60);
        }
        closedir($od);
        return $content;
    }

    /**
     * Callback для сортировки директорий
     * @param type $f1
     * @param type $f2
     * @return type
     */
    function _sort_cmp_dir($f1, $f2) {
        if ($f1->position == $f2->position) {
            return strcmp($f1->runame, $f2->runame);
        }
        return ($f1->position < $f2->position) ? -1 : 1;
    }

    /**
     * callback для сортировки файлов
     * @param type $f1
     * @param type $f2
     * @return int
     */
    protected function _sort_cmp_files($f1, $f2) {
        $sn = $this->user_sort;
        if ($f1->$sn == $f2->$sn) {
            return 0;
        }
        return ($f1->$sn < $f2->$sn) ? - 1 : 1;
    }

    /**
     * Получение списка папок и файлов с применением сортировки
     * @param type $list
     * @param type $sort
     * @return type
     */
    protected function _listSort($list, $sort) {
        usort($list ['dirs'], array($this, '_sort_cmp_dir'));

        if (!$sort) {
            $sort = $this->sort_default;
        }
        @list ($this->user_sort, $order ) = @explode(':', $sort);
        usort($list ['files'], array($this, '_sort_cmp_files'));
        if ($order == 'desc') {
            $list ['files'] = array_reverse($list ['files']);
        }

        return $list;
    }

    /**
     * Фильтрация недоступных пользователю папок и файлов
     * @global \user $user
     * @param type $list
     * @return type
     */
    protected function _listFilter($list) {
        global $user;
        $list2 = array();
        $c = count($list);
        for ($i = 0; $i < $c; $i++) {
            if ($list [$i]->group_show <= $user->group)
                $list2 [] = $list [$i];
        }
        return $list2;
    }

    /**
     * Проверка на существование подпапки с указанным именем
     * @param string $name Имя подпапки
     * @return boolean
     */
    public function is_dir($name) {
        $list = $this->getList();

        foreach ($list ['dirs'] as $dir) {
            if ($dir->name === $name)
                return true;
        }

        return false;
    }

    /**
     * Проверка на существование файла с указанным именем
     * @param string $name Имя файла
     * @return boolean
     */
    public function is_file($name) {
        $list = $this->getList();

        foreach ($list ['files'] as $file) {
            if ($file->name === $name)
                return true;
        }

        return false;
    }

    /**
     * Возвращает содержимое директории
     * @param string $sort Ключ сортировки (список ключей можно получить методом getKeys)
     * @param string $search Фильтр по имени файла
     * @return array
     */
    public function getList($sort = false, $search = false) {
        if ($search) {
            $list = $this->_search($search); // получение списка файлов по запросу
        } else {
            $list = $this->_getListFull(); // получение списка содержимого
        }

        $list ['dirs'] = $this->_listFilter($list ['dirs']); // отсеиваем недоступные папки
        $list ['files'] = $this->_listFilter($list ['files']); // отсеиваем недоступные файлы
        $list = $this->_listSort($list, $sort); // сортируем
        return $list;
    }

    /**
     * Путь директории на русском языке
     * @return string
     */
    public function getPathRu() {
        $return = array();
        $all_path = array();
        if ($this->path_rel) {
            $path_rel = preg_split('#/+#', $this->path_rel);
            if ($path_rel) {
                // $all_path[]='/';
                foreach ($path_rel as $key => $value) {
                    $path = '';
                    for ($i = 0; $i < $key; $i++) {
                        $path .= $path_rel [$i] . '/';
                    }
                    if ($path)
                        $all_path [] = $path;
                }
            }

            if ($all_path) {
                for ($i = 0; $i < count($all_path); $i++) {
                    $cnf = new files(FILES . '/' . $all_path [$i]);
                    $return [] = $cnf->runame;
                }
            }
        }

        return ($return ? implode('/', $return) . '/' : '') . $this->runame;
    }

    /**
     * Получение пути к папке для ссылки
     * @return type
     */
    public function getPath() {
        $path_rel = preg_split('#/+#', $this->path_rel);
        foreach ($path_rel as $key => $value) {
            $path_rel [$key] = urlencode($value);
        }
        return implode('/', $path_rel) . '/';
    }

    /**
     * Список скриншотов (относительные пути)
     * @return array
     */
    public function getScreens() {
        // получение скриншотов папки
        $screens = array();
        foreach ($this->_screens as $key => $value) {
            if (is_file($this->path_abs . '/' . $value))
                $screens [] = $this->path_rel . '/' . $value;
        }
        return $screens;
    }

    /**
     * Массив путей для возврата к родительским директориям
     * @global \user $user
     * @param int $k максимальное кол-во путей
     * @return array [path, runame]
     */
    public function ret($k = 5) {
        // вывод массива путей для возврата
        global $user;
        $return = array();
        $all_path = array();
        if ($this->path_rel) {
            $path_rel = preg_split('#/+#', $this->path_rel);
            if ($path_rel) {
                // $all_path[]='/';
                foreach ($path_rel as $key => $value) {
                    $path = '';
                    for ($i = 0; $i < $key; $i++) {
                        $path .= $path_rel [$i] . '/';
                    }
                    if ($path)
                        $all_path [] = $path;
                }
            }

            if ($all_path) {
                $all_path = array_reverse($all_path);
                for ($i = 0; $i < $k && $i < count($all_path); $i++) {
                    $cnf = new files(FILES . '/' . $all_path [$i]);

                    if ($cnf->group_show > $user->group) {
                        // если пользователь не сможет зайти в папку, то и ссылку показывать не будем
                        $k++;
                        continue;
                    }
                    $return [] = array('path' => $cnf->getPath(), 'runame' => $cnf->runame);
                }
            }
        }
        return $return;
    }

    /**
     * Иконка папки
     * @return string
     */
    public function icon() {
        return $this->type;
    }

    /**
     * Перемещение папки
     * @param string $new_path_abs абсолютный путь к папке в загруз-центре
     * @return boolean
     */
    public function move($new_path_abs) {
        // перемещение папки
        $new_path_abs = filesystem::unixpath($new_path_abs);
        // если новое расположение выходит за рамки Папки загрузок
        if (strpos($new_path_abs, filesystem::unixpath(FILES)) !== 0) {
            return false;
        }
        // новое расположение не может находиться внутри текущего
        if (strpos($new_path_abs, $this->path_abs) === 0) {
            return false;
        }
        // если нихрена не прокатило
        if (!rename($this->path_abs, $new_path_abs)) {
            return false;
        }
        $path_rel_ru_old = convert::to_utf8($this->path_rel);
        $this->_setPathes($new_path_abs);
        $path_rel_ru_new = convert::to_utf8($this->path_rel);
        // не забываем и в базе изменить путь вложенных файлов
        mysql_query("UPDATE `files_cache` SET `path_file_rel` = REPLACE(`path_file_rel`, '" . my_esc($path_rel_ru_old) . "', '" . my_esc($path_rel_ru_new) . "') WHERE `path_file_rel` LIKE '" . my_esc($path_rel_ru_old) . "/%'");
        $np = pathinfo($new_path_abs);
        $to_dir = new files($np ['dirname']);
        $to_dir->cacheClear();
        return true;
    }

    protected function _setPathes($path_dir_abs) {
        // установка путей
        // полный путь к папке
        $this->path_abs = filesystem::unixpath($path_dir_abs);
        // относительный путь к папке
        $this->path_rel = str_replace(filesystem::unixpath(FILES), '', $this->path_abs);
    }

    /**
     * Переименование папки
     * @param string $runame Отображаемое имя папки
     * @param string $realname Реально имя папки на сервере
     * @return boolean
     */
    public function rename($runame, $realname) {
        // переименование папки
        if ($this->path_rel && $this->name {0} !== '.') {
            $path_new = preg_replace('#[^\/\\\]+$#u', $realname, $this->path_rel);

            if (!@rename($this->path_abs, FILES . $path_new))
                return false;
            else {
                $this->name = basename($path_new);
            }
            $path_rel_ru_old = convert::to_utf8($this->path_rel);
            $this->_setPathes(FILES . $path_new);
            $path_rel_ru_new = convert::to_utf8($this->path_rel);
            // не забываем и в базе изменить путь вложенных файлов
            mysql_query("UPDATE `files_cache` SET `path_file_rel` = REPLACE(`path_file_rel`, '" . my_esc($path_rel_ru_old) . "', '" . my_esc($path_rel_ru_new) . "') WHERE `path_file_rel` LIKE '" . my_esc($path_rel_ru_old) . "/%'");
        }
        $np = pathinfo($this->path_abs);
        $to_dir = new files($np ['dirname']);
        $to_dir->cacheClear();
        $this->runame = $runame;
        return true;
    }

    /**
     * Список всех вложенных папок (рекурсивно)
     * @param string $exclude Абсолютный путь, который будет исключен из перебора
     * @return array \files
     */
    public function getPathesRecurse($exclude = false) {
        // получение всех объектов папок (рекурсивно)
        $dirs = array();
        $od = opendir($this->path_abs);
        while ($rd = readdir($od)) {
            if ($rd == '.' || $rd == '..')
                continue;
            if (is_dir($this->path_abs . '/' . $rd)) {
                if (function_exists('set_time_limit')) {
                    set_time_limit(30);
                }
                $dir = new files($this->path_abs . '/' . $rd);

                $dirs [] = $dir;
                // обработка исключений
                if ($exclude && strpos($dir->path_abs, $exclude->path_abs) === 0) {
                    continue;
                }
                $dirs = array_merge($dirs, $dir->getPathesRecurse($exclude));
            }
        }

        closedir($od);
        return $dirs;
    }

    /**
     * установка группы пользователей, которым разрешено просматривать директорию
     * данный параметр будет рекурсивно применен ко всем вложенным объектам
     * @param int $group_show
     */
    public function setGroupShowRecurse($group_show) {
        $od = @opendir($this->path_abs);
        while ($rd = @readdir($od)) {
            if ($rd {0} == '.')
                continue;
            if (is_dir($this->path_abs . '/' . $rd)) {
                if (function_exists('set_time_limit'))
                    set_time_limit(30);
                $dir = new files($this->path_abs . '/' . $rd);
                $dir->setGroupShowRecurse($group_show);
            } elseif (is_file($this->path_abs . '/' . $rd)) {
                $file = new files_file($this->path_abs, $rd);
                $file->group_show = $group_show;
            }
        }
        @closedir($od);
        $this->cacheClear();
        $this->group_show = $group_show;
    }

    function __get($n) {
        if (array_key_exists($n, $this->_data))
            return $this->_data [$n];
        else
            return false;
    }

    function __set($n, $v) {
        $this->_data [$n] = $v;
        $this->_need_save = true;
    }

    function __destruct() {
        if ($this->_need_save) {
            $this->time_last = TIME; // время последних действий
            ini::save($this->path_abs . '/' . $this->_config_file_name, array('CONFIG' => $this->_data, 'SCREENS' => $this->_screens, 'ADDKEYS' => $this->_keys), true);
        }
    }

}

?>
