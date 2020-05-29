<?php

/**
 * сборщик JavaScript
 */
class js_assembly {

    protected $_files = array(),
            $_dir_abs,
            $_files_content = array();

    function __construct($dir_abs = false) {
        if ($dir_abs)
            $this->setDirAbs($dir_abs);
    }

    function buildTo($file_path) {
        $javascriptCode = '';
        $this->_files_content = array();
        foreach ($this->_files AS $file) {
            $relpath = str_replace(filesystem::setPathSeparator($this->_dir_abs), '', $file);
            $content = file_get_contents($file);

            preg_match_all('/include "(.+?)"/ime', $content, $m, PREG_SET_ORDER);

            $includes = array();
            foreach ($m AS $match) {
                $includes[] = $match[1];
            }

            $this->_files_content[$relpath] = array(
                'content' => $content,
                'includes' => $includes
            );
        }

        $javascriptCode .= "/* DCMS jsBuild system */\r\n\r\n\r\n\r\n";
        foreach ($this->_files_content AS $path => $content) {
            $javascriptCode .= $this->getContent($path);
        }

        return file_put_contents($file_path, $javascriptCode) !== false;
    }

    function setDirAbs($dir_abs) {
        $this->_dir_abs = $dir_abs;
        $this->_files = filesystem::getFilesByPattern($this->_dir_abs, '/\.js$/', true);
        sort($this->_files);
    }

    function getContent($path) {
        $return = '';
        static $outputed = array();

        if (in_array($path, $outputed))
            return;
        $outputed[] = $path;

        if (!array_key_exists($path, $this->_files_content))
            return;

        // $return .= '// START ' . $path . "\r\n";



        $file = $this->_files_content[$path];

        foreach ($file['includes'] AS $include) {
            // echo '// include ' . $include . "\r\n";
            $search = array(
                dirname($path) . '/' . $include,
                $include
            );

            foreach ($search AS $sfile) {
                // echo '// search ' . $include . "\r\n";
                $return .= $this->getContent($sfile);
            }
        }


        //$return .= '// END ' . $path . "\r\n";

        $return .= '// FILE ' . $path . "\r\n";
        $return .= $file['content'];
        $return .= "\r\n\r\n";
        return $return;
    }

}

?>
