<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$doc = new document(groups::max());
$doc->title = __('Поддомены');

$browser_types = array('wap', 'pda', 'itouch', 'web');

if (!$dcms->check_domain_work)
    $dcms->check_domain_work = passgen();

function domain_check($domain) {
    global $dcms;
    $http = new http_client('http://' . $domain . '/?check_domain_work');
    $http->timeout = 10;
    return $dcms->check_domain_work === $http->getContent();
}

if (isset($_POST ['save'])) {

    $subdomain_theme_redirect_old = $dcms->subdomain_theme_redirect;
    $dcms->subdomain_theme_redirect = (int) !empty($_POST ['subdomain_theme_redirect']);
    $dcms->subdomain_replace_url = (int) !empty($_POST ['subdomain_replace_url']);

    $subdomain_wap_enable_old = $dcms->subdomain_wap_enable;
    $dcms->subdomain_wap_enable = (int) !empty($_POST ['subdomain_wap_enable']);

    $subdomain_pda_enable_old = $dcms->subdomain_pda_enable;
    $dcms->subdomain_pda_enable = (int) !empty($_POST ['subdomain_pda_enable']);

    $subdomain_itouch_enable_old = $dcms->subdomain_itouch_enable;
    $dcms->subdomain_itouch_enable = (int) !empty($_POST ['subdomain_itouch_enable']);

    $subdomain_web_enable_old = $dcms->subdomain_web_enable;
    $dcms->subdomain_web_enable = (int) !empty($_POST ['subdomain_web_enable']);


    $dcms->subdomain_main = text::input_text($_POST ['subdomain_main']);    
    
    $subdomain_wap_old = $dcms->subdomain_wap;
    $dcms->subdomain_wap = text::input_text($_POST ['subdomain_wap']);

    $subdomain_pda_old = $dcms->subdomain_pda;
    $dcms->subdomain_pda = text::input_text($_POST ['subdomain_pda']);

    $subdomain_itouch_old = $dcms->subdomain_itouch;
    $dcms->subdomain_itouch = text::input_text($_POST ['subdomain_itouch']);

    $subdomain_web_old = $dcms->subdomain_web;
    $dcms->subdomain_web = text::input_text($_POST ['subdomain_web']);

    if ($dcms->subdomain_theme_redirect && $dcms->subdomain_theme_redirect != $subdomain_theme_redirect_old) {
        if (!$dcms->subdomain_main) {
            $doc->err(__('Основной домен не введен'));
            $dcms->subdomain_theme_redirect = 0;
        } elseif (!domain_check($dcms->subdomain_main)) {
            $doc->err(__('Основной домен не открывает данный сайт'));
            $dcms->subdomain_theme_redirect = 0;
        }
    }

    if ($dcms->subdomain_wap_enable && ($dcms->subdomain_wap_enable != $subdomain_wap_enable_old || $subdomain_wap_old != $dcms->subdomain_wap )) {
        if (!$dcms->subdomain_wap) {
            $doc->err(__('Поддомен для WAP тем оформления не задан'));
            $dcms->subdomain_wap_enable = 0;
        } elseif (!domain_check($dcms->subdomain_wap . '.' . $dcms->subdomain_main)) {
            $doc->err(__('Поддомен для WAP тем оформления не открывает данный сайт'));
            $dcms->subdomain_wap_enable = 0;
        }
    }

    if ($dcms->subdomain_pda_enable && ( $dcms->subdomain_pda_enable != $subdomain_pda_enable_old || $subdomain_pda_old != $dcms->subdomain_pda )) {
        if (!$dcms->subdomain_pda) {
            $doc->err(__('Поддомен для PDA тем оформления не задан'));
            $dcms->subdomain_pda_enable = 0;
        } elseif (!domain_check($dcms->subdomain_pda . '.' . $dcms->subdomain_main)) {
            $doc->err(__('Поддомен для PDA тем оформления не открывает данный сайт'));
            $dcms->subdomain_pda_enable = 0;
        }
    }
    if ($dcms->subdomain_itouch_enable && ($dcms->subdomain_itouch_enable != $subdomain_itouch_enable_old || $subdomain_itouch_old != $dcms->subdomain_itouch )) {
        if (!$dcms->subdomain_itouch) {
            $doc->err(__('Поддомен для iTouch тем оформления не задан'));
            $dcms->subdomain_itouch_enable = 0;
        } elseif (!domain_check($dcms->subdomain_itouch . '.' . $dcms->subdomain_main)) {
            $doc->err(__('Поддомен для iTouch тем оформления не открывает данный сайт'));
            $dcms->subdomain_itouch_enable = 0;
        }
    }

    if ($dcms->subdomain_web_enable && ($dcms->subdomain_web_enable != $subdomain_web_enable_old || $subdomain_web_old != $dcms->subdomain_web )) {
        if (!$dcms->subdomain_web) {
            $doc->err(__('Поддомен для WEB тем оформления не задан'));
            $dcms->subdomain_web_enable = 0;
        } elseif (!domain_check($dcms->subdomain_web . '.' . $dcms->subdomain_main)) {
            $doc->err(__('Поддомен для WEB тем оформления не открывает данный сайт'));
            $dcms->subdomain_web_enable = 0;
        }
    }


    $dcms->save_settings($doc);
}


$form = new form('?' . passgen());
$form->text('subdomain_main', __('Основной домен'), $dcms->subdomain_main);
$form->checkbox('subdomain_theme_redirect', __('При переходе на главный домен переадресовывать на поддомен в соответствии с автоматически определенным типом браузера'), $dcms->subdomain_theme_redirect);
$form->checkbox('subdomain_replace_url', __('Удалять поддомен из ссылок'), $dcms->subdomain_replace_url);

foreach ($browser_types as $b_type) {
    $key_subdomain = 'subdomain_' . $b_type;
    $key_enable = 'subdomain_' . $b_type . '_enable';
    $form->text($key_subdomain, __('Поддомен %s (*.%s)', strtoupper($b_type), $dcms->subdomain_main), $dcms->$key_subdomain);
    $form->checkbox($key_enable, __('Выбирать %s тему при переходе по данному поддомену', strtoupper($b_type)), $dcms->$key_enable);
}

$form->button(__('Применить'), 'save');
$form->display();

$doc->ret(__('Админка'), '/dpanel/');
?>
