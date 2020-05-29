<!DOCTYPE html>
<html>
    <head>
        <title><?= $title ?></title>
        <link rel="shortcut icon" href="/favicon.ico" />
        <script charset="utf-8" src="/sys/javascript/dcms.js" type="text/javascript"></script>        
        <script charset="utf-8" src="<?= $path ?>/user.js" type="text/javascript"></script>
        <link rel="stylesheet" href="/sys/themes/system.css" type="text/css" />
        <link rel="stylesheet" href="<?= $path ?>/style.css" type="text/css" />
        <meta http-equiv="content-Type" content="application/xhtml+xml; charset=utf-8" />
        <meta name="viewport" content="minimum-scale=1.0,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" />
        <? if ($description) { ?><meta name="description" content="<?= $description ?>" /><? } ?>
        <? if ($keywords) { ?><meta name="keywords" content="<?= $keywords ?>" /><? } ?>
        <style>
            .hide {
                display: none !important;
            }
        </style>
    </head>
    <body>
        <div>
            <? $this->display('inc.title.tpl') ?>
            <? $this->display('inc.user.tpl') ?>
            <div id="content">
                <? $this->display('inc.adt.top.tpl') ?> 
                <div id="messages">
                    <?= $this->section($err, '<div class="error">{text}</div>'); ?>
                    <?= $this->section($msg, '<div class="message">{text}</div>'); ?>                
                </div>                           
                <?= $content ?>
            </div>
            <? $this->display('inc.foot.tpl') ?>
            <? $this->display('inc.adt.bottom.tpl') ?>
            <div id="foot">
                <?= __("Время генерации страницы: %s сек", $document_generation_time) ?><br />
                <?= $copyright ?>
            </div>
        </div>
    </body>
</html>