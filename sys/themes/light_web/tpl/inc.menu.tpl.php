<?
foreach ($menu AS $item){
    if (!empty($item['razdel']))
        echo '<span>'.$item['name'].'</span>';
    else
        echo '<a href="'.$item['url'].'">'.$item['name'].'</a>';
}
?>
