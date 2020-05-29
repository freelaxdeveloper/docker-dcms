<?php

include_once 'sys/inc/start.php';

if (!empty($_GET['theme']) && themes::exists($_GET['theme'])) {
    $probe_theme = $_GET['theme'];
}

$doc = new document(1);
$doc->title = __('Тема оформления');


if (!empty($probe_theme)) {
    $doc->ret(__('Список тем оформления'), '?');
    $doc->ret(__('Личное меню'), '/menu.user.php');

    $theme = themes::getConfig($probe_theme);

    if (isset($_POST['save'])) {
        $user->theme = $probe_theme;
        $doc->msg('Тема оформления успешно изменена');
        exit;
    }

    if (isset($_POST['cancel'])) {
        header('Location: ?' . SID);
        exit;
    }


    $form = new design();
    $form->assign('method', 'post');
    $form->assign('action', '?theme=' . urlencode($probe_theme) . '&amp;' . passgen());
    $elements = array();

    $elements [] = array('type' => 'text', 'br' => 1, 'value' => __('Вы действительно хотите применить тему оформления "%s" для браузеров типа "%s"?', $theme['name'], $dcms->browser_type));

    $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'save', 'value' => __('Применить')));
    $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'cancel', 'value' => __('Отмена')));

    $form->assign('el', $elements);
    $form->display('input.form.tpl');
    exit;
}


$themes_list = themes::getList();
$listing = new listing();
foreach ($themes_list as $theme) {
    $post = $listing->post();
    $post->icon('theme');
    $post->title = $theme['name'];
    $post->hightlight = $user->theme == $theme['dir'];
    $post->url = '?theme=' . urlencode($theme['dir']);
    $supported = in_array($dcms->browser_type, $theme['browsers']);
    if ($theme['browsers']) $post->content[] = __('Поддерживаемые типы браузеров: %s', implode(', ', $theme['browsers']));
    if (!$supported)
        $post->content[] = '[b]' . __('Тема может некорректно отображаться на Вашем устройстве') . '[/b]';
}

$listing->display(__('Список тем оформления пуст'));

$doc->ret(__('Личное меню'), '/menu.user.php');
?>