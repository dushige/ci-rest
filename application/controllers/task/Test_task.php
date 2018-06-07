<?php

use dkm\libraries\Task_base;

class Test_task extends Task_base {
    public function execute() {
        for ($i = 1; $i < 1000000; $i++) {
            daemon_log_info($i);
            usleep(10000);
        }
    }
}
