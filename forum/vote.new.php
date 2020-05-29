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

if (!empty($theme['id_vote'])) {
    if (isset($_GET['return']))
        header('Refresh: 1; url=' . $_GET['return']);
    else
        header('Refresh: 1; url=theme.php?id=' . $theme['id']);
    $doc->err(__('Голосование уже создано'));
    exit;
}

if (!empty($_POST['vote'])) {
    $vote = text::input_text($_POST['vote']);
    if (!$vote)
        $doc->err(__('Заполните поле "Вопрос"'));

    else {
        $v = array();
        $k = array();
        foreach ($_POST as $key => $value) {
            $vv = text::input_text($value);
            if ($vv && preg_match('#^v([0-9]+)$#', $key)) {
                $v[] = "'" . my_esc($vv) . "'";
                $k[] = '`v' . count($v) . '`';
            }
        }

        if (count($v) < 2)
            $doc->err(__('Должно быть не менее 2-х вариантов ответа'));
        else {
            mysql_query("INSERT INTO `forum_vote` (`id_autor`, `id_theme`, `name`, " . implode(', ', $k) . ")
VALUES ('$user->id', '$theme[id]', '$vote', " . implode(', ', $v) . ")");

            if (!$id_vote = mysql_insert_id())
                $doc->err(__('При создании голосования возникла ошибка'));
            else {
                if (isset($_GET['return']))
                    header('Refresh: 1; url=' . $_GET['return']);
                else
                    header('Refresh: 1; url=theme.php?id=' . $theme['id']);
                mysql_query("UPDATE `forum_themes` SET `id_vote` = '$id_vote' WHERE `id` = '$theme[id]' LIMIT 1");
                $doc->msg('Голосование успешно создано');

                $dcms->log('Форум', 'Создание голосования в теме [url=/forum/theme.php?id=' . $theme['id'] . ']' . $theme['name'] . '[/url]');

                if (isset($_GET['return']))
                    $doc->ret(__('В тему'), for_value($_GET['return']));
                else
                    $doc->ret(__('В тему'), 'theme.php?id=' . $theme['id']);
                exit;
            }
        }
    }
}

$form = new form("?id_theme=$theme[id]&amp;" . passgen() . (isset($_GET['return']) ? '&amp;return=' . urlencode($_GET['return']) : null));
$form->textarea('vote', __('Вопрос'));
for ($i = 1; $i <= 10; $i++)
    $form->text("v$i", __('Ответ №') . $i);
$form->button(__('Создать голосование'));
$form->display();

if (isset($_GET['return']))
    $doc->ret(__('В тему'), for_value($_GET['return']));
else
    $doc->ret(__('В тему'), 'theme.php?id=' . $theme['id']);
?>
