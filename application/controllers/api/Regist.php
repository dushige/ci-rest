<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\service\UserService;

class Regist extends API_Controller {
    public function index_put() {
        $username = $this->put('username');
        $password = $this->put('password');
        $name = $this->put('name');
        $email = $this->put('email');
        $tel = $this->put('tel');

        $result = new Result();
        $userService = UserService::get_instance();
        $regist_result = $userService->regist($username, $password, $name, $email, $tel);
        if (!$regist_result->success) {
            $result->set_error($regist_result->message);
            $this->response($result);
        } else {
            $result->uid = $regist_result->uid;
            $result->set_success($regist_result->message);
            $this->response($result);
        }
    }
}
