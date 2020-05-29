<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$advertisement = new adt();
$doc = new document(5);
$doc->title = __('Реклама и баннеры');

if (isset($_GET['id'])) {
    $id_space = (string) $_GET['id'];

    if (!$name = $advertisement->getNameById($id_space)) {
        header('Refresh: 1; url=?');
        $doc->ret(__('Вернуться'), '?');
        $doc->err(__('Выбраная позиция отсутствует'));
        exit;
    }

    $doc->title = __('Рекламные площадки');

    switch (@$_GET['filter']) {
        case 'new':$filter = 'new';
            $sql = " AND `time_start` > '" . TIME . "' AND (`time_end` > '" . TIME . "' OR `time_end` = '0')";
            break;
        case 'old':$filter = 'old';
            $sql = " AND (`time_start` < '" . TIME . "' OR `time_start` = '0') AND (`time_end` < '" . TIME . "' AND `time_end` != '0')";
            break;
        case 'all':$filter = 'all';
            $sql = '';
            break;
        default:$filter = 'active';
            $sql = " AND (`time_start` < '" . TIME . "' OR `time_start` = '0') AND (`time_end` > '" . TIME . "' OR `time_end` = '0')";
            break;
    }


    $pages = new pages;
    $pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `advertising` WHERE `space` = '$id_space'$sql"), 0);
    $pages->this_page(); // получаем текущую страницу
    // меню сортировки
    $ord = array();
    $ord[] = array("?id=$id_space&amp;filter=all&amp;page={$pages->this_page}", __('Все'), $filter == 'all');
    $ord[] = array("?id=$id_space&amp;filter=active&amp;page={$pages->this_page}", __('Активные'), $filter == 'active');
    $ord[] = array("?id=$id_space&amp;filter=old&amp;page={$pages->this_page}", __('Завершенные'), $filter == 'old');
    $ord[] = array("?id=$id_space&amp;filter=new&amp;page={$pages->this_page}", __('В ожидании'), $filter == 'new');
    $or = new design();
    $or->assign('order', $ord);
    $or->display('design.order.tpl');

    $listing = new listing();

    $q = mysql_query("SELECT * FROM `advertising` WHERE `space` = '$id_space'$sql ORDER BY `time_start` ASC LIMIT $pages->limit");
    while ($adt = mysql_fetch_assoc($q)) {
        $post = $listing->post();
        $post->url = 'adt.stat.php?id=' . $adt['id'];
        $post->title = for_value($adt['name'] ? $adt['name'] : 'Реклама #' . $adt['id']) . ($adt['url_img'] ? ' (' . __('баннер') . ')' : null);
        $post->icon('adt');
        $post->action('edit', "adt.edit.php?id={$adt['id']}");
        $post->action('delete', "adt.edit.php?id={$adt['id']}&amp;delete");

        if ($filter == 'all') {
            if ((!$adt['time_start'] || $adt['time_start'] < TIME) && (!$adt['time_end'] || $adt['time_end'] > TIME)) {
                $post->content[] = __('Реклама активна');
            } elseif ($adt['time_start'] > TIME && (!$adt['time_end'] || $adt['time_end'] > TIME)) {
                $post->content[] = __('В ожидании');
            } elseif ((!$adt['time_start'] || $adt['time_start'] < TIME) && $adt['time_end'] < TIME) {
                $post->content[] = __('Показ окончен');
            }
        }

        if ($adt['time_start'] > TIME) {
            $post->content[] = __("Начало показа: %s", vremja($adt['time_start']));
        }

        if (!$adt['time_end']) {
            $post->content[] = __('Бесконечный показ');
        } elseif ($adt['time_end'] > TIME) {
            $post->content[] = __("Конец показа: %s", vremja($adt['time_end']));
        } else {
            $post->content[] = __("Показ истек: %s", vremja($adt['time_end']));
        }

        if ($adt['bold']) {
            $post->content[] = "[b]" . __('Выделение жирным шрифтом') . "[/b]";
        }

        $post->content[] = __('Адрес ссылки: %s', for_value($adt['url_link']));
        if ($adt['url_img']) {
            $post->content[] = __('Адрес изображения: %s', for_value($adt['url_img']));
        }

        if ($adt['page_main'] && $adt['page_other']) {
            $post->content[] = __('На всех страницах');
        } elseif (!$adt['page_main'] && $adt['page_other']) {
            $post->content[] = __('Кроме главной');
        } elseif ($adt['page_main'] && !$adt['page_other']) {
            $post->content[] = __('Только на главной');
        } else {
            $post->content[] = __("Не отображается");
        }
    }
    $listing->display(__('Реклама отсутствует'));

    $pages->display("?id=$id_space&amp;filter=$filter&amp;"); // вывод страниц

    $doc->act(__('Создать площадку'), 'adt.new.php?id=' . $id_space);
    $doc->act(__('Добавить счетчик или баннер'), 'adt.new.banner.php?id=' . $id_space);
    $doc->ret(__('Рекламные позиции'), '?');
    $doc->ret(__('Админка'), '/dpanel/');
    exit;
}

$doc->title = __('Рекламные позиции');

$advertisement->display();

$doc->ret(__('Админка'), '/dpanel/');
?>
