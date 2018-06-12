<?php

use dkm\libraries\API_Controller;
use dkm\libraries\RedisFactory;

class Test extends API_Controller {
    public function index_get() {
        $result = new Result();
        $redis_client = RedisFactory::get_dkm_redis_client();
        $value = $redis_client->get('test_key');
        $result->set_success($value);
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
