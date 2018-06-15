<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\util\LogFactory;

class Log extends API_Controller {
    public function index_put() {
        $response = new Result();
        $content = $this->put('content');
        $logger = LogFactory::getLogger('dkm');
        $log_result = $logger->info($content);
        if ($log_result) {
            $response->set_success('日志已保存');
        } else {
            $response->set_error('日志保存出错');
        }

        $this->response($response);
    }
}
