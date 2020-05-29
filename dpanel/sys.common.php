<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$doc = new document(5);
$doc->title = __('Общие настройки');
$languages = languages::getList(); // список доступных языковых пакетов

$browser_types = array('wap', 'pda', 'itouch', 'web');

if (isset($_POST ['save'])) {
    $dcms->debug = (int) !empty($_POST ['debug']);
    $dcms->align_html = (int) !empty($_POST ['align_html']);
    $dcms->new_time_as_date = (int) !empty($_POST ['new_time_as_date']);
    $dcms->censure = (int) !empty($_POST ['censure']);

    foreach ($browser_types as $b_types) {
        $key = 'theme_' . $b_types;
        if (!empty($_POST [$key])) {
            $theme_set = (string) $_POST [$key];
            if (themes::exists($theme_set, $b_types))
                $dcms->$key = $theme_set;
        }
    }

    $lang = text::input_text($_POST ['language']);
    if (isset($languages[$lang]))
        $dcms->language = $lang;

    $dcms->title = text::for_name($_POST ['title']);
    $dcms->sitename = text::for_name($_POST ['sitename']);
    $dcms->copyright = text::input_text($_POST ['copyright']);
    $dcms->system_nick = text::for_name($_POST ['system_nick']);
    $dcms->save_settings($doc);
}


$form = new form('?' . passgen());
$form->text('title', __('Заголовок по-умолчанию'), $dcms->title);
$form->text('sitename', __('Название сайта'), $dcms->sitename);
$form->text('system_nick', __('Системный ник') . ' *', $dcms->system_nick);

foreach ($browser_types as $b_types) {
    $key = 'theme_' . $b_types;
    $options = array();
    $themes_list = themes::getList($b_types);
    foreach ($themes_list as $theme)
        $options [] = array($theme ['dir'], $theme ['name'], $dcms->$key === $theme ['dir']);
    $form->select($key, __('Тема оформления') . ' (' . $b_types . ')', $options);
}

$options = array();
foreach ($languages as $key => $l) {
    $options [] = array($key, $l['name'], $dcms->language === $key);
}
$form->select('language', __('Язык по-умолчанию'), $options);

$form->checkbox('new_time_as_date', __('Новые файлы (темы и т.д.) за текущие сутки') . ' **', $dcms->new_time_as_date);
$form->checkbox('debug', __('Режим разработчика') . ' ***', $dcms->debug);
$form->checkbox('align_html', __('Выравнивание HTML кода'), $dcms->align_html);
$form->checkbox('censure', __('Антимат') . ' ****', $dcms->censure);
$form->text('copyright', __('Копирайт'), $dcms->copyright);

$form->bbcode('* - ' . __('Будет заключен в квадратные скобки'));
$form->bbcode('** - ' . __('В противном случае за последние 24 часа'));
$form->bbcode('*** - [url=/faq.php?info=debug]' . __('Информация о режиме разработчика') . '[/url]');
$form->bbcode('**** - ' . __('Только для Русского языка'));
$form->button(__('Применить'), 'save');
$form->display();

$doc->ret(__('Админка'), '/dpanel/');
?>
