<? if ($actions) { ?>
    <div id="actions">
        <?= $this->section($actions, '<div><a href="{1}">{0}</a></div>'); ?>
    </div>
<? } ?>

<? if ($returns OR !IS_MAIN) { ?>
    <div id="returns">        
        <?= $this->section($returns, '<div><a href="{1}">{0}</a></div>'); ?>
        <? if (!IS_MAIN) { ?>
            <div><a href='/'><?= __("На главную") ?></a></div>
        <? } ?>  
    </div>
<? } ?>


