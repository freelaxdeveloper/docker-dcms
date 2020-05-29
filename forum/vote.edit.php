<?php

include_once '../sys/inc/start.php';
$doc = new document();
$doc->title = __('Форум: Голосование');

if (!isset($_GET['id_theme']) || !is_numeric($_GET['id_theme'])) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора темы'));
    exit;
}
$id_theme = (int) $_GET['id_theme'];

$q = mysql_query("SELECT * FROM `forum_themes` WHERE `id` = '$id_theme' AND `group_edit` <= '$user->group'");

if (!mysql_num_rows($q)) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=theme.php?id=' . $theme['id']);
    $doc->err(__('Тема не доступна'));
    exit;
}

$theme = mysql_fetch_assoc($q);

if (empty($theme['id_vote'])) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=theme.php?id=' . $theme['id']);
    $doc->err(__('Голосование отсутствует'));
    exit;
}

$q = mysql_query("SELECT * FROM `forum_vote` WHERE `id` = '$theme[id_vote]'");

if (!mysql_num_rows($q)) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=theme.php?id=' . $theme['id']);
    $doc->err(__('Голосование отсутствует'));
    exit;
}

$vote_a = mysql_fetch_assoc($q);

if (!empty($_POST['vote'])) {
    $vote = text::input_text($_POST['vote']);
    if (!$vote)
        $doc->err(__('Поле "Вопрос" пусто'));
    else {
        $set = array();
        foreach ($_POST as $key => $value) {
            $vv = text::input_text($value);
            if ($vv && preg_match('#^v([0-9]+)$#', $key, $m)) {
                if ($m[1] > 0 || $m[1] <= 10)
                    $set[] = "`v$m[1]` = '" . my_esc($vv) . "'";
            }
        }
        $num = count($set);
        for ($i = 0; $i < (10 - $num); $i++)
            $set[] = "`v" . (10 - $i) . "` = null";

        if (count($set) < 2)
            $doc->err(__('Должно быть не менее 2-х вариантов ответа'));
        else {
            // echo mysql_error();
            if (isset($_GET['return']))
                header('Refresh: 1; url=' . $_GET['return']);
            else
                header('Refresh: 1; url=theme.php?id=' . $theme['id']);

            if (!empty($_POST['finish'])) {
                mysql_query("UPDATE `forum_vote` SET `active` = '0' WHERE `id` = '$vote_a[id]' LIMIT 1");
                $dcms->log('Форум', 'Закрытие голосования в теме [url=/forum/theme.php?id=' . $theme['id'] . ']' . $theme['name'] . '[/url]');
                $doc->msg(__('Голосование окончено'));
            } elseif (!empty($_POST['clear'])) {
                mysql_query("UPDATE `forum_vote` SET `active` = '1' WHERE `id` = '$vote_a[id]' LIMIT 1");
                mysql_query("DELETE FROM `forum_vote_votes` WHERE `id_vote` = '$vote_a[id]'");
                $dcms->log('Форум', 'Обнуление голосования в теме [url=/forum/theme.php?id=' . $theme['id'] . ']' . $theme['name'] . '[/url]');
                $doc->msg(__('Голосование начато заново'));
            } elseif (!empty($_POST['delete'])) {
                mysql_query("DELETE FROM `forum_vote`  WHERE `id` = '$vote_a[id]' LIMIT 1");
                mysql_query("DELETE FROM `forum_vote_votes` WHERE `id_vote` = '$vote_a[id]'");
                mysql_query("UPDATE `forum_themes` SET `id_vote` = null WHERE `id` = '$theme[id]' LIMIT 1");
                $dcms->log('Форум', 'Удаление голосования в теме [url=/forum/theme.php?id=' . $theme['id'] . ']' . $theme['name'] . '[/url]');
                $doc->msg(__('Голосование успешно удалено'));
            } else {
                $dcms->log('Форум', 'Изменение параметров голосования в теме [url=/forum/theme.php?id=' . $theme['id'] . ']' . $theme['name'] . '[/url]');
                mysql_query("UPDATE `forum_vote` SET " . implode(', ', $set) . ", `name` = '" . my_esc($vote) . "' WHERE `id` = '$vote_a[id]' LIMIT 1");
                $doc->msg(__('Параметры успешно изменены'));
            }

            if (isset($_GET['return']))
                $doc->ret('В тему', for_value($_GET['return']));
            else
                $doc->ret(__('В тему'), 'theme.php?id=' . $theme['id']);
            exit;
        }
    }
}

$form = new form("?id_theme=$theme[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$form->textarea('vote', __('Вопрос'), $vote_a['name']);
for ($i = 1; $i <= 10; $i++)
    $form->text("v$i", __('Ответ №') . $i, $vote_a['v' . $i]);
$form->checkbox('finish', __('Окончить голосование'));
$form->checkbox('clear', __('Начать заново'));
$form->checkbox('delete', __('Удалить голосование'));
$form->button(__('Применить'));
$form->display();

if (isset($_GET['return']))
    $doc->ret(__('В тему'), for_value($_GET['return']));
else
    $doc->ret(__('В тему'), 'theme.php?id=' . $theme['id']);
?>
