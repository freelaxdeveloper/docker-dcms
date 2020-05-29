<?php

/**
 * Получение аватара пользователя
 * Использовать как $user->getAvatar([макс. ширина аватара в пикселях])
 * @param \user $user
 * @param array $args
 * @return string
 */
function user_getAvatar($user, $args) {
    $max_width = empty($args[0]) ? 48 : $args[0];

    $avatar_file_name = $user->id . '.jpg';
    $avatars_path = FILES . '/.avatars'; // папка с аватарами
    $avatars_dir = new files($avatars_path);
    if ($avatars_dir->is_file($avatar_file_name)) {
        $avatar = new files_file($avatars_path, $avatar_file_name);
        return $avatar->getScreen($max_width, 0);
    }
}

?>
