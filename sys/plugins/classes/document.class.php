<?php

/**
 * Класс для формирования HTML документа.
 */
class document extends design {

    public $title = 'Заголовок';
    public $description = '';
    public $keywords = array();
    protected $err = array();
    protected $msg = array();
    protected $outputed = false;
    protected $actions = array();
    protected $returns = array();

    function __construct($group = 0) {
        parent::__construct();
        global $user, $dcms;
        $this->title = $dcms->title;
        if ($group > $user->group) {
            $this->access_denied(__('Доступ к данной странице запрещен'));
        }
        ob_start();
    }

    /**
     * Сообщение об ошибке в верху страницы 
     * @param type $err
     */
    function err($err) {
        $this->err[] = array('text' => text::filter($err, 1));
    }

    /**
     * Сообщение в верху страницы
     * @param string $msg
     */
    function msg($msg) {
        $this->msg[] = array('text' => text::filter($msg, 1));
    }

    /**
     * Добавление ссылки на возврат
     * @param string $name отображаемое название
     * @param string $link URL ссылки
     */
    function ret($name, $link) {
        $this->returns[] = array($name, $link);
    }

    /**
     * Добавление ссылки "Действие"
     * @param string $name отображаемое название
     * @param string $link URL ссылки
     */
    function act($name, $link) {
        $this->actions[] = array($name, $link);
    }

    function access_denied($err) {
        if (isset($_GET['return'])) {
            header('Refresh: 2; url=' . $_GET['return']);
        }
        $this->err($err);
        $this->output();
        exit;
    }

    /**
     * Формирование HTML документа и отправка данных браузеру
     * @global dcms $dcms
     */
    private function output() {
        global $dcms;
        if ($this->outputed) {
            // повторная отправка html кода вызовет нарушение синтаксиса документа, да и вообще нам этого нафиг не надо
            return;
        }
        $this->outputed = true;
        header('Cache-Control: no-store, no-cache, must-revalidate', true);
        header('Expires: ' . date('r'), true);



        // для осла (IE) как обычно отдельное условие
        if ($dcms->browser == 'Microsoft Internet Explorer') {
            $content_type = 'text/html';
            header('X-UA-Compatible: IE=edge', true); // отключение режима совместимости в осле
        } else if (!empty($this->theme['content']) && $this->theme['content'] === 'xhtml')
            $content_type = 'application/xhtml+xml';
        else
            $content_type = 'text/html';
        header('Content-Type: ' . $content_type . '; charset=utf-8', true);

        $this->assign('adt', new adt()); // реклама
        $this->assign('description', $this->description, 1); // описание страницы (meta)
        $this->assign('keywords', implode(', ', $this->keywords), 1); // ключевые слова (meta)
        $this->assign('actions', $this->actions); // ссылки к действию
        $this->assign('returns', $this->returns); // ссылки для возврата
        $this->assign('err', $this->err); // сообщения об ошибке
        $this->assign('msg', $this->msg); // сообщения
        $this->assign('title', $this->title, 1); // заголовок страницы
        $this->assign('content', @ob_get_clean()); // то, что попало в буфер обмена при помощи echo (display())
        $this->assign('document_generation_time', round(microtime(true) - TIME_START, 3)); // время генерации страницы

        if ($dcms->align_html) {
            // форматирование HTML кода
            $document_content = $this->fetch('document.tpl');
            $align = new alignedxhtml();
            echo $align->parse($document_content);
        } else {
            $this->display('document.tpl');
        }
    }

    /**
     * Очистка вывода
     * Тема оформления применяться не будет
     */
    function clean() {
        $this->outputed = true;
        @ob_clean();
    }

    /**
     * То что срабатывает при exit
     */
    function __destruct() {
        $this->output();
    }

}

?>