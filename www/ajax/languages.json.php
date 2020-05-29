<?php
include '../sys/inc/start.php';
header('Content-type: application/json');

echo json_encode(languages::getList());
?>