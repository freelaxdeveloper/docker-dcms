<?php

defined('DCMS') or die();
// файл отвечает за исполнение действий
if ($access_write) {
    // выгрузка
    if (!empty($_FILES ['file'])) {
        if ($_FILES ['file'] ['error'])
            $doc->err(__('Ошибка при загрузке'));
        elseif (!$_FILES ['file'] ['size'])
            $doc->err(__('Содержимое файла пусто'));
        elseif ($dir->is_file(text::for_filename($_FILES ['file'] ['name']))) {
            $doc->err(__('Файл с таким названием уже существует'));
        } else {
            if ($files_ok = $dir->filesAdd(array($_FILES ['file'] ['tmp_name'] => $_FILES ['file'] ['name']))) {
                $files_ok [$_FILES ['file'] ['tmp_name']]->id_user = $user->id;
                $files_ok [$_FILES ['file'] ['tmp_name']]->group_edit = max($user->group, $dir->group_write, 2);

                $doc->msg(__('Файл "%s" успешно добавлен', $_FILES ['file'] ['name']));
                // записываем свое действие в общий лог
                if ($dir->group_write > 1)
                    $dcms->log('Файлы', 'Выгрузка файла [url="/files' . $files_ok [$_FILES ['file'] ['tmp_name']]->getPath() . '"]' . $_FILES ['file'] ['name'] . '[/url]');
                unset($files_ok);
            } else {
                $doc->err(__('Не удалось сохранить выгруженный файл'));
            }
        }
    }
}

