<?php

/**
 * Возвращает ник пользователя
 * Использовать как $user->nick()
 * @param \user $user
 * @param array $args
 * @return string Ник пользователя
 */
function user_nick($user, $args) {

    if ($user->id === false) {
        return '[' . __('Пользователь удален') . ']';
    }

    return '<span class="' . ($user->online ? 'DCMS_nick_on' : 'DCMS_nick_off') . '">' . $user->login . '</span>';
}

?>
