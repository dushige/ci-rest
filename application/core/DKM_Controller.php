<?php

/**
 * Class DKM_Controller
 *
 * @property CI_Config                    $config
 * @property CI_Router                    $router
 * @property CI_Input                     $input
 * @property CI_Calendar                  $calendar
 * @property CI_Encrypt                   $encrypt
 * @property CI_Encryption                $encryption
 * @property CI_Ftp                       $ftp
 * @property CI_Hooks                     $hooks
 * @property CI_Image_lib                 $image_lib
 * @property CI_Log                       $log
 * @property CI_Output                    $output
 * @property CI_Pagination                $pagination
 * @property CI_Parser                    $parser
 * @property CI_Session                   $session
 * @property CI_Table                     $table
 * @property CI_Trackback                 $trackback
 * @property CI_Unit_test                 $unit
 * @property CI_Upload                    $upload
 * @property CI_URI                       $uri
 * @property CI_User_agent                $agent
 * @property CI_Xmlrpc                    $xmlrpc
 * @property CI_Zip                       $zip
 * @property CI_Loader                    $load
 * @property CI_Lang                      $lang
 * @property \dkm\models\User_model       $user
 * @property \dkm\models\Img_model        $img
 */
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
