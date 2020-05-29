<?php
include_once '../sys/inc/start.php';
$doc = new document(4);
$doc->title = __('Создание новости');
$doc->ret (__('К новостям'), './');
$news = &$_SESSION['news_create'];

if (isset($_POST['clear']))$news = array();

if (empty($news)) {
    $news = array();
    $news['title'] = '';
    $news['text'] = '';
    $news['checked'] = false;
}

if ($news['checked'] && isset($_POST['send'])) {
    if (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session']))
        $doc->err(__('Ошибка при вводе чисел с картинки'));
    else {
        mysql_query("INSERT INTO `news` (`title`, `time`, `text`, `id_user`)
VALUES ('" . my_esc($news['title']) . "', '" . TIME . "', '" . my_esc($news['text']) . "', '$user->id')");

        $doc->msg(__('Новость успешно опубликована'));
        $news = array();
        header('Refresh: 1; ./');
        exit;
    }
}

if (isset($_POST['edit']))$news['checked'] = 0;

if (isset($_POST['next'])) {
    $title = text::for_name($_POST['title']);
    $text = text::input_text($_POST['text']);

    if (!$title)$doc->err(__('Заполните "Заголовок новости"'));
    else $news['title'] = $title;
    if (!$text)$doc->err(__('Заполните "Текст новости"'));
    else $news['text'] = $text;

    if ($title && $text)$news['checked'] = 1;
}

$smarty = new design();
$smarty->assign('method', 'post');
$smarty->assign('action', '?' . passgen());
$elements = array();
$elements[] = array('type' => 'input_text', 'title' => __('Заголовок новости'), 'br' => 1, 'info' => array('name' => 'title', 'value' => $news['title'] , 'disabled' => $news['checked']));
$elements[] = array('type' => 'textarea', 'title' => __('Текст новости'), 'br' => 1, 'info' => array('name' => 'text', 'value' => $news['text'], 'disabled' => $news['checked']));

if ($news['checked']) {
    $elements[] = array('type' => 'captcha', 'session' => captcha::gen(), 'br' => 1);
    $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'edit', 'value' => __('Редактировать'))); // кнопка
    $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'send', 'value' => __('Опубликовать'))); // кнопка
}else {
    $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'clear', 'value' => __('Очистить'))); // кнопка
    $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'next', 'value' => __('Далее'))); // кнопка
}

$smarty->assign('el', $elements);
$smarty->display('input.form.tpl');

?>
