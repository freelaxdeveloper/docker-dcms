<?php

class install_reg_admin {

    var $login = 'Administrator';
    var $pass1 = '';
    var $pass2 = '';
    var $err_login;
    var $err_pass1;
    var $err_pass2;
    var $settings = array();
    var $users_count = 0;
    var $adm_count = 0;

    function __construct() {
        db_connect();
        $this->err_login = &$_SESSION['reg_admin']['err_login'];
        $this->err_pass1 = &$_SESSION['reg_admin']['err_pass1'];
        $this->err_pass2 = &$_SESSION['reg_admin']['err_pass2'];

        $this->login = &$_SESSION['reg_admin']['login'];
        $this->pass1 = &$_SESSION['reg_admin']['pass1'];
        $this->pass2 = &$_SESSION['reg_admin']['pass2'];

        $this->settings = &$_SESSION['settings'];

        $this->users_count = mysql_result(mysql_query("SELECT COUNT(*) FROM `users`"), 0);
        $this->adm_count = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `group` > '1'"), 0);
    }

    function actions() {
        $this->err_login = false;
        $this->err_pass1 = false;
        $this->err_pass2 = false;

        $return = false;

        if (isset($_POST['login']))
            if (is_valid::nick($_POST['login'])) {
                $this->login = $_POST['login'];
                if (!mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `login` = '" . my_esc($this->login) . "'"), 0)) {
                    if (empty($_POST['password']))
                        $this->err_pass1 = true;
                    elseif (empty($_POST['password_retry']))
                        $this->err_pass2 = true;
                    elseif ($_POST['password_retry'] != $_POST['password']) {
                        $this->err_pass1 = true;
                        $this->err_pass2 = true;
                    } elseif (!is_valid::password($_POST['password'])) {
                        $this->err_pass1 = true;
                        $this->err_pass2 = true;
                    } else {
                        // если нет зарегистрированных пользователей, то генегируем новую соль
                        if (!$this->users_count)
                            $this->settings['salt'] = passgen();
                        // делаем всех админов простыми пользователями
                        if (!empty($_POST['clear_adm']))
                            mysql_query("UPDATE `users` SET `group` = '1'");

                        $sex = (int) !empty($_POST['sex']);
                        $this->pass2 = $this->pass1 = $_POST['password'];
                        mysql_query("INSERT INTO `users` (`reg_date`, `group`, `login`, `password`, `sex`) values('" . TIME . "', '6', '" . my_esc($this->login) . "', '" . crypt::hash($this->pass1, $this->settings['salt']) . "', '$sex')");
                        $return = true;
                    }
                }else {
                    $return = false;
                    $this->err_login = true;
                }
            } else {
                $return = false;
                $this->err_login = true;
            }

        return $return;
    }

    function form() {
        echo "<div style='background-color:" . ($this->err_login ? '#FFADB0' : '#ADFFB0') . "'>";
        echo __('Логин') . ":<br /><input type='text' name='login' value='" . for_value($this->login) . "' /><br />";
        echo "</div>";
        echo "<div style='background-color:" . ($this->err_pass1 ? '#FFADB0' : '#ADFFB0') . "'>";
        echo __('Пароль') . ":<br /><input type='password' name='password' value='" . for_value($this->pass1) . "' /><br />";
        echo "</div>";
        echo "<div style='background-color:" . ($this->err_pass2 ? '#FFADB0' : '#ADFFB0') . "'>";
        echo __('Подтверждение') . ":<br /><input type='password' name='password_retry' value='" . for_value($this->pass2) . "' /><br />";
        echo "</div>";
        echo __('Пол') . ":<br /><select name='sex'><option value='1'>" . __('Мужской') . "</option><option value='0'>" . __('Женский') . "</option></select>";

        if ($this->adm_count)
            echo '<br /><label><input type="checkbox" checked="checked" value="1" name="clear_adm" />' . __('Разжаловать всех админов') . '</label>';

        return true;
    }

}

?>
