<?php

include_once '../sys/inc/start.php';
$json = array();

if (isset($_POST['send'])) {
    if (!$user->is_writeable) {
        $json['err'] = __('Писать запрещено');
    } elseif (isset($_POST['message']) && $user->group) {
        $message = (string) $_POST['message'];
        $users_in_message = text::nickSearch($message);
        $message = text::input_text($message);

        if ($dcms->censure && $mat = is_valid::mat($message)) {
            $json['err'] = __('Обнаружен мат: %s', $mat);
        } elseif ($message) {
            $user->balls++;
            mysql_query("INSERT INTO `chat_mini` (`id_user`, `time`, `message`) VALUES ('$user->id', '" . TIME . "', '" . my_esc($message) . "')");

            $json['msg'] = __('Сообщение успешно отправлено');

        } else {
            $json['err'] = __('Сообщение пусто');
        }
    }
    else{
        $json['err'] = __('Неизвестная ошибка');
    }
    $json['form']['message'] = '';
} else {

    $skip_ids = explode(',', @$_POST['skip_ids']);

    $json['remove'] = $skip_ids;
    $json['add'] = array();

    $pages = new pages(mysql_result(mysql_query("SELECT COUNT(*) FROM `chat_mini`"), 0));
    $pages->this_page(); // получаем текущую страницу

    $q = mysql_query("SELECT * FROM `chat_mini` ORDER BY `id` DESC LIMIT $pages->limit");

    $after_id = false;
    while ($message = mysql_fetch_assoc($q)) {
        $id_post = 'chat_post_' . $message['id'];

        if (in_array($id_post, $skip_ids)) {
            $key = array_search($id_post, $json['remove']);
            unset($json['remove'][$key]);
        } else {
            $ank = new user($message['id_user']);
            $post = new listing_post();
            $post->id = $id_post;
            $post->url = 'actions.php?id=' . $message['id'];
            $post->time = vremja($message['time']);
            $post->title = $ank->nick();
            $post->post = output_text($message['message']);
            $post->icon($ank->icon());

            $json['add'][] = array(
                'after_id' => $after_id,
                'html' => $post->fetch()
            );
        }
        $after_id = $id_post;
    }
    $json['remove'] = array_values($json['remove']);
}

header('Content-type: application/json; charset=utf-8', true);
echo json_encode($json);
?>
