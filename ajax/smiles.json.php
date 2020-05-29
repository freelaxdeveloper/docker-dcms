<?php

include '../sys/inc/start.php';

$data = array();

$smiles = smiles::get_ini();
$smiles_a = array();
$smiles_gl = (array) glob(H . '/sys/images/smiles/*.gif');

foreach ($smiles_gl as $path) {
    if (preg_match('#/([^/]+)\.gif$#', $path, $m)) {
        $smiles_a[$m[1]] = $path;
    }
}


foreach ($smiles_a as $name => $path) {
    if (!$code = array_search($name, $smiles)) {
        continue;
    }
    $data[] = array('image' => '/sys/images/smiles/' . $name . '.gif', 'code' => '*' . $code . '*', 'title'=> $name);
}


header('Content-type: application/json');
echo json_encode($data);
?>
