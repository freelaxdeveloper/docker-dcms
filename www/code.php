<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Кодекс');
$doc->act(__('Правила сайта'), '/rules.php');
if (isset($_GET['info'])) {
    $faq = preg_replace('#[^a-z0-9_\-]+#ui', '', $_GET['info']);
    $bb = new bb(H . '/sys/docs/code/' . $faq . '.txt');
    if ($bb->err) {
        if (isset($_GET['return']))
            header('Refresh: 1; url=' . $_GET['return']);
        else
            header('Refresh: 1; url=?');
        $doc->err(__('Запрошенная информация не найдена'));
        exit;
    }
}
if (isset($_GET['return']))
    $doc->ret(__('Вернуться'), urlencode($_GET['return']));

if (isset($_GET['info'])) {
    if ($bb->title) {
        $doc->title = $bb->title;
    }
    $bb->display();
    $doc->ret(__('Весь кодекс'), '?');
} else {
    $menu = new menu_code('code'); // загружаем меню кодекса
    $menu->display();
}
$doc->ret(__('Личное меню'), '/menu.user.php');
?>
