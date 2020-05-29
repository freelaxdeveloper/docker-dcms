<div class="pages">
    <?
    echo $page == 1 ? '<span class="gradient_blue invert border radius">1</span>' : '<a class="gradient_grey border radius" href="' . $link . 'page=1">1</a>';
    for ($i = max(2, $page - 4); $i < min($k_page, $page + 3); $i++) {
        if ($i == $page)
            echo '<span class="gradient_blue invert border radius">' . $i . '</span>';
        else
            echo '<a class="gradient_grey border radius" href="' . $link . 'page=' . $i . '">' . $i . '</a>';
    }
    echo $page == $k_page ? '<span class="gradient_blue invert border radius">' . $k_page . '</span>' : '<a class="gradient_grey border radius" href="' . $link . 'page=' . $k_page . '">' . $k_page . '</a>'
    ?>
</div>