if ($access_edit) {
    // импорт файлов
    if (!empty($_POST ['file_import']) && !empty($_POST ['url'])) {
        if ($file = $dir->fileImport($_POST ['url'])) {
            $doc->msg(__('Файл "%s" успешно импортирован', $file->runame));
            $file->id_user = $user->id;
            // записываем свое действие в общий лог
            if ($dir->group_write > 1) {
                $dcms->log('Файлы', 'Импорт файла [url="/files' . $file->getPath() . '"]' . $file->runame . '[/url]');
            }

            header('Refresh: 1; url=?act=file_import&' . passgen());

            $doc->ret(__('К файлу'), '/files' . $file->getPath() . '.htm');
            $doc->ret(__('Вернуться'), '?act=file_import&amp;' . passgen());
            exit();
        } elseif ($error = $dir->error) {
            $doc->err($error);
        } else {
            $doc->err(__('Не удалось импортировать файл'));
        }
    }
    // изменеение параметров
    if (isset($_POST ['edit_path']) && !empty($_POST ['path_rel_new'])) {
        // перемещение папки
        $root_dir = new files(FILES);
        $dirs = $root_dir->getPathesRecurse($dir);

        $path_rel_new = $_POST ['path_rel_new'];

        foreach ($dirs as $dir2) {
            // если нет прав на чтение папки или на запись в папку, то пропускаем
            if ($dir2->group_show > $user->group || $dir2->group_write > $user->group) {
                continue;
            }
            // мы не можем папку переместить саму в себя
            if ($dir2->path_rel == $dir->path_rel)
                continue;
            if ($dir2->getPath() == $path_rel_new) {
                $path_abs_new = $dir2->path_abs . '/' . $dir->name;
            }
        }

        if (!empty($path_abs_new)) {
            if ($dir->move($path_abs_new)) {
                // записываем свое действие в общий лог
                $dcms->log('Файлы', 'Перемещение папки [url="/files' . $dir->getPath() . '"]' . $dir->runame . '[/url]');

                $doc->msg(__('Папка успешно перемещена'));
                $doc->ret(__('Вернуться'), '/files' . $dir->getPath() . '?' . passgen());
                header('Refresh: 1; url=/files' . $dir->getPath() . '?' . passgen());
                exit();
            } else {
                $doc->err(__('При перемещении папки возникла ошибка'));
            }
        } else
            $doc->err(__('Ошибка при выборе нового каталога'));
    }

    if (isset($_POST ['write_dir']) && isset($_POST ['name'])) {
        $runame = text::for_name($_POST ['name']);

        if (!$runame)
            $doc->err(__('Неверно задано имя папки'));
        elseif (!$new_dir = $dir->mkdir($runame))
            $doc->err(__('Не удалось создать папку на сервере'));
        else {
            $new_dir->id_user = $user->id;

            $dcms->log('Файлы', 'Создание папки [url="/files' . $new_dir->getPath() . '"]' . $new_dir->runame . '[/url]');

            $doc->msg(__('Папка "%s" успешно создана', $runame));
            $doc->ret(__('Вернуться'), '?act=write_dir');
            header('Refresh: 1; url=?act=write_dir');
            exit();
        }
    }

    if (isset($_POST ['edit_unlink']) && $rel_path && $dir->name {0} !== '.') {
        if (empty($_POST ['captcha']) || empty($_POST ['captcha_session']) || !captcha::check($_POST ['captcha'], $_POST ['captcha_session'])) {
            $design->err(__('Проверочное число введено неверно'));
        } else {
            if ($dir->delete()) {
                $dcms->log('Файлы', 'Удаление папки ' . $dir->runame . ' (' . $dir->getPath() . ')');

                $doc->msg(__('Папка успешно удалена'));
                $doc->ret(__('Вернуться'), '../?' . passgen());
                header('Refresh: 1; url=../?' . passgen());
                exit();
            } else {
                $doc->err(__('Ошибка при удалении папки'));
            }
        }
    }

    if (isset($_POST ['edit_prop'])) {
        $groups = groups::load_ini(); // загружаем массив групп


        if ($rel_path && isset($_POST ['position'])) {
            $dir->position = (int) $_POST ['position'];
        }
        if (isset($_POST ['description'])) {
            $dir->description = text::input_text($_POST ['description']);
        }


        $order_keys = $dir->getKeys();
        if (!empty($_POST ['sort_default']) && isset($order_keys [$_POST ['sort_default']])) {
            $dir->sort_default = $_POST ['sort_default'];
        }



        if (!empty($_POST ['name'])) {
            $runame = text::for_name($_POST ['name']);
            $name = text::for_filename($runame);

            if ($runame != $dir->runame) {
                $oldname = $dir->runame;
                if (!$runame || !$name)
                    $doc->err(__('Неверно задано имя папки'));
                elseif (!$dir->rename($runame, $name))
                    $doc->err(__('Не удалось переименовать папку'));
                else {
                    $dcms->log('Файлы', 'Переименование папки из ' . $oldname . ' в [url="/files' . $dir->getPath() . '"]' . $dir->runame . '[/url]');
                    $doc->msg(__('Новое название папки "%s"', $runame));
                }
            }
        }

        if (isset($_POST ['group_show'])) { // просмотр
            $group_show = (int) $_POST ['group_show'];
            if (isset($groups [$group_show]) && $group_show != $dir->group_show) {
                $dir->setGroupShowRecurse($group_show); // данный параметр необходимо применять рекурсивно


                $dcms->log('Файлы', 'Изменение привилегий просмотра папки [url="/files' . $dir->getPath() . '"]' . $dir->runame . '[/url] на группу ' . groups::name($group_show));

                $doc->msg(__('Просмотр папки разрешен группе "%s" и выше', groups::name($group_show)));
            }
        }

        if (isset($_POST ['group_write'])) { // запись
            $group_write = (int) $_POST ['group_write'];
            if (isset($groups [$group_write]) && $group_write != $dir->group_write) {
                if ($dir->group_show > $group_write)
                    $doc->err(__('Для того, чтобы выгружать файлы группе "%s" сначала необходимо дать права на просмотр этой папки', groups::name($group_write)));
                else {
                    $dir->group_write = $group_write;

                    $dcms->log('Файлы', 'Изменение привилегий выгрузки файлов для папки [url="/files' . $dir->getPath() . '"]' . $dir->runame . '[/url] на группу ' . groups::name($group_write));

                    $doc->msg(__('Выгружать файлы разрешено группе "%s" и выше', groups::name($group_write)));
                }
            }
        }

        if (isset($_POST ['group_edit'])) { // редактирование
            $group_edit = (int) $_POST ['group_edit'];
            if (isset($groups [$group_edit]) && $group_edit != $dir->group_edit) {
                if ($dir->group_write > $group_edit)
                    $doc->err(__('Для изменения параметров папки и создания папок группе "%s" сначала необходимо дать права на запись в папку', groups::name($group_edit)));
                else {
                    $dir->group_edit = $group_edit;

                    $dcms->log('Файлы', 'Изменение привилегий создания папок и изменения параметров для папки [url="/files' . $dir->getPath() . '"]' . $dir->runame . '[/url] на группу ' . groups::name($group_edit));

                    $doc->msg(__('Изменять параметры папки и создание папок теперь разрешено группе "%s" и выше', groups::name($group_edit)));
                }
            }
        }


        $doc->msg(__('Параметры успешно приняты'));
        $doc->ret(__('Вернуться'), '/files' . $dir->getPath() . '?' . passgen());
        header('Refresh: 2; url=/files' . $dir->getPath() . '?' . passgen());
        exit;
    }
}
?>
