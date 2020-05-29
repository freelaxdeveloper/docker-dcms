<?php

/**
 * UI. Пост в списке постов
 */
class listing_post extends ui {

    public $id = 0;
    public $url = '';
    public $icon = false;
    public $counter = 0;
    public $time = '';
    public $image = '';
    public $title = '';
    public $content = '';
    public $bottom = '';
    public $hightlight = false;
    public $actions = array();
    protected $_old_props = array(
        'post' => 'content',
        'edit' => 'bottom',
        'new' => 'highlight',
    );

    /**
     * 
     * @param string $title заголовок поста
     * @param string $content Содержимое поста
     */
    public function __construct($title = '', $content = '') {
        parent::__construct();
        $this->_tpl_file = 'listing.post.tpl';
        $this->id = $this->_data['id'];

        $this->title = $title;
        $this->content = $content;
    }

    public function __get($name) {
        $name = $this->_replace_old_properties($name);
        return $this->$name;
    }

    public function __set($name, $value) {
        $name = $this->_replace_old_properties($name);
        if (isset($this->$name)) {
            $this->$name = $value;
            return true;
        } else {
            return false;
        }
    }

    /**
     * иконка действия
     * @param string $icon имя системной иконки
     * @param string $url путь
     */
    public function action($icon, $url) {
        $design = new design();
        $this->actions[] = array('icon' => $design->getIconPath($icon), 'url' => $url);
    }

    /**
     * Установка иконки сообщения
     * @param string $icon имя системной иконки
     */
    public function icon($icon) {
        $design = new design();
        $this->icon = $design->getIconPath($icon);
    }

    /**
     * Замена старых названий свойств
     * @param type $name
     * @return type
     */
    protected function _replace_old_properties($name) {
        if (isset($this->_old_props[$name])) {
            $name = $this->_old_props[$name];
        }
        return $name;
    }

    public function fetch() {
        $this->_data['id'] = $this->id;
        $this->_data['url'] = $this->url;
        $this->_data['time'] = $this->time;
        $this->_data['title'] = $this->title;
        if (is_array($this->content)) {
            $this->content = output_text(implode("\n", $this->content));
        }
        $this->_data['content'] = $this->content;
        $this->_data['counter'] = $this->counter;
        $this->_data['image'] = $this->image;
        $this->_data['icon'] = $this->icon;
        $this->_data['hightlight'] = $this->hightlight;
        $this->_data['bottom'] = $this->bottom;
        $this->_data['actions'] = $this->actions;
        return parent::fetch();
    }

}

?>
