<? if ($actions) { ?>
    <div id="actions">
        <?= $this->section($actions, '<a class="gradient_blue border" href="{1}">{0}</a>'); ?>
    </div>
<? } ?>

<? if ($returns OR !IS_MAIN) { ?>
    <div id="returns">        
        <?= $this->section($returns, '<a class="gradient_grey border" href="{1}">{0}</a>'); ?>
        <? if (!IS_MAIN) { ?>
            <a class="gradient_grey border" href='/'><?= __("На главную") ?></a>
        <? } ?>  
    </div>
<? } ?>