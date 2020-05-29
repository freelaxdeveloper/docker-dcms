<?php

include '../sys/inc/start.php';
$mail = array();

$ank = new user((int)@$_GET['id_user']);

if ($user->group && $ank->group) {

    // только непрочитанные
    $only_unreaded = (int) isset($_GET['only_unreaded']);
    // отмечать все письма как прочитанные
    $set_readed = (int) isset($_GET['set_readed']);
    // с выбранного времени
    $time_from = (int) @$_GET['time_from'];



    if ($set_readed) {
        // отмечаем письма от этого человека как прочитанные
        mysql_query("UPDATE `mail` SET `is_read` = '1' WHERE `id_user` = '{$user->id}' AND `id_sender` = '{$ank->id}'");
    }
    
    $q = mysql_query("SELECT * FROM `mail` WHERE `time` > '$time_from'".($only_unreaded?' AND `is_read` = 0':'')." AND ((`id_user` = '{$user->id}' AND `id_sender` = '{$ank->id}') OR (`id_user` = '{$ank->id}' AND `id_sender` = '{$user->id}')) ORDER BY `id` DESC");
        
    while ($qmail = mysql_fetch_assoc($q)) {
        $mail[] = array('id' => $qmail['id'],
            'id_sender' => $qmail['id_sender'],
            'id_user' => $qmail['id_user'],
            'mess' => output_text($qmail['mess']),
            'time' => $qmail['time'],
            'is_read' => $qmail['is_read']   
        );
    }
    
    
}

header('Content-type: application/json');
echo json_encode($mail);
?>
