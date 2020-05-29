<div class="pages">
    <?
    echo $page == 1 ? '<span class="gradient_blue invert border radius padding">1</span>' : '<a class="gradient_grey border radius padding" href="' . $link . 'page=1">1</a>';
    for ($i = max(2, $page - 8); $i < min($k_page, $page + 10); $i++) {
        if ($i == $page)
            echo '<span class="gradient_blue invert border radius padding">' . $i . '</span>';
        else
            echo '<a class="gradient_grey border radius padding" href="' . $link . 'page=' . $i . '">' . $i . '</a>';
    }
    echo $page == $k_page ? '<span class="gradient_blue invert border radius padding">' . $k_page . '</span>' : '<a class="gradient_grey border radius padding" href="' . $link . 'page=' . $k_page . '">' . $k_page . '</a>'
    ?>
</div>
