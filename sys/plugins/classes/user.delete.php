<?php

/**
 * Удаление юзера со всеми потрохами
 * использовать как $user->delete()
 * @param \user $ank текущий пользователь
 */
function user_delete($ank) {
    $tables = ini::read(H . '/sys/ini/user.tables.ini', true);
    foreach ($tables AS  $v) {
        mysql_query("DELETE FROM `" . my_esc($v['table']) . "` WHERE `" . my_esc($v['row']) . "` = '$ank->id'");
    }
    mysql_query("DELETE FROM `users` WHERE `id` = '$ank->id'");
}

?>
