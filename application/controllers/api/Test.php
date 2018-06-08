<?php

use dkm\libraries\API_Controller;
use dkm\libraries\service\UserService;

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

    public function index_put() {
        $result = new Result();
        $result->set_success('test/index_put');
        $this->response($result);
    }

    public function index_delete() {
        $result = new Result();
        $result->set_success('test/index_delete');
        $this->response($result);
    }

    public function index_patch() {
        $result = new Result();
        $result->set_success('test/index_patch');
        $this->response($result);
    }
}
