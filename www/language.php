<?php

include_once 'sys/inc/start.php';
$doc = new document(); // инициализация документа для браузера
$doc->title = __('Настройки языка');


if (!empty($_GET['set_lang'])) {
    if (!languages::exists($_GET['set_lang'])) {
        $doc->err(__('Запрашиваемый языковой пакет не найден'));
    } else {
        $user_language_pack = new language_pack($_GET['set_lang']);

        if ($user->group) {
            $user->language = $user_language_pack->code;
        } else {
            $_SESSION['language'] = $user_language_pack->code;
        }

        $doc->msg(__('Языковой пакет %s (%s) успешно выбран', $user_language_pack->name, $user_language_pack->enname));

        if (!empty($_GET['return'])) {
            header('Refresh: 1; url=' . $_GET['return']);
            exit;
        }
    }
}



$languages = languages::getList();
$listing = new listing();
foreach ($languages as $key => $l) {
    $post = $listing->post();
    $post->url = '?set_lang=' . urlencode($key) . (!empty($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : '');
    $post->title = $user_language_pack->code == $key ? $l['name'] : $l['enname'];
    $post->icon = empty($l['icon']) ? false : $l['icon'];
}
$listing->display(__('Языковые пакеты не найдены'));

$doc->ret(__('Личное меню'), '/menu.user.php');
?>