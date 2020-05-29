<?php

/**
 * Проверка на то, является ли пользователь другом
 * Использовать как $user->is_friend($ank)
 * @param \user $user
 * @param array $args
 * @return boolean
 */
function user_is_friend($user, $args) {
    if (!$user->id) {
        return false;
    }

    $ank = empty($args[0]) ? false : $args[0];

    if (!is_object($ank)) {
        $ank = $user;
    }


    if (!$ank->id) {
        return false;
    }


    if ($user->id && $user->id === $ank->id) {
        return true;
    }
    return mysql_result(mysql_query("SELECT COUNT(*) FROM `friends` WHERE `id_user` = '{$user->id}' AND `id_friend` = '{$ank->id}' AND `confirm` = '1' LIMIT 1"), 0);
}

?>
