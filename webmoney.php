<?php

include_once 'sys/inc/start.php';
$doc = new document(1);
$doc->title = __('Подтверждение WMID');


if ($user->wmid) {
    $doc->err(__('Активированный WMID изменять и удалять запрещено'));
}

if (!$user->wmid && isset($_GET['openid_mode'])) {



    if ($_GET['openid_mode'] == 'id_res') {  // Perform HTTP Request to OpenID server to validate key
        $openid = new SimpleOpenID;
        $openid->SetIdentity($_GET['openid_identity']);
        $openid_validation_result = $openid->ValidateWithServer();
        if ($openid_validation_result == true) {   // OK HERE KEY IS VALID
            if (preg_match('#^http://([0-9]{12})\.wmkeeper\.com#i', $_GET['openid_identity'], $m)) {
                $user->wmid = $m[1];
                $user->wmid_tmp = '';
                $user->wmid_tmp_code = '';
                $doc->msg(__('WMID %s успешно подтвержден', $user->wmid));
            } else {
                $doc->err(__('Ошибка при идентификации'));
            }
        } else if ($openid->IsError() == true) {   // ON THE WAY, WE GOT SOME ERROR
            $error = $openid->GetError();
            $doc->err($error['description']);
        } else {           // Signature Verification Failed
            $doc->err(__('Ошибка при авторизации'));
        }
    } else if ($_GET['openid_mode'] == 'cancel') { // User Canceled your Request
        $doc->err(__('Запрос отменен пользователем'));
    }
}



if (!$user->wmid && !empty($_POST['openidwm'])) { // Get identity from user and redirect browser to OpenID Server
    $openid = new SimpleOpenID;
    $openid->SetIdentity($_POST['openidwm'] . '.wmkeeper.com');
    $openid->SetTrustRoot('http://' . $_SERVER["HTTP_HOST"]);
    //$openid->SetRequiredFields(array('email'));
    //$openid->SetOptionalFields(array('dob', 'gender', 'postcode', 'country', 'language', 'timezone'));
    if ($openid->GetOpenIDServer()) {
        $openid->SetApprovedURL('http://' . $_SERVER["HTTP_HOST"] . '/webmoney.php');   // Send Response from OpenID server to this script
        $openid->Redirect();  // This will redirect user to OpenID Server
    } else {
        $error = $openid->GetError();

        $doc->err($error['description']);
    }
    exit;
}


$form = new design ();
$form->assign('method', 'post');
$form->assign('action', '?' . passgen());
$elements = array();
$elements [] = array('type' => 'input_text', 'title' => __('WebMoney ID'), 'br' => 0, 'info' => array('name' => 'openidwm', 'value' => $user->wmid ? $user->wmid : $user->wmid_tmp, 'disabled' => $user->wmid));
$elements [] = array('type' => 'text', 'br' => 1, 'value' => '.wmkeeper.com');

if (!$user->wmid) {
    $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'save', 'value' => __('Авторизоваться'))); // кнопка
}


$form->assign('el', $elements);
$form->display('input.form.tpl');
$doc->ret(__('Личное меню'), '/menu.user.php');
?>