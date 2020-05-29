<div class="pages_wrapper"><div class="pages">
        <?
        foreach ($select AS $option) {
            if (empty($option[2]))
                echo '<a href="' . $option[0] . '">' . $option[1] . '</a>';
            else
                echo '<span>' . $option[1] . '</span>';
        }
        ?>
    </div>
</div>