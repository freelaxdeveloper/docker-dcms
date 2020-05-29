<?php

include_once '../sys/inc/start.php';
$groups = groups::load_ini(); // загружаем массив групп
$doc = new document();
$doc->title = __('Редактирование темы');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора темы'));
    exit;
}
$id_theme = (int) $_GET['id'];

$q = mysql_query("SELECT * FROM `forum_themes` WHERE `id` = '$id_theme' AND `group_edit` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Тема не доступна для редактирования'));
    exit;
}

$theme = mysql_fetch_assoc($q);

if (isset($_POST['save'])) {

    $t['top'] = (int) !empty($_POST['top']);
    if ($t['top'] != $theme['top']) {
        $theme['top'] = $t['top'];
        mysql_query("UPDATE `forum_themes` SET `top` = '$theme[top]' WHERE `id` = '$theme[id]' LIMIT 1");
        if ($theme['top'])
            $doc->msg(__('Тема успешно закреплена'));
        else
            $doc->msg(__('Тема успешно откреплена'));
    }

    if (isset($_POST['group_show'])) { // просмотр
        $group_show = (int) $_POST['group_show'];
        if (isset($groups[$group_show]) && $group_show != $theme['group_show']) {
            $theme_dir = new files(FILES . '/.forum/' . $theme['id']);
            $theme_dir->setGroupShowRecurse($group_show); // данный параметр необходимо применять рекурсивно

            $theme['group_show'] = $group_show;
            mysql_query("UPDATE `forum_themes` SET `group_show` = '$theme[group_show]' WHERE `id` = '$theme[id]' LIMIT 1");
            $doc->msg(__('Читать тему теперь разрешено группе "%s" и выше', groups::name($group_show)));
        }
    }

    if (isset($_POST['group_write'])) { // запись
        $group_write = (int) $_POST['group_write'];
        if (isset($groups[$group_write]) && $group_write != $theme['group_write']) {
            if ($theme['group_show'] > $group_write)
                $doc->err(__('Для того, чтобы оставлять сообщения группе "%s" сначала необходимо дать права на чтение темы', groups::name($group_write)));
            else {
                $theme['group_write'] = $group_write;
                mysql_query("UPDATE `forum_themes` SET `group_write` = '$theme[group_write]' WHERE `id` = '$theme[id]' LIMIT 1");
                $doc->msg(__('Оставлять сообщения в теме теперь разрешено группе "%s" и выше', groups::name($group_write)));
            }
        }
    }

    if (isset($_POST['group_edit'])) { // редактирование
        $group_edit = (int) $_POST['group_edit'];
        if (isset($groups[$group_edit]) && $group_edit != $theme['group_edit']) {
            if ($theme['group_write'] > $group_edit)
                $doc->err(__('Для изменения параметров темы группе "%s" сначала необходимо дать права на запись в тему', groups::name($group_edit)));
            else {
                $theme['group_edit'] = $group_edit;
                mysql_query("UPDATE `forum_themes` SET `group_edit` = '$theme[group_edit]' WHERE `id` = '$theme[id]' LIMIT 1");
                $doc->msg(__('Изменять параметры темы теперь разрешено группе "%s" и выше', groups::name($group_edit)));
            }
        }
    }
    // после изменения параметров темы обновляем данные, сохраненные в базе
    $q = mysql_query("SELECT * FROM `forum_themes` WHERE `id` = '$id_theme' AND `group_edit` <= '$user->group'");
    if (!mysql_num_rows($q)) {
        header('Refresh: 1; url=./');
        $doc->err(__('Тема не доступна для редактирования'));
        exit;
    }
    $theme = mysql_fetch_assoc($q);
}

$doc->title = __('Редактирование темы "%s"', $theme['name']); // шапка страницы

$form = new form("?id=$theme[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));

$options = array();
foreach ($groups as $type => $value)
    $options[] = array($type, $value['name'], $type == $theme['group_show']);
$form->select('group_show', __('Чтение темы'), $options);


$options = array();
foreach ($groups as $type => $value) {
    if ($type < 1)// гостям писать в тему уж точно запрещено        
        continue;
    $options[] = array($type, $value['name'], $type == $theme['group_write']);
}
$form->select('group_write', __('Пишут в тему'), $options);

$options = array();
foreach ($groups as $type => $value) {
    if ($type < 2) // Изменять параметры темы пользователю тоже нельзя        
        continue;
    $options[] = array($type, $value['name'], $type == $theme['group_edit']);
}
$form->select('group_edit', __('Изменяют параметры'), $options);
$form->bbcode('* ' . __('Будьте внимательнее при установке доступа выше своего.'));
$form->checkbox('top', __('Всегда наверху'), $theme['top']);
$form->button(__('Применить'), 'save');
$form->display();

$doc->ret(__('Действия'), 'theme.actions.php?id=' . $theme['id']);
$doc->ret(__('Вернуться в тему'), 'theme.php?id=' . $theme['id']);
$doc->ret(empty($theme['topic_name']) ? __('В раздел') : $theme['topic_name'], 'topic.php?id=' . $theme['id_topic']);
$doc->ret(empty($theme['category_name']) ? __('В категорию') : $theme['category_name'], 'category.php?id=' . $theme['id_category']);
$doc->ret(__('Форум'), './');
?>