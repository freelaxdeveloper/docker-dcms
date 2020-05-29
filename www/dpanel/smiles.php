<?php

include_once '../sys/inc/start.php';
dpanel::check_access();
$smiles = smiles::get_ini();
$doc = new document(5);
$doc->title = __('Смайлы');

$smiles_a = array();
// загружаем список смайлов
$smiles_gl = (array) glob(H . '/sys/images/smiles/*.gif');

foreach ($smiles_gl as $path) {
    preg_match('#/([^/]+)\.gif$#', $path, $m);
    $smiles_a[$m[1]] = $path;
}

if (!empty($_GET['smile']) && isset($smiles_a[$_GET['smile']])) {
    $sm = $_GET['smile'];

    if (isset($_GET['act']) && $_GET['act'] == 'delete' && !empty($_GET['phrase'])) {
        $phrase = (string) $_GET['phrase'];
        if (!empty($smiles[$phrase])) {
            if ($smiles[$phrase] != $sm)
                $doc->err(__('Фраза относится к другому смайлу'));
            else {
                unset($smiles[$phrase]);

                if (ini::save(H . '/sys/ini/smiles.ini', $smiles)) {
                    $doc->msg(__('Фраза успешно удалена'));
                }else
                    $doc->err(__('Нет прав на запись в файл %s', 'smiles.ini'));
            }
        }else {
            $doc->err(__('Фраза уже удалена'));
        }
    }

    if (!empty($_POST['phrase'])) {
        $phrase = text::for_value($_POST['phrase']);
        if ($phrase) {
            if ($phrase == 'null' || $phrase == 'yes' || $phrase == 'no' || $phrase == 'true' || $phrase == 'false')
                $doc->err(__('Запрещено использовать данную фразу'));
            elseif (!empty($smiles[$phrase]))
                $doc->err(__('Данная фраза используется для смайла "%s"', $smiles[$phrase]));
            else {
                $smiles[$phrase] = $sm;
                if (ini::save(H . '/sys/ini/smiles.ini', $smiles)) {
                    $doc->msg(__('Фраза успешно добавлена'));
                }else
                    $doc->err(__('Нет прав на запись в файл %s', 'smiles.ini'));
            }
        }
    }

    $doc->title = __('Смайл "%s"', $sm);

    $phrases = array_keys($smiles, $sm);

    $listing = new listing();
    foreach ($phrases as $text) {
        $post = $listing->post();
        $post->title = $text;
        $post->image = '/sys/images/smiles/' . $sm . '.gif';
        $post->action('delete', '?smile=' . urlencode($sm) . '&amp;phrase=' . urlencode($text) . '&amp;act=delete');
    }

    $listing->display(__('Фразы отсутствуют'));

    $form = new form('?' . passgen() . '&amp;smile=' . urlencode($sm) . '&amp;act=add');
    $form->text('phrase', __('Фраза'));
    $form->button(__('Добавить'));
    $form->display();

    $doc->ret(__('Смайлы'), '?');
    $doc->ret(__('Админка'), './');
    exit;
}


$listing = new listing();
foreach ($smiles_a as $name => $path) {
    $post = $listing->post();
    $post->image = '/sys/images/smiles/' . $name . '.gif';
    $post->url = '?smile=' . urlencode($name);
    $post->content = __('Варианты') . ': ' . implode(', ', array_keys($smiles, $name));
}
$listing->display(__('Смайлы отсутствуют'));

$doc->ret(__('Админка'), './');
?>
