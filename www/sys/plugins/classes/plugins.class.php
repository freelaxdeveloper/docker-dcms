<?php

/**
 * Позволяет добавлять методы класса в виде отдельных файлов(плагинов)
 */
class plugins {

    function __call($name, $arg) {
        $class_name = get_class($this);

        if (file_exists(H . '/sys/plugins/classes/' . $class_name . '.' . basename($name) . '.php')) {
            include_once H . '/sys/plugins/classes/' . $class_name . '.' . basename($name) . '.php';
        }

        if (function_exists($class_name . '_' . $name)) {
            return call_user_func($class_name . '_' . $name, $this, $arg);
        }

        return false;
    }

}