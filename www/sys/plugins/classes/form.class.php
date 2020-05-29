<?php

/**
 * Генератор форм
 */
class form extends ui {

    /**
     * Создание формы
     * @param string $url Путь (атрибут action в форме)
     * @param boolean $post true-отправлять post`ом, false - get`ом
     */
    public function __construct($url = '', $post = true) {
        parent::__construct();
        $this->_tpl_file = 'input.form.tpl';

        $this->_data['el'] = array();

        $this->set_url($url);
        $this->set_method($post ? 'post' : 'get');
    }
    
    /**
     * URL для обновления формы
     * @param string $url
     */
    function refresh_url($url){
        $this->_data['refresh_url'] = $url;
    }
    
    /**
     * Вставка HTML блока
     * @param string $html
     * @param boolean $br
     */
    function html($html, $br = false){
        $this->_data['el'][] = array(
            'type' => 'text',
            'br' => (bool) $br,
            'value' => $html
        );
    }    
    
    /**
     * Вставка текстового блока, который будет обработан BBCODE
     * @param string $bbcode
     * @param boolean $br
     */
    function bbcode($bbcode, $br = true) {
        $this->html(text::output_text($bbcode), $br);
    }

    /**
     * Чекбокс 
     * @param string $name аттрибут name
     * @param string $title текст к чекбоксу
     * @param boolean $checked значение, установлена ли галочка
     * @param boolean $br перенос строки
     * @param string $value аттрибут value
     */
    function checkbox($name, $title, $checked = false, $br = true, $value = '1') {
        $this->_data['el'][] = array(
            'type' => 'checkbox',
            'br' => (bool) $br,
            'info' => array(
                'name' => text::for_value($name),
                'checked' => (bool) $checked,
                'value' => text::for_value($value),
                'text' => text::for_value($title)
            )
        );
    }

    /**
     * Поле "select"
     * @param string $name
     * @param string $title
     * @param array $options
     * @param boolean $br
     */
    function select($name, $title, $options, $br = true) {
        $this->_data['el'][] = array(
            'type' => 'select',
            'title' => text::for_value($title),
            'br' => (bool) $br,
            'info' => array(
                'name' => text::for_value($name),
                'options' => (array) $options
            )
        );
    }

    /**
     * Кнопка
     * @param string $text Отображаемое название кнопки
     * @param string $name аттрибут name
     * @param boolean $br перенос
     */
    function button($text, $name = '', $br = true) {
        $this->input($name, '', $text, 'submit', $br);
    }

    /**
     * Поде для выбора файла
     * @param string $name аттрибут name
     * @param string $title Заголовок к полю выбора файла
     * @param boolean $br перенос строки
     */
    function file($name, $title, $br = true) {
        $this->input($name, $title, false, 'file', $br);
    }

    /**
     * Капча
     * @param boolean $br перенос строки
     */
    function captcha($br = true) {
        $this->_data['el'][] = array('type' => 'captcha', 'br' => $br, 'session' => captcha::gen());
    }

    /**
     * Поле ввода пароля
     * @param string $name аттрибут name
     * @param string $title Заголовок к полю ввода
     * @param string $value введенное значение в поле
     * @param boolean $br перенос строки
     * @param int $size ширина поля ввода в символах
     */
    function password($name, $title, $value = '', $br = true, $size = false) {
        $this->input($name, $title, $value, 'password', $br, $size);
    }

    /**
     * Текстовое поле ввода
     * @param string $name аттрибут name
     * @param string $title Заголовок поля ввода
     * @param string $value значение в поле ввода
     * @param boolean $br перенос строки
     * @param int $size ширина поля ввода в символах
     * @param boolean $disabled запретить изменение
     */
    function text($name, $title, $value = '', $br = true, $size = false, $disabled = false) {
        $this->input($name, $title, $value, 'text', $br, $size, $disabled);
    }

    /**
     * Скрытое поле формы
     * @param string $name аттрибут name
     * @param string $value значение
     */
    function hidden($name, $value) {
        $this->input($name, '', $value, 'hidden', false);
    }

    /**
     * Поле ввода для сообщения
     * @param string $name аттрибут name
     * @param string $title заголовок поля ввода
     * @param string $value введенный текст
     * @param boolean $br перенос
     * @param boolean $disabled запретить изменение
     */
    function textarea($name, $title, $value = '', $br = true, $disabled = false) {
        $this->input($name, $title, $value, 'textarea', $br, false, $disabled);
    }

    /**
     * Добавление input`a
     * @param string $name аттрибут name
     * @param string $title заголовок
     * @param string $value значение по-умолчанию
     * @param string $type тип (аттрибут type)
     * @param boolean $br вставка переноса строки после input`a
     * @param int $size ширина поля ввода в символах
     * @param boolean $disabled блокировать изменения
     * @param int $maxlength максимальная вместимость в символах
     * @return boolean
     */
    function input($name, $title, $value = '', $type = 'text', $br = true, $size = false, $disabled = false, $maxlength = false) {
        if (!in_array($type, array('text', 'input_text', 'password', 'hidden', 'textarea', 'submit', 'file')))
            return false;

        $input = array();

        if ($type == 'file')
            $this->set_is_files();

        if ($type == 'text')
            $type = 'input_text'; // так уж изначально было задумано. Избавляться будем постепенно

        $input['type'] = $type;
        $input['title'] = text::output_text($title);
        $input['br'] = (bool) $br;

        $info = array();
        $info['name'] = text::for_value($name);
        $info['value'] = $value;

        $info['disabled'] = (bool) $disabled;

        if ($size)
            $info['size'] = (int) $size;
        if ($maxlength)
            $info['maxlength'] = (int) $maxlength;

        $input['info'] = $info;
        $this->_data['el'][] = $input;
        return true;
    }

    /**
     * Установка метода передачи формы на сервер (post, get)
     * @param string $method
     */
    function set_method($method) {
        if (in_array($method, array('get', 'post')))
            $this->_data['method'] = $method;
    }

    /**
     * Установка URL (атрибут action формы)
     * @param string $url
     */
    function set_url($url) {
        $this->_data['action'] = $url;
    }

    /**
     * Будут передаваться файлы
     */
    function set_is_files() {
        $this->_data['method'] = 'post';
        $this->_data['files'] = true;
    }

}

?>
