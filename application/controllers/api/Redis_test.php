<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\util\RedisFactory;

class Redis_test extends API_Controller {
    public function get_get() {
        $key = $this->get('key');

        $response = new Result();

        $r_client = RedisFactory::get_dkm_client();
        $value = $r_client->get($key);
        if ($value === FALSE) {
            $response->set_error($value);
            $this->response($response);
        }

        $response->set_success($value);
        $this->response($response);
    }

    public function set_get() {
        $key = $this->get('key');
        $value = $this->get('value');
        $expire = $this->get('expire') ? $this->get('expire') : 60;

        $response = new Result();

        $r_client = RedisFactory::get_dkm_client();
        $result = $r_client->setEx($key, $value ,$expire);

        if ($result === FALSE) {
            $response->set_error($result);
            $this->response($response);
        }

        $response->set_success();
        $this->response($response);
    }
}
