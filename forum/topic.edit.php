<?php

include_once '../sys/inc/start.php';
$groups = groups::load_ini(); // загружаем массив групп
$doc = new document ();
$doc->title = __('Редактирование раздела');

if (!isset($_GET ['id']) || !is_numeric($_GET ['id'])) {
    header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора раздела'));
    exit();
}
$id_topic = (int) $_GET ['id'];

$q = mysql_query("SELECT * FROM `forum_topics` WHERE `id` = '$id_topic' AND `group_edit` <= '$user->group'");

if (!mysql_num_rows($q)) {
    header('Refresh: 1; url=./');
    $doc->err(__('Раздел не доступен для редактирования'));
    exit;
}

$topic = mysql_fetch_assoc($q);

if (isset($_POST ['save'])) {
    if (isset($_POST ['name'])) {
        $name = text::for_name($_POST ['name']);
        $description = text::input_text($_POST ['description']);

        if ($name && $name != $topic ['name']) {
            $dcms->log('Форум', 'Изменение названия раздела "' . $topic ['name'] . '" на [url=/forum/topic.php?id=' . $topic ['id'] . ']"' . $name . '"[/url]');
            $topic ['name'] = $name;
            mysql_query("UPDATE `forum_topics` SET `name` = '" . my_esc($topic ['name']) . "' WHERE `id` = '$topic[id]' LIMIT 1");
            $doc->msg(__('Название раздела успешно изменено'));
        }
    }

    if ($description != $topic ['description']) {
        $dcms->log('Форум', 'Изменение описания раздела [url=/forum/topic.php?id=' . $topic ['id'] . ']"' . $topic ['name'] . '"[/url]');
        $topic ['description'] = $description;
        mysql_query("UPDATE `forum_topics` SET `description` = '" . my_esc($topic ['description']) . "' WHERE `id` = '$topic[id]' LIMIT 1");
        $doc->msg(__('Описание раздела успешно изменено'));
    }

    if (isset($_POST ['category'])) {
        $category = (int) $_POST ['category'];
        $q = mysql_query("SELECT * FROM `forum_categories` WHERE `id` = '$category' AND `group_show` <= '$user->group' AND `group_write` <= '$user->group'");

        if (mysql_num_rows($q) && $category != $topic ['id_category']) {
            $category = mysql_fetch_assoc($q);
            $topic ['id_category'] = $category ['id'];
            $dcms->log('Форум', 'Перемещение раздела [url=/forum/topic.php?id=' . $topic ['id'] . ']' . $topic ['name'] . '[/url] в категорию [url=/forum/category.php?id=' . $category ['id'] . ']' . $category ['name'] . '[/url]');
            mysql_query("UPDATE `forum_topics` SET `id_category` = '$topic[id_category]' WHERE `id` = '$topic[id]' LIMIT 1");
            mysql_query("UPDATE `forum_themes` SET `id_category` = '$topic[id_category]' WHERE `id_topic` = '$topic[id]'");
            mysql_query("UPDATE `forum_messages` SET `id_category` = '$topic[id_category]' WHERE `id_topic` = '$topic[id]'");
            $doc->msg(__('Раздел успешно перемещен'));
        }
    }

    if (isset($_POST ['group_show'])) { // просмотр
        $group_show = (int) $_POST ['group_show'];
        if (isset($groups [$group_show]) && $group_show != $topic ['group_show']) {
            $topic ['group_show'] = $group_show;
            mysql_query("UPDATE `forum_topics` SET `group_show` = '$topic[group_show]' WHERE `id` = '$topic[id]' LIMIT 1");
            $doc->msg(__('Читать раздел теперь разрешено группе %s и выше', groups::name($group_show)));
            $dcms->log('Форум', 'Изменение прав чтения раздела [url=/forum/topic.php?id=' . $topic ['id'] . ']' . $topic ['name'] . '[/url] для группы ' . groups::name($group_show));
        }
    }

    if (isset($_POST ['group_write'])) { // запись
        $group_write = (int) $_POST ['group_write'];
        if (isset($groups [$group_write]) && $group_write != $topic ['group_write']) {
            if ($topic ['group_show'] > $group_write)
                $doc->err('Для того, чтобы создавать темы группе "' . groups::name($group_write) . '" сначала необходимо дать права на просмотр раздела');
            else {
                $topic ['group_write'] = $group_write;
                mysql_query("UPDATE `forum_topics` SET `group_write` = '$topic[group_write]' WHERE `id` = '$topic[id]' LIMIT 1");
                $doc->msg(__('Создавать темы в разделе теперь разрешено группе %s и выше', groups::name($group_write)));
                $dcms->log('Форум', 'Изменение прав создания тем в разделе [url=/forum/topic.php?id=' . $topic ['id'] . ']' . $topic ['name'] . '[/url] для группы ' . groups::name($group_write));
            }
        }
    }

    if (isset($_POST ['group_edit'])) { // редактирование
        $group_edit = (int) $_POST ['group_edit'];
        if (isset($groups [$group_edit]) && $group_edit != $topic ['group_edit']) {
            if ($topic ['group_write'] > $group_edit)
                $doc->err('Для изменения параметров раздела группе "' . groups::name($group_edit) . '" сначала необходимо дать права на создание тем');
            else {
                $topic ['group_edit'] = $group_edit;
                mysql_query("UPDATE `forum_topics` SET `group_edit` = '$topic[group_edit]' WHERE `id` = '$topic[id]' LIMIT 1");
                $doc->msg(__('Изменять параметры раздела теперь разрешено группе %s и выше', groups::name($group_edit)));
                $dcms->log('Форум', 'Изменение прав редактирования раздела [url=/forum/topic.php?id=' . $topic ['id'] . ']' . $topic ['name'] . '[/url] для группы ' . groups::name($group_edit));
            }
        }
    }

    $topic_theme_create_with_wmid = (int) !empty($_POST ['theme_create_with_wmid']);
    if ($topic_theme_create_with_wmid != $topic ['theme_create_with_wmid']) {
        $topic ['theme_create_with_wmid'] = $topic_theme_create_with_wmid;
        mysql_query("UPDATE `forum_topics` SET `theme_create_with_wmid` = '$topic[theme_create_with_wmid]' WHERE `id` = '$topic[id]' LIMIT 1");
        if ($topic ['theme_create_with_wmid']) {
            $doc->msg(__('Создавать темы в данном разделе теперь смогут только пользователи с активированным WMID'));
        } else {
            $doc->msg(__('Ограничение на создание тем без WMID снято'));
        }

        $dcms->log('Форум', 'Изменение ограничений WMID раздела [url=/forum/topic.php?id=' . $topic ['id'] . ']' . $topic ['name'] . '[/url]');
    }
}

