<div class="votes">
    <span class="vote_name"><?= $name ?></span>
    <table style="width: 100%">
        <?
        foreach ($votes AS $vote) {
            ?>
            <tr>
                <td colspan="2">
                    <?= $vote['name'] ?>
                    <?= $vote['count'] ? ' (' . $vote['count'] . ')' : '' ?>
                </td>
            </tr>
            <tr style="height: 16px;">
                <td class="gradient_grey invert border radius" style="width: 100%">
                    <div class="gradient_blue border radius vote_pc" style="<?= 'width: ' . max($vote['pc'], 6) . '%' ?>">
                        <?= $vote['pc'] ?>%
                    </div>
                </td>
                <? if ($is_add) { ?>
                    <td>
                        <a class="gradient_blue border radius vote_plus" href="<?= $vote['url'] ?>">+</a>                        
                    </td>
                <? } ?>
            </tr>        
        <? } ?>
    </table>
</div>