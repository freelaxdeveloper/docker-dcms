<?php

/**
 * Работа с текстом
 */
abstract class text {

    /**
     * Фильтрация текста
     * @param type $str
     * @param type $type
     * @return type
     */
    static function filter($str, $type = 1) {
        switch ($type) {
            case 1: return self::for_value($str);
                break;
            case 2: return self::output_text($str);
                break;
            default:return $str;
        }
    }

    /**
     * Получение корректного ICQ UIN
     * @param type $icq
     * @return string|null
     */
    static function icq_uin($icq) {
        if (preg_match('#[0-9]{5,9}#', $icq, $res)) {
            return $res[0];
        } else {
            return null;
        }
    }

    /**
     * Фильтрация текста с ограничением длины в зависимости от типа браузера.
     * Обрабатывается BBCODE
     * @global type $dcms
     * @param type $text
     * @return type
     */
    static function for_opis($text) {
        global $dcms;
        $text = self::substr($text, $dcms->browser_type == 'web' ? 100000 : 4096);
        $text = self::output_text($text);
        return $text;
    }

    /**
     * получение кол-ва символов строки 
     * Корректная работа с UTF-8
     * @param string $str
     * @return integer
     */
    static function strlen($str) {
        if (function_exists('mb_substr')) {
            return mb_strlen($str);
        }
        if (function_exists('iconv')) {
            return iconv_strlen($str);
        }
        return strlen($str);
    }

    /**
     * Получение подстроки
     * Корректная работа с UTF-8
     * @param string $text Исходная строка
     * @param integer $len Максимальная длина возвращаемой строки
     * @param integer $start Начало подстроки
     * @param string $mn Текст, подставляемый в конец строки при условии, что возхвращаемая строка меньще исходной
     * @return string
     */
    static function substr($text, $len, $start = 0, $mn = ' (...)') {
        $text = trim($text);
        if (function_exists('mb_substr')) {
            return mb_substr($text, $start, $len) . (mb_strlen($text) > $len - $start ? $mn : null);
        }
        if (function_exists('iconv')) {
            return iconv_substr($text, $start, $len) . (iconv_strlen($text) > $len - $start ? $mn : null);
        }

        return $text;
    }

    /**
     * Фильтрация и обработка текста, поступающего от пользователя
     * !!! не защищает от SQL-Inj или XSS
     * @param string $str
     * @return string
     */
    static function input_text($str) {
        // обработка входящего текста
        $str = (string) $str;
        // обработка ника
        //$str = preg_replace_callback('#@([a-zа-яё][a-zа-яё0-9\-\_\ ]{2,31})([\!\.\,\ \)\(]|$)#uim', array('text', 'nick'), $str);
        $str = preg_replace("#(^( |\r|\n)+)|(( |\r|\n)+$)|([^\pL\r\n\s0-9" . preg_quote(' []|`@\'"-_+=~!#:;$%^&*()?/\\.,<>{}©№', '#') . "]+)#ui", '', $str);

        $inputbbcode = new inputbbcode($str);
        $str = $inputbbcode->get_html();

        return $str;
    }

    /**
     * Фильтрация и форматирование текста перед вставкой в HTML
     * Производится обработка BBCODE и вставка смайлов
     * @param string $str
     * @return string
     */
    static function output_text($str) {

        //
        // преобразование смайлов в BBcode
        $str = smiles::input($str);

        // обработка старых цитат с числом в теге
        $str = preg_replace('#\[(/?)quote_([0-9]+)(\]|\=)#ui', '[\1quote\3', $str);

        // преобразование ссылок в тег URL
        $str = preg_replace('#(^|\s|\(|\])([a-z]+://([^ \r\n\t`\'"<]+))(,|\[|<|\s|$)#iuU', '\1[url="\2"]\2[/url]\4', $str);

        // предварительная обработка BBcode
        $prebbcode = new prebbcode($str);
        $str = $prebbcode->get_html();

        $bbcode = new bbcode($str);

        $bbcode->mnemonics['[add]'] = '<img src="/sys/images/icons/bb.add.png" alt="" />';
        $bbcode->mnemonics['[del]'] = '<img src="/sys/images/icons/bb.del.png" alt="" />';
        $bbcode->mnemonics['[fix]'] = '<img src="/sys/images/icons/bb.fix.png" alt="" />';
        $bbcode->mnemonics['[change]'] = '<img src="/sys/images/icons/bb.change.png" alt="" />';
        $bbcode->mnemonics['[secure]'] = '<img src="/sys/images/icons/bb.secure.png" alt="" />';
        $bbcode->mnemonics['[notice]'] = '<img src="/sys/images/icons/bb.notice.png" alt="" />';

        $str = $bbcode->get_html();

        //$str = wordwrap($str, 10, "&#173;");

        return $str;
    }

