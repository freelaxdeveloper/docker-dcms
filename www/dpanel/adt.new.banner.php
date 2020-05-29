<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$advertisement = new adt();
$doc = new document(5);
$doc->title = __('Новый баннер');

if (!isset($_GET['id'])) {
    header('Refresh: 1; url=adt.php');
    $doc->ret(__('Реклама и баннеры'), 'adt.php');
    $doc->ret(__('Админка'), '/dpanel/');
    $doc->err(__('Ошибка выбора позиции'));

    exit;
}
$id_space = (string) $_GET['id'];

if (!$name = $advertisement->getNameById($id_space)) {
    header('Refresh: 1; url=?');
    $doc->err(__('Выбраная позиция отсутствует'));
    exit;
}

if (isset($_POST['create'])) {
    $code_main = text::input_text(@$_POST['code_main']);
    $code_other = text::input_text(@$_POST['code_other']);
    $pattern = '#<a +href\="(.+?)"><img +src\="(.+?)" *(alt\="(.+?)")? ?/></a>#ui';

    if (empty($_POST['captcha']) || empty($_POST['captcha_session']) || !captcha::check($_POST['captcha'], $_POST['captcha_session'])) {
        $doc->err(__('Проверочное число введено неверно'));
    } else {
        if (preg_match($pattern, $code_main, $main)) {
            $dcms->log('Реклама', 'Установка баннера [url=/dpanel/adt.php?id=' . $id_space . ']' . $main[1] . '[/url]');

            if ($code_main == $code_other) {
                mysql_query("INSERT INTO `advertising` (`space`, `url_link`, `name`, `url_img`, `page_main`, `page_other`, `time_create`, `time_start`, `time_end`, `bold`)
VALUES ('" . my_esc($id_space) . "', '" . my_esc($main[1]) . "', '" . my_esc($main[4]) . "', '" . my_esc($main[2]) . "', '1', '1', '" . TIME . "', '0', '0', '0')");
                header('Refresh: 1; url=adt.settings.php?id=' . $id_space);

                $doc->msg(__('Баннер успешно установлен'));
                $doc->ret(__('Вернуться'), "adt.php?id=$id_space");
                $doc->ret(__('Рекламные позиции'), 'adt.php');
                $doc->ret(__('Админка'), '/dpanel/');
                exit;
            } else {
                mysql_query("INSERT INTO `advertising` (`space`, `url_link`, `name`, `url_img`, `page_main`, `page_other`, `time_create`, `time_start`, `time_end`, `bold`)
VALUES ('" . my_esc($id_space) . "', '" . my_esc($main[1]) . "', '" . my_esc($main[4]) . "', '" . my_esc($main[2]) . "', '1', '0', '" . TIME . "', '0', '0', '0')");
                $doc->msg(__('Баннер для главной страницы успешно установлен'));

                if (preg_match($pattern, $code_other, $other)) {
                    mysql_query("INSERT INTO `advertising` (`space`, `url_link`, `name`, `url_img`, `page_main`, `page_other`, `time_create`, `time_start`, `time_end`, `bold`)
VALUES ('" . my_esc($id_space) . "', '" . my_esc($other[1]) . "', '" . my_esc($other[4]) . "', '" . my_esc($other[2]) . "', '0', '1', '" . TIME . "', '0', '0', '0')");
                    $doc->msg(__('Баннер для остальных страниц успешно установлен'));
                }

                header('Refresh: 1; url=adt.php?id=' . $id_space);

                $doc->ret(__('Вернуться'), "adt.php?id=$id_space");
                $doc->ret(__('Рекламные позиции'), 'adt.php');
                $doc->ret(__('Админка'), '/dpanel/');
                exit;
            }
        }else
            $doc->err(__('Невозможно разобрать код'));
    }
}

$form = new form("?id=$id_space&amp;" . passgen());
$form->textarea('code_main', __('HTML - код (для главной)'));
$form->textarea('code_other', __('HTML - код (для остальных)'));
$form->captcha();
$form->bbcode('[notice] ' . __('Распознаются коды счетчиков waplog.net и подобных'));
$form->button(__('Создать'), 'create');
$form->display();

$doc->ret(__('Вернуться'), "adt.php?id=$id_space");
$doc->ret(__('Рекламные позиции'), 'adt.php');
$doc->ret(__('Админка'), '/dpanel/');
?>
