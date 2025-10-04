<?php
/**
 * Custom auto loader
*/

final class Loader {

    public function __construct() {
        spl_autoload_register(array($this, 'loadClass'), true);
    }

    private function loadClass($className) {

        $path = str_replace(['_', '\\'], DIRECTORY_SEPARATOR, $className).".php";
        $parts = explode(DIRECTORY_SEPARATOR, $path);

        // Last part = class filename (keep case)
        $classFile = array_pop($parts);
        $firstDir = (count($parts) > 0) ? $parts[0] : $classFile;

        if( strtoupper($firstDir) == "TINYPHP" ) {
            $fileFullPath = ROOT_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts).DIRECTORY_SEPARATOR.$classFile;
        }
        else if( strtoupper($firstDir) == "MIDDLEWARE" || strtoupper($firstDir) == "API" ) {

            $parts = array_map('strtolower', $parts);
            $fileFullPath = APP_PATH.DIRECTORY_SEPARATOR."http".DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts).DIRECTORY_SEPARATOR.$classFile;            
        } 
        else {

            $parts = array_map('strtolower', $parts);
            $fileFullPath = APP_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts).DIRECTORY_SEPARATOR.$classFile;
        }

        if (file_exists($fileFullPath)) {
            require_once $fileFullPath;
            return;
        }


        // try to include original path
        if (file_exists($path)) {
            require_once $path;
            return;
        }        
    }

}

?>