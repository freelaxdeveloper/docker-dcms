<?php

include_once 'sys/inc/start.php';
$doc = new document();
$doc->title = __('Справка');
$faq = preg_replace('#[^a-z0-9_\-]+#ui', '', @$_GET['info']);
$bb = new bb(H . '/sys/docs/faq/' . $faq . '.txt');

if ($bb->err) {
    if (isset($_GET['return'])) {
        header('Refresh: 1; url=' . $_GET['return']);
    } else {
        header('Refresh: 1; url=/');
    }

    $doc->err(__('Запрошенная информация не найдена'));
    exit;
}


if ($bb->title) {
    $doc->title = $bb->title;
}
$bb->display();


if (isset($_GET['return'])) {
    $doc->ret(__('Вернуться'), for_value($_GET['return']));
}
?>
