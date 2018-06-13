<?php

namespace dkm\libraries\base;

class Task_base extends \DKM_Controller {

    public $task_config = NULL;
    public $argv = NULL;

    public function __construct() {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            error_log('Permission denied!');
            exit(102);
        }
        parent::__construct();
        
        if (function_exists('load_task_config')) {
            $this->task_config = load_task_config();
            $this->argv = $this->task_config['argv'];
        }
    }

    protected function get_argv($n, $default_value) {
        $argv = $this->get_all_argv();
        if (isset($argv[$n])) {
            return $argv[$n];
        } else {
            return $default_value;
        }
    }

    protected function get_all_argv() {
        return $this->task_config['argv'];
    }
}
