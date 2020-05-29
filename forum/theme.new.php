<?php

include_once '../sys/inc/start.php';
$doc = new document(1);

$doc->title = __('Новая тема');

if (!isset($_GET ['id_topic']) || !is_numeric($_GET ['id_topic'])) {
    if (isset($_GET ['return']))
        header('Refresh: 1; url=' . $_GET ['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('Ошибка выбора раздела'));
    exit();
}
$id_topic = (int) $_GET ['id_topic'];


$q = mysql_query("SELECT * FROM `forum_topics` WHERE `id` = '$id_topic' AND `group_write` <= '$user->group'");

if (!mysql_num_rows($q)) {
    if (isset($_GET ['return']))
        header('Refresh: 1; url=' . $_GET ['return']);
    else
        header('Refresh: 1; url=./');
    $doc->err(__('В выбранном разделе нельзя создавать темы'));
    exit();
}

$topic = mysql_fetch_assoc($q);

// лимит на создание тем
$timelimit = (empty($_SESSION ['antiflood'] ['newtheme']) || $_SESSION ['antiflood'] ['newtheme'] < TIME - 3600) ? true : false;
// запрет на создание тем без WMID
$check_wmid = (empty($topic ['theme_create_with_wmid']) || $user->wmid);


$time_reg = true;
if (!$user->is_writeable) {
    $doc->msg(__('Создавать темы запрещено'), 'write_denied');
    $time_reg = false;
}


if ($user->group >= 2) {
    $timelimit = true; // админ-составу разрешается создавать темы без ограничений по времени
    $check_wmid = true; // админ-составу разрешается создавать темы без ограничений WMID
}

if (!$timelimit) {
    $doc->err(__("Разрешается создавать темы не чаще одного раза в час"));
}

if (!$check_wmid) {
    $doc->err(__("В данном разделе разрешено создавать темы только с заполненным и активированным полем WMID в анкете"));
}


$can_write = $timelimit && $check_wmid && $time_reg;

if ($can_write && isset($_POST ['message']) && isset($_POST ['name'])) {
    $message = text::input_text($_POST ['message']);
    $name = text::for_name($_POST ['name']);

    if ($dcms->censure && $mat = is_valid::mat($message))
        $doc->err(__('Обнаружен мат: %s', $mat));
    elseif ($dcms->censure && $mat = is_valid::mat($name))
        $doc->err(__('Обнаружен мат: %s', $mat));
    elseif ($dcms->forum_theme_captcha && $user->group < 2 && (empty($_POST ['captcha']) || empty($_POST ['captcha_session']) || !captcha::check($_POST ['captcha'], $_POST ['captcha_session']))) {
        $doc->err(__('Проверочное число введено неверно'));
    } elseif ($message && $name) {
        $user->balls++;
        mysql_query("UPDATE `forum_topics` SET `time_last` = '" . TIME . "' WHERE `id` = '$id_topic' LIMIT 1");

        mysql_query("INSERT INTO `forum_themes` (`id_category`, `id_topic`,  `name`, `id_autor`, `time_create`, `id_last`, `time_last`, `group_show`, `group_write`, `group_edit`)
 VALUES ('$topic[id_category]','$topic[id]','" . my_esc($name) . "', '$user->id', '" . TIME . "','$user->id','" . TIME . "','$topic[group_show]','$topic[group_write]','" . max($user->group, 2) . "')");

        $theme ['id'] = mysql_insert_id();
        $theme = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum_themes` WHERE `id` = '$theme[id]' LIMIT 1"));
        mysql_query("INSERT INTO `forum_messages` (`id_category`, `id_topic`, `id_theme`, `id_user`, `time`, `message`, `group_show`, `group_edit`)
 VALUES ('$theme[id_category]','$theme[id_topic]','$theme[id]','$user->id','" . TIME . "','" . my_esc($message) . "','$theme[group_show]','$theme[group_edit]')");

        $_SESSION ['antiflood'] ['newtheme'] = TIME;
        $doc->msg(__('Тема успешно создана'));

        header('Refresh: 1; url=theme.php?id=' . $theme ['id']);
        $doc->ret(__('В тему'), 'theme.php?id=' . $theme ['id']);
        exit();
    } else {
        $doc->err(__('Сообщение или название темы пусто'));
    }
}

$doc->title = $topic ['name'] . ' - ' . __('Новая тема');

if ($can_write) {
    $form = new form("?id_topic=$topic[id]&amp;" . passgen() . (isset($_GET ['return']) ? '&amp;return=' . urlencode($_GET ['return']) : null));
    $form->text('name', __('Название темы'));
    $form->bbcode('* ' . __('Название темы должно быть информативным, четко выделяя ее среди других тем. [b]Названия вида "помогите", "как сделать" и т.д. строго запрещены.[/b]'));
    $form->textarea('message', __('Сообщение'));
    if ($dcms->forum_theme_captcha && $user->group < 2)
        $form->captcha();
    $form->button(__('Создать тему'));
    $form->display();
}

if (isset($_GET ['return']))
    $doc->ret(__('В раздел'), for_value($_GET ['return']));
else
    $doc->ret(__('В раздел'), 'theme.php?id=' . $theme ['id']);
?>
