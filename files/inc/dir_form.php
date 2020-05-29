<?php

defined('DCMS') or die();
// файл отвечает за отображение возможных действий
if ($access_write) {
    // выгрузка и импорт файлов
    switch (@$_GET ['act']) {
        case 'file_upload' : {
                $smarty = new design ();
                $smarty->assign('method', 'post');


                $smarty->assign('files', 1);
                $smarty->assign('action', '?' . passgen());
                $elements = array();
                $elements [] = array('type' => 'file', 'title' => __('Файл'), 'br' => 1, 'info' => array('name' => 'file'));
                $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('value' => __('Выгрузить'))); // кнопка
                $smarty->assign('el', $elements);
                $smarty->display('input.form.tpl');
            }
            break;
    }

    $doc->act(__('Выгрузить файл'), '?act=file_upload');
}

if ($access_edit) {
    // изменеение параметров
    switch (@$_GET ['act']) {
        case 'file_import' : {
                $smarty = new design ();
                $smarty->assign('method', 'post');
                $smarty->assign('action', '?' . passgen());
                $elements = array();
                $elements [] = array('type' => 'input_text', 'title' => __('URL'), 'br' => 1, 'info' => array('name' => 'url', 'value' => 'http://'));
                $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'file_import', 'value' => __('Импортировать'))); // кнопка
                $smarty->assign('el', $elements);
                $smarty->display('input.form.tpl');
            }
            break;
        case 'write_dir' : {
                $smarty = new design ();
                $smarty->assign('method', 'post');
                $smarty->assign('action', '?' . passgen());
                $elements = array();
                $elements [] = array('type' => 'input_text', 'title' => __('Название папки') . ' *', 'br' => 1, 'info' => array('name' => 'name'));
                $elements [] = array('type' => 'text', 'value' => '* ' . __('На сервере создастся папка на транслите'), 'br' => 1);
                $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'write_dir', 'value' => __('Создать'))); // кнопка
                $smarty->assign('el', $elements);
                $smarty->display('input.form.tpl');
            }
            break;

        case 'edit_unlink' : {
                $smarty = new design ();
                $smarty->assign('method', 'post');
                $smarty->assign('action', '?' . passgen());
                $elements = array();
                $elements [] = array('type' => 'captcha', 'session' => captcha::gen(), 'br' => 1);
                $elements [] = array('type' => 'text', 'value' => '* ' . __('Все данные, находящиеся в этой папке будут безвозвратно удалены'), 'br' => 1);
                $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'edit_unlink', 'value' => __('Удалить'))); // кнопка
                $smarty->assign('el', $elements);
                if ($rel_path)
                    $smarty->display('input.form.tpl');
            }
            break;
        case 'edit_path' : {
                // перемещение папки
                $smarty = new design ();
                $smarty->assign('method', 'post');
                $smarty->assign('action', '?' . passgen());
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
                        $options [] = array($dir2->getPath(), for_value($dir2->getPathRu() . ' <- ' . $dir->runame));
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
                        $options [] = array($dir2->getPath(), for_value($dir2->getPathRu() . ' <- ' . $dir->runame));
                    }
                }


                $elements [] = array('type' => 'select', 'br' => 1, 'title' => __('Новый путь'), 'info' => array('name' => 'path_rel_new', 'options' => $options));

                $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'edit_path', 'value' => __('Применить'))); // кнопка
                $smarty->assign('el', $elements);
                $smarty->display('input.form.tpl');
            }
            break;
        case 'edit_prop' : {
                $groups = groups::load_ini(); // загружаем массив групп
                $smarty = new design ();
                $smarty->assign('method', 'post');
                $smarty->assign('action', '?' . passgen());
                $elements = array();
                $elements [] = array('type' => 'input_text', 'title' => __('Название папки') . ' *', 'br' => 1, 'info' => array('name' => 'name', 'value' => $dir->runame));

                $elements [] = array('type' => 'textarea', 'title' => __('Описание'), 'br' => 1, 'info' => array('name' => 'description', 'value' => $dir->description));

                if ($rel_path) {
                    $elements [] = array('type' => 'input_text', 'title' => __('Позиция') . ' **', 'br' => 1, 'info' => array('name' => 'position', 'value' => $dir->position));
                }
                
                
                $order_keys = $dir->getKeys();
                $options = array();
                foreach ($order_keys as $key => $key_name) {
                    $options [] = array($key, $key_name, $key == $dir->sort_default);
                }
                $elements [] = array('type' => 'select', 'br' => 1, 'title' => __('Сортировка по-умолчанию'), 'info' => array('name' => 'sort_default', 'options' => $options));

                

                $options = array();
                foreach ($groups as $type => $value) {
                    $options [] = array($type, $value ['name'], $type == $dir->group_show);
                }
                $elements [] = array('type' => 'select', 'br' => 1, 'title' => __('Просмотр папки') . ' ***', 'info' => array('name' => 'group_show', 'options' => $options));

                $options = array();
                foreach ($groups as $type => $value) {
                    $options [] = array($type, $value ['name'], $type == $dir->group_write);
                }
                $elements [] = array('type' => 'select', 'br' => 1, 'title' => __('Выгрузка файлов'), 'info' => array('name' => 'group_write', 'options' => $options));

                $options = array();
                foreach ($groups as $type => $value) {
                    $options [] = array($type, $value ['name'], $type == $dir->group_edit);
                }
                $elements [] = array('type' => 'select', 'br' => 1, 'title' => __('Изменение параметров и создание папок'), 'info' => array('name' => 'group_edit', 'options' => $options));

                if ($rel_path && $dir->name {0} !== '.')
                    $elements [] = array('type' => 'text', 'value' => '* ' . __('На сервере папка будет на транслите'), 'br' => 1);
                else
                    $elements [] = array('type' => 'text', 'value' => '* ' . __('Изменится только отображаемое название'), 'br' => 1);

                if ($rel_path)
                    $elements [] = array('type' => 'text', 'value' => '** ' . __('Если у папок одинаковая позиция, то они сортируются по имени'), 'br' => 1);

                $elements [] = array('type' => 'text', 'value' => '*** ' . __('При большом кол-ве вложенных объектов изменение данного параметра может затянуться (и подвесить сервер)'), 'br' => 1);

                $elements [] = array('type' => 'submit', 'br' => 0, 'info' => array('name' => 'edit_prop', 'value' => __('Применить'))); // кнопка
                $smarty->assign('el', $elements);
                $smarty->display('input.form.tpl');
            }
            break;
    }

    $doc->act(__('Импортировать файл'), '?act=file_import');
    $doc->act(__('Создать папку'), '?order=' . $order . '&amp;act=write_dir');
    $doc->act(__('Параметры'), '?order=' . $order . '&amp;act=edit_prop');

    if ($rel_path && $dir->name {0} !== '.') {
        $doc->act(__('Перемещение'), '?order=' . $order . '&amp;act=edit_path');
        $doc->act(__('Удаление папки'), '?order=' . $order . '&amp;act=edit_unlink');
    }
}
?>
