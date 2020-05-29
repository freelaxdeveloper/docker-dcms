<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $lang->xml_lang ?>">
    <head>
        <title><?= $title ?></title>
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="stylesheet" href="/sys/themes/system.css" type="text/css" />
        <link rel="stylesheet" href="/sys/themes/theme_light.css" type="text/css" />
        <link rel="stylesheet" type="text/css" href="<?= $path ?>/style.css" />
        <script charset="utf-8" src="/sys/javascript/dcms.js" type="text/javascript"></script>
        <script charset="utf-8" src="<?= $path ?>/user.js" type="text/javascript"></script>
        <meta http-equiv="Сontent-Type" content="application/xhtml+xml; charset=utf-8" />
        <? if ($description) { ?><meta name="description" content="<?= $description ?>" /><? } ?>
        <? if ($keywords) { ?><meta name="keywords" content="<?= $keywords ?>" /><? } ?>
        <style>
            .hide {
                display: none !important;
            }
        </style>
        <script>
            LANG = {
                bbcode_b: '<?= __('Текст жирным шрифтом') ?>',
                bbcode_i: '<?= __('Текст курсивом') ?>',
                bbcode_u: '<?= __('Подчеркнутый текст') ?>',
                bbcode_img: '<?= __('Вставка изображения') ?>',
                bbcode_php: '<?= __('Выделение PHP-кода') ?>',
                bbcode_big: '<?= __('Увеличенный размер шрифта') ?>',
                bbcode_small: '<?= __('Уменьшенный размер шрифта') ?>',
                bbcode_gradient:'<?= __('Цветовой градиент') ?>',
                bbcode_hide: '<?= __('Скрытый текст') ?>',
                bbcode_spoiler: '<?= __('Свернутый текст') ?>',
                form_submit_error: '<?= __('Ошибка связи...') ?>'
            };
        </script>
    </head>
    <?
    $class_fix = $dcms->ie_ver ? 'ie ie' . $dcms->ie_ver : '';
    ?>
    <body class="theme_light_web theme_light <?= $class_fix ?>"> 
        <div id="main">
            <div id="top_part">
                <div id="header" class="gradient_blue">
                    <div class="body_width_limit">
                        <h1 id="title"><?= $title ?></h1>
                        <div id="navigation">
                            <? if (!IS_MAIN) { ?>
                                <a class="gradient_blue invert border radius padding" href='/'><?= __("На главную") ?></a>
                            <? } ?>
                            <?= $this->section($returns, '<a class="gradient_blue invert border radius padding" href="{1}">{0}</a>', true); ?>
                        </div>
                    </div>
                    <div id="navigation_user" class="gradient_grey invert">
                        <div class="body_width_limit">
                            <?
                            echo $this->section($actions, '<a class="gradient_grey border radius padding" href="{1}">{0}</a>');
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
                    </div> 
                </div>
                <div class="body_width_limit">
                    <div id="menu">
                        <div class="listing">
                            <div id="adt_top" class="post">
                                <a href="http://nighthosting.ru" target="_blank"><img src="<?=$path?>/img/nighthosting.png" alt="NightHosting"></a>
                                <?= $this->section($adt->top, '{0}') ?>
                            </div>
                        </div>
                        <?
                        $menu = new menu('main');
                        $menu->display();
                        ?>
                        <? if ($adt->bottom) { ?>
                            <div id="adt_bottom">
                                <?= $this->section($adt->bottom, '{0}') ?>
                            </div>
                        <? } ?>
                    </div>
                    <div id="content">
                        <div id="messages">
                            <?= $this->section($err, '<div class="gradient_red border radius">{text}</div>'); ?>
                            <?= $this->section($msg, '<div class="gradient_green border radius">{text}</div>'); ?>                
                        </div>
                        <?= $content ?>
                    </div>

                </div>
                <div id="empty"></div>
            </div>
            <div id="footer" class="gradient_grey">
                <div class="body_width_limit">
                    <span id="copyright">
                        <?= $copyright ?>
                    </span>
                    <span id="language">
                        <?= __("Язык") ?>:<a href='/language.php?return=<?= URL ?>' style='background-image: url(<?= $lang->icon ?>); background-repeat: no-repeat; background-position: 5px 2px; padding-left: 23px;'><?= $lang->name ?></a>
                    </span>
                    <span id="generation">
                        <?= __("Время генерации страницы: %s сек", $document_generation_time) ?>
                    </span>                    
                </div>
            </div>
        </div>
    </body>
</html>