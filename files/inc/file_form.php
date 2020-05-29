<?php

switch (@$_GET['act']) {
    case 'edit_prop': {
            $groups = groups::load_ini(); // загружаем массив групп
            $smarty = new design();
            $smarty->assign('method', 'post');
            $smarty->assign('action', '?order=' . $order . '&amp;' . passgen());
            $elements = array();
            $elements[] = array('type' => 'input_text', 'title' => __('Название файла') . ' *', 'br' => 1, 'info' => array('name' => 'name', 'value' => $file->runame));

            $elements[] = array('type' => 'textarea', 'title' => __('Описание'), 'br' => 1, 'info' => array('name' => 'description', 'value' => $file->description));
            $elements[] = array('type' => 'textarea', 'title' => __('Краткое описание'), 'br' => 1, 'info' => array('name' => 'description_small', 'value' => $file->description_small));


            if ($file->group_edit <= $user->group) {
                $options = array();
                foreach ($groups as $type => $value) {
                    $options[] = array($type, $value['name'], $type == $file->group_show);
                }
                $elements[] = array('type' => 'select', 'br' => 1, 'title' => __('Просмотр/скачивание файла'), 'info' => array('name' => 'group_show', 'options' => $options));

                $options = array();
                foreach ($groups as $type => $value) {
                    $options[] = array($type, $value['name'], $type == $file->group_edit);
                }
                $elements[] = array('type' => 'select', 'br' => 1, 'title' => __('Изменение параметров'), 'info' => array('name' => 'group_edit', 'options' => $options));
            }

            $elements[] = array('type' => 'text', 'value' => '* ' . __('На сервере имя файла будет на транслите'), 'br' => 1);

            $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'edit_prop', 'value' => __('Применить'))); // кнопка
            $smarty->assign('el', $elements);
            $smarty->display('input.form.tpl');
        }
        break;
    case 'edit_path' : {
            // перемещение папки
            $smarty = new design ();
            $smarty->assign('method', 'post');
            $smarty->assign('action', '?order=' . $order . '&amp;' . passgen());
            $elements = array();

            $options = array();

            // список папок в загруз-центре
            $root_dir = new files(FILES . '/.downloads');
            $dirs = $root_dir->getPathesRecurse($dir);
            foreach ($dirs as $dir2) {

                if ($dir2->group_show > $user->group || $dir2->group_write > $user->group) {
                    // если нет прав на чтение папки или на запись в папку, то пропускаем
                    continue;
                }

                if ($dir2->path_rel == $dir->path_rel) {
                    $options [] = array($dir2->path_rel, $dir2->getPathRu(), true);
                } else {
                    $options [] = array($dir2->getPath(), for_value($dir2->getPathRu() . ' <- ' . $file->runame));
                }
            }

            // список папок обменника
            $root_dir = new files(FILES . '/.obmen');
            $dirs = $root_dir->getPathesRecurse($dir);
            foreach ($dirs as $dir2) {

                if ($dir2->group_show > $user->group || $dir2->group_write > $user->group) {
                    // если нет прав на чтение папки или на запись в папку, то пропускаем
                    continue;
                }

                if ($dir2->path_rel == $dir->path_rel) {
                    $options [] = array($dir2->path_rel, $dir2->getPathRu(), true);
                } else {
                    $options [] = array($dir2->getPath(), for_value($dir2->getPathRu() . ' <- ' . $file->runame));
                }
            }


            $elements [] = array('type' => 'select', 'br' => 1, 'title' => __('Новый путь'), 'info' => array('name' => 'path_rel_new', 'options' => $options));

            $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'edit_path', 'value' => __('Применить'))); // кнопка
            $smarty->assign('el', $elements);
            $smarty->display('input.form.tpl');
        }
        break;
    case 'edit_unlink': {
            $smarty = new design();
            $smarty->assign('method', 'post');
            $smarty->assign('action', '?order=' . $order . '&amp;' . passgen());
            $elements = array();
            if ($file->id_user && $file->id_user != $user->id)
                $elements[] = array('type' => 'textarea', 'title' => __('Причина удаления'), 'br' => 1, 'info' => array('name' => 'reason'));
            $elements[] = array('type' => 'text', 'value' => __('Подтвердите удаление файла'), 'br' => 1);
            $elements[] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'edit_unlink', 'value' => __('Удалить'))); // кнопка
            $smarty->assign('el', $elements);
            $smarty->display('input.form.tpl');
        }
        break;
}


$doc->act(__('Скриншоты'), '?order=' . $order . '&amp;act=edit_screens');
$doc->act(__('Параметры файла'), '?order=' . $order . '&amp;act=edit_prop');
$doc->act(__('Переместить'), '?order=' . $order . '&amp;act=edit_path');
$doc->act(__('Удалить файл'), '?order=' . $order . '&amp;act=edit_unlink');
?>
