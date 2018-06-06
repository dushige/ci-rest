<?php

abstract class DKM_Controller extends CI_Controller {
    public function __construct() {
        parent::__construct();
    }
}

function class_autoloader($classname) {
    // Prefix check
    if (strpos(strtolower($classname), "dkm\\")===0) {
        // Locate class relative path
        $classname = str_replace("dkm\\", "", $classname);
        $filepath = APPPATH.  str_replace('\\', DIRECTORY_SEPARATOR, ltrim($classname, '\\')) . '.php';
        
        if (file_exists($filepath)) {
            require $filepath;
        }
    }
}

spl_autoload_register('class_autoloader');