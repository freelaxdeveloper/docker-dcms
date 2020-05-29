<?php

/**
 * Отправляем пользователю личное сообщение
 * Использовать как $user->mess(сообщение, [id отправителя. по умолчанию система])
 * @param \user $user
 * @param array $args
 * @return boolean
 */
function user_mess($user, $args) {
    if (!$user->id) {
        return false;
    }
    $msg = $args[0];
    $id_user = empty($args[1]) ? 0 : $args[1];

    mysql_query("UPDATE `users` SET `mail_new_count` = `mail_new_count` + '1' WHERE `id` = '" . $user->id . "' LIMIT 1");
    mysql_query("INSERT INTO `mail` (`id_user`,`id_sender`,`time`,`mess`)
VALUES ('" . $user->id . "', '$id_user', '" . TIME . "', '" . my_esc($msg) . "' )");
    return true;
}

?>