$doc->title = __('Редактирование раздела "%s"', $topic ['name']); // шапка страницы

$form = new form("?id=$topic[id]&amp;" . passgen() . (isset($_GET ['return']) ? '&amp;return=' . urlencode($_GET ['return']) : null));
$form->text('name', __('Название'), $topic['name']);
$form->textarea('description', __('Описание'), $topic['description']);

$options = array();
$q = mysql_query("SELECT `id`,`name` FROM `forum_categories` WHERE `group_show` <= '$user->group' ORDER BY `position` ASC");
while ($category = mysql_fetch_assoc($q))
    $options [] = array($category ['id'], $category ['name'], $category ['id'] == $topic ['id_category']);
$form->select('category', __('Категория'), $options);

$options = array();
foreach ($groups as $type => $value)
    $options [] = array($type, $value ['name'], $type == $topic ['group_show']);
$form->select('group_show', __('Просмотр тем'), $options);

$options = array();
foreach ($groups as $type => $value)
    $options [] = array($type, $value ['name'], $type == $topic ['group_write']);

$form->select('group_write', __('Создание тем'), $options);

$options = array();
foreach ($groups as $type => $value)
    $options [] = array($type, $value ['name'], $type == $topic ['group_edit']);
$form->select('group_edit', __('Изменение параметров'), $options);

$form->bbcode('* ' . __('Будьте внимательнее при установке доступа выше своего.'));
$form->checkbox('theme_create_with_wmid', __('Создание тем только с WMID'), $topic['theme_create_with_wmid']);
$form->button(__('Применить'), 'save');
$form->display();

$doc->act(__('Удаление тем'), 'topic.themes.delete.php?id=' . $topic ['id']);
$doc->act(__('Удалить раздел'), 'topic.delete.php?id=' . $topic ['id']);

if (isset($_GET ['return']))
    $doc->ret(__('В раздел'), for_value($_GET ['return']));
else
    $doc->ret(__('В раздел'), 'topic.php?id=' . $topic ['id']);

$doc->ret(__('В категорию'), 'category.php?id=' . $topic ['id_category']);
$doc->ret(__('Форум'), './');
?>
