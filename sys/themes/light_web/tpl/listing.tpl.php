<div class="listing" id="<?= $id ?>">
    <?= $content ?>
</div>
<? if ($ajax_url) { ?>
    <script>
        listing_auto_update(document.getElementById('<?= $id ?>'), '<?= $ajax_url ?>');
        
    <? if ($form) { ?>
        DCMS.Event.on('form_submited', function(form){
            if ('<?= $form->id ?>' === form.id){
                listing_auto_update(document.getElementById('<?= $id ?>'), '<?= $ajax_url ?>', true);
            }
        });
    <? } ?>
        
    </script>
<?
}?>