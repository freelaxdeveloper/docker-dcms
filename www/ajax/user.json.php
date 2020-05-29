<?php

include '../sys/inc/start.php';

// ключи, которые будут переданы обязательно
$default = array('id');

// ключи, которые будут исключены
$skip = array('_', 'password', 'a_code', 'recovery_password', 'wmid_tmp_code');

// отправляемые данные
$data = array();

// все запрашиваемые ключи
$options = array_merge($default, array_keys(@$_GET));

foreach ($options as $key) {
    if (in_array($key, $skip)) {
        continue;
    }
    $data[$key] = $user->$key;
}


header('Content-type: application/json');
echo json_encode($data);
?>