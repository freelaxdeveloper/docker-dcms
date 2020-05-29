<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Изменение порядка категорий');


if (isset($_GET['sortable'])) {

    $sort = explode(',', $_POST['sortable']);
    $q = mysql_query("SELECT * FROM `forum_categories` WHERE `group_show` <= '$user->group' ORDER BY `position` ASC");
    while ($category = mysql_fetch_assoc($q)) {
        if (($position = array_search('cid' . $category['id'], $sort)) !== false) {
            mysql_query("UPDATE `forum_categories` SET `position` = '$position' WHERE `id` = '{$category['id']}'");
        }
    }

    $doc->clean();
    header('Content-type: application/json');
    echo json_encode(array('result' => 1, 'description' => __('Порядок категорий успешно сохранен')));
    exit;
}

if (!empty($_GET['id']) && !empty($_GET['act'])) {
    $sort = array();
    $q = mysql_query("SELECT * FROM `forum_categories` WHERE `group_show` <= '$user->group' ORDER BY `position` ASC");
    while ($category = mysql_fetch_assoc($q)) {
        $sort[$category['id']] = $category['id'];
    }

    switch ($_GET['act']) {
        case 'up':
            if (misc::array_key_move($sort, $_GET['id'], - 1)) {
                $doc->msg(__('Категория успешно перемещена вверх'));
            }else
                $doc->err(__('Категория уже находится вверху'));
            break;

        case 'down':
            if (misc::array_key_move($sort, $_GET['id'], 1)) {

                $doc->msg(__('Категория успешно перемещена вниз'));
            }else
                $doc->err(__('Категория уже находится внизу'));

            break;
    }



    $q = mysql_query("SELECT * FROM `forum_categories` WHERE `group_show` <= '$user->group' ORDER BY `position` ASC");
    while ($category = mysql_fetch_assoc($q)) {
        if (($position = array_search('cid' . $category['id'], $sort)) !== false) {
            mysql_query("UPDATE `forum_categories` SET `position` = '$position' WHERE `id` = '{$category['id']}'");
        }
    }


    $doc->ret('Вернуться', '?' . passgen());
    header('Refresh: 1; url=?' . passgen());
    exit;
}




$listing = new listing();
$q = mysql_query("SELECT * FROM `forum_categories` WHERE `group_show` <= '$user->group' ORDER BY `position` ASC");
while ($category = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $post->id = 'cid' . $category['id'];
    //$post->url = "category.php?id=$category[id]";
    $post->title = for_value($category['name']);
    $post->icon('forum.category');
    $post->post = text::for_opis($category['description']);

    $post->action('up', '?id=' . $category['id'] . '&amp;act=up');
    $post->action('down', '?id=' . $category['id'] . '&amp;act=down');
}
$listing->sortable = '?sortable';
$listing->display(__('Доступных Вам категорий нет'));

$doc->ret(__('Форум'), 'index.php');
?>
