<?php

use dkm\libraries\API_Controller;

class Test extends API_Controller {
    public function index_get() {
        $result = new Result();
        $result->set_success('test/index_get');
        $this->response($result);
    }

    public function index_post() {
        $result = new Result();
        $result->set_success('test/index_post');
        $this->response($result);
    }
}
