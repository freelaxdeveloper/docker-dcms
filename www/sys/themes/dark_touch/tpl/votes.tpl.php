<div class="votes">
    <span class="votes_name"><?= $name ?></span>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <? for ($i = 0; $i < count($votes); $i++) { ?>
            <tr>
                <td class="vote_title" colspan="2">
                    <?= $votes[$i]['name'] ?>
                    <?= $votes[$i]['count'] ? ' (' . $votes[$i]['count'] . ')' : '' ?>
                </td>
            </tr>
            <tr>
                <td class="vote_bar">
                    <div class="vote_bar" style=" width:<?= $votes[$i]['pc'] ?>%; box-sizing: border-box;">
                        <?= $votes[$i]['pc'] ?>%
                    </div>
                </td>
                <? if ($is_add) { ?>
                    <td class="vote_add">
                        <div>
                            <a href="<?= $votes[$i]['url'] ?>">+</a>
                        </div>
                    </td>
                <? } ?>
            </tr>        
        <? } ?>
    </table>
</div>