    /**
     * Поиск ника пользователя в тексте сообщения и замена BBCOD`ом
     * @param string $str Текст сообщения
     * @param boolean $replace
     * @return boolean
     */
    static function nickSearch(&$str, $replace = true) {
        if (!@mysql_ping()) {
            return false;
        }
        $pattern = '#@([a-zа-яё][a-zа-яё0-9\-\_\ ]{2,31})([\!\.\,\ \)\(]|$)#uim';

        $m = array();
        preg_match_all($pattern, $str, $m, PREG_SET_ORDER);
        if ($replace)
            $str = preg_replace_callback($pattern, array('text', 'nick'), $str);

        $logins = array();
        foreach ($m AS $sl) {
            $logins[] = "'" . my_esc($sl[1]) . "'";
        }
        $logins = array_unique($logins);

        $users_id = array();

        if ($logins) {
            $q = mysql_query("SELECT `id` FROM `users` WHERE `login` IN (" . implode(',', $logins) . ")");
            while ($ank = mysql_fetch_assoc($q)) {
                $users_id[] = $ank['id'];
            }
        }
        return $users_id;
    }

    /**
     * Callback для замены ника пользователя в тексте сообщения
     * @param type $value
     * @return type
     */
    static function nick($value) {
        if (!@mysql_ping()) {
            // сделано для избежания проблем при установке, когда подключение к базе еще не выполнено
            return $value[1] . $value[2];
        }
        $q = mysql_query("SELECT `id` FROM `users` WHERE `login` = '" . my_esc($value[1]) . "' LIMIT 1");
        if ($ank = mysql_fetch_assoc($q)) {
            return '[user]' . $ank['id'] . '[/user]' . $value[2];
        } else {
            return $value[1] . $value[2];
        }
    }

    /**
     * Обработка текста
     * @param type $str
     * @return type
     */
    static function for_value($str) {

        // обработка старых цитат с числом в теге
        $str = preg_replace('#\[(/?)quote_([0-9]+)(\]|\=)#ui', '[\1quote\3', $str);

        // предварительная обработка BBcode
        $bbcode = new prebbcode($str);
        $str = $bbcode->get_html();

        $str = trim(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'));

        return $str;
    }

    /**
     * Возврат строки, разрешенной для названий
     * @param string $text
     * @return string
     */
    static function for_name($text) {
        return trim(preg_replace('#[^\pL0-9\=\?\!\@\\\%/\#\$^\*\(\)\-_\+ ,\.:;]+#ui', '', $text));
    }

    /**
     * Возврат строки, разрешенной для названий файлов
     * @param string $text
     * @return string
     */
    static function for_filename($text) {
        return trim(preg_replace('#(^\.)|[^a-z0-9_\-\(\)\.]+#ui', '_', self::translit($text)));
    }

    /**
     * Транслитерация русского текста в английский
     * @param string $string
     * @return string
     */
    static function translit($string) {
        $table = array(
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Ґ' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Є' => 'YE',
            'Ё' => 'YO',
            'Ж' => 'ZH',
            'З' => 'Z',
            'И' => 'I',
            'І' => 'I',
            'Ї' => 'YI',
            'Й' => 'J',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ў' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'CH',
            'Ш' => 'SH',
            'Щ' => 'CSH',
            'Ь' => '',
            'Ы' => 'Y',
            'Ъ' => '',
            'Э' => 'E',
            'Ю' => 'YU',
            'Я' => 'YA',
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'ґ' => 'g',
            'д' => 'd',
            'е' => 'e',
            'є' => 'ye',
            'ё' => 'yo',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'і' => 'i',
            'ї' => 'yi',
            'й' => 'j',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ў' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'csh',
            'ь' => '',
            'ы' => 'y',
            'ъ' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
        );
        return str_replace(array_keys($table), array_values($table), $string);
    }

}