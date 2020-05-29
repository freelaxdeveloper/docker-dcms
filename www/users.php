<?php

include_once 'sys/inc/start.php';
$doc = new document();
$doc->title = __('Пользователи');

switch (@$_GET['order']) {
    case 'group':$order = 'group';
        $sort = 'DESC';
        $where = "WHERE `group` >= '2'";
        $doc->title = __('Администрация');
        break;
    case 'login':$order = 'login';
        $sort = 'ASC';
        $where = '';
        break;
    case 'balls':$order = 'balls';
        $sort = 'DESC';
        $where = '';
        break;
    case 'rating':$order = 'rating';
        $sort = 'DESC';
        $where = '';
        break;
    default:$order = 'id';
        $sort = 'DESC';
        $where = '';
        break;
}

if (!empty($_GET['search']))
    $search = text::input_text($_GET['search']);
if (isset($search) && !$search)
    $doc->err(__('Пустой запрос'));
elseif (isset($search) && $search) {
    $where = "WHERE `login` LIKE '%" . my_esc($search) . "%'";
    $doc->title = __('Поиск по запросу "%s"', $search);
}

$posts = array();
$pages = new pages;
$pages->posts = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` $where"), 0);
$pages->this_page(); // получаем текущую страницу
// меню сортировки
$ord = array();
$ord[] = array("?order=id&amp;page={$pages->this_page}" . (isset($search) ? '&amp;search=' . urlencode($search) : ''), __('ID пользователя'), $order == 'id');
$ord[] = array("?order=login&amp;page={$pages->this_page}" . (isset($search) ? '&amp;search=' . urlencode($search) : ''), __('Логин'), $order == 'login');
$ord[] = array("?order=rating&amp;page={$pages->this_page}" . (isset($search) ? '&amp;search=' . urlencode($search) : ''), __('Рейтинг'), $order == 'rating');
$ord[] = array("?order=balls&amp;page={$pages->this_page}" . (isset($search) ? '&amp;search=' . urlencode($search) : ''), __('Баллы'), $order == 'balls');
$ord[] = array("?order=group&amp;page={$pages->this_page}" . (isset($search) ? '&amp;search=' . urlencode($search) : ''), __('Статус'), $order == 'group');
$or = new design();
$or->assign('order', $ord);
$or->display('design.order.tpl');

$q = mysql_query("SELECT `id` FROM `users` $where ORDER BY `$order` $sort LIMIT $pages->limit");


$listing = new listing();
while ($ank = mysql_fetch_assoc($q)) {
    $post = $listing->post();
    $p_user = new user($ank['id']);

    $post->icon($p_user->icon());
    $post->title = $p_user->nick();
    $post->url = '/profile.view.php?id=' . $p_user->id;


    $post->content = $order == 'id' ? __('ID пользователя') . ': ' . $p_user->id . "<br />\n" : '';

    if ($order == 'group')
        $post->content .= __($p_user->group_name) . "<br />\n";
    if ($order == 'balls')
        $post->content .= __('Баллы') . ': ' . ((int) $p_user->balls) . "<br />\n";
    if ($order == 'rating')
        $post->content .= __('Рейтинг') . ': ' . $p_user->rating . "<br />\n";
    $post->content .= __('Дата регистрации') . ': ' . date('d-m-Y', $p_user->reg_date) . "<br />\n";
    $post->content .= __('Последний визит') . ': ' . vremja($p_user->last_visit) . '<br />';
}

$smarty = new design();
$smarty->assign('method', 'get');
$smarty->assign('action', '?');
$elements = array();
$elements[] = array('type' => 'hidden', 'info' => array('name' => 'order', 'value' => $order));
$elements[] = array('type' => 'input_text', 'title' => __('Ник или его часть'), 'br' => 0, 'info' => array('name' => 'search', 'value' => @$search));
$elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('value' => __('Поиск'))); // кнопка
$smarty->assign('el', $elements);
$smarty->display('input.form.tpl');


$listing->display(__('Нет пользователей'));


$pages->display("?order=$order&amp;" . (isset($search) ? 'search=' . urlencode($search) . '&amp;' : '')); // вывод страниц
?>
