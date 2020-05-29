<?php

/**
 * Возвращает ник пользователя с ссылкой на анкету
 * Использовать как $user->show()
 * @param \user $user
 * @param array $args
 * @return string
 */
function user_show($user, $args) {

    if ($user->id !== false) {
        return '<a href="/profile.view.php?id=' . $user->id . '">' . $user->nick() . '</a>';
    } else {
        return '[Нет данных]';
    }
}

?>
