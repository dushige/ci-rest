<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\util\MemcachedFactory;

class Memcached_test extends API_Controller {
    public function index_get() {
        $key = $this->get('key');

        $response = new Result();

        $m_client = MemcachedFactory::get_dkm_client();
        $value = $m_client->get($key);
        if ($value === FALSE) {
            $response->set_error($value);
            $this->response($response);
        }

        $response->set_success($value);
        $this->response($response);
    }

    public function index_put() {
        $key = $this->put('key');
        $value = $this->put('value');
        $expire = $this->put('expire') ? $this->put('expire') : 60;

        $response = new Result();

        $m_client = MemcachedFactory::get_dkm_client();
        $result = $m_client->set($key, $value ,$expire);

        if ($result === FALSE) {
            $response->set_error();
            $this->response($response);
        }

        $response->set_success();
        $this->response($response);
    }
}
