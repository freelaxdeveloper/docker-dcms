<?php

/**
 * Получение иконки пользователя
 * Использовать как $user->icon()
 * @param \user $user
 * @return string
 */
function user_icon($user) {


    // система 
    if ($user->group === 6 && $user->id === 0) {
        return 'system';
    }

    // забаненый пользователь
    if ($user->is_ban) {
        return 'shit';
    }

// администратор
    if ($user->group >= 2) {
        return 'admin.' . $user->sex;
    }

    // пользователь
    if ($user->group) {
        return 'user.' . $user->sex;
    }

    // гость
    return 'guest';
}

?>
