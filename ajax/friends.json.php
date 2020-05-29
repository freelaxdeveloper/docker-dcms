<?php
include '../sys/inc/start.php';
$friends = array();

if ($user->id) {
    $q = mysql_query("SELECT * FROM `friends` WHERE `id_user` = '$user->id' ORDER BY `confirm` ASC, `time` DESC");
    while ($friend = mysql_fetch_assoc($q)) {
        $ank = new user($friend['id_friend']);
        $friends[] = array('confirm' => $friend['confirm'],
            'id_friend' => $friend['id_friend'],
            'login' => $ank->login,
            'online' => $ank->online,
            'time' => $friend['time']
        );
    }
}


header('Content-type: application/json');
echo json_encode($friends);
?>
