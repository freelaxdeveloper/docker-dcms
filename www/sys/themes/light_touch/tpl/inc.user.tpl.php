<div id="navigation_user" class="gradient_grey invert">
    <?
    if ($user->id) {
        ?>
        <a id='user_friend' class='gradient_grey border radius padding <?= $user->friend_new_count ? '' : 'hide' ?>' href='/my.friends.php'><?= __("Друзья") ?> +<span><?= $user->friend_new_count ?></span></a>
        <a id='user_mail' class='gradient_grey border radius padding <?= $user->mail_new_count ? '' : 'hide' ?>' href='/my.mail.php?only_unreaded'><?= __("Почта") ?> +<span><?= $user->mail_new_count ?></span></a>
        <a class="gradient_grey invert border radius padding" id='menu_user' style='font-weight: bold;' href="/menu.user.php"><?= $user->login ?></a> 
        <script type="text/javascript">    
            var USER = {
                id: <?= $user->id ?>,
                mail_new_count: <?= $user->mail_new_count ?>,
                friend_new_count: <?= $user->friend_new_count ?>
            };
            DCMS.UserUpdate.delay_update();  // запускаем периодический запрос данных пользователя
            // новые данные можно получать, подписавшись на событие user_update: DCMS.Event.on('user_update', user_update);
        </script>
        <?
    } else {
        ?>
        <a class="gradient_grey border radius padding" href="/login.php?return=<?= URL ?>"><?= __("Авторизация") ?></a>
        <a class="gradient_grey border radius padding" href="/reg.php?return=<?= URL ?>"><?= __("Регистрация") ?></a>
        <?
    }
    ?>
</div> 