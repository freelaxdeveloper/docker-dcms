<?
$iefix = ($url && $dcms->ie_ver && $dcms->ie_ver < 10) ? 'onclick="location.href = \'' . $url . '\'"' : '';
$post_time = $time ? '<span class="time">' . $time . '</span>' : '';
$post_counter = $counter ? '<span class="counter gradient_grey invert border">' . $counter . '</span>' : '';
$post_actions = '<span class="actions">' . $this->section($actions, '<a href="{url}"><img src="{icon}" alt="" /></a>') . '</span>';
?>
<?= ($url ? '<a href="' . $url . '" class="' : '<div class="') . 'post' . ($hightlight ? ' hightlight' : '') . '" id="'.$id.'">' ?>
<table <?= $iefix ?> cellspacing="0" callpadding="0" width="100%">
    <? if ($image) { ?>            
        <tr>
            <td class="image" rowspan="4">
                <img src="<?= $image ?>" alt="" />
            </td>
            <td class="title">
                <?= $title ?>
                <?= $post_counter ?>
            </td>

            <td class="right">
                <?= $post_time ?>
                <?= $post_actions ?>
            </td>
        </tr>
    <? } elseif ($icon) { ?>            
        <tr>
            <td class="icon">
                <img src="<?= $icon ?>" alt="" />
            </td>
            <td class="title">
                <?= $title ?>
                <?= $post_counter ?>
            </td>

            <td class="right">
                <?= $post_time ?>
                <?= $post_actions ?>
            </td>
        </tr>
    <? } else { ?>
        <tr>
            <td class="title">
                <?= $title ?>
                <?= $post_counter ?>
            </td>

            <td class="right">
                <?= $post_time ?>
                <?= $post_actions ?>
            </td>
        </tr>
    <? } ?>

    <? if ($content) { ?>
        <tr>
            <td class="content" colspan="10">
                <?= $content ?>
            </td>
        </tr>
    <? } ?>

    <? if ($bottom) { ?>
        <tr>
            <td class="bottom" colspan="10">
                <?= $bottom ?>
            </td>
        </tr>
    <? } ?>
</table>
<?=
$url ? '</a>' : '</div>'?>