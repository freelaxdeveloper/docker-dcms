<?php

include_once 'sys/inc/start.php';
if (isset($_GET['url'])) {
    header('Location: ' . $_GET['url']);
    $urlwithouthttp = preg_replace('#^(https?|ftp)://#', '', $_GET['url']);
    dpanel::access_delete();
    if (!isset($_SESSION['adt'][$urlwithouthttp]['time_out']) || $_SESSION['adt'][$urlwithouthttp]['time_out'] < TIME - 600) {
        // переход по рекламе засчитывается один раз в 10 минут
        $_SESSION['adt'][$urlwithouthttp]['time_out'] = TIME;
        mysql_query("UPDATE `advertising` SET `count_out_" . $dcms->browser_type . "` = `count_out_" . $dcms->browser_type . "` + 1 WHERE `url_link` = '" . my_esc($_GET['url']) . "'");
    }
    exit;
} else {
    header('Location: /');
}
?>