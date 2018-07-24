<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\service\UserService;

class Register extends API_Controller {
    public function index_put() {
        $username = $this->put('username');
        $password = $this->put('password');
        $name = $this->put('name');
        $email = $this->put('email');
        $tel = $this->put('tel');

        $result = new Result();
        $userService = UserService::get_instance();
        $register_result = $userService->register($username, $password, $name, $email, $tel);
        if (!$register_result->success) {
            $result->set_error($register_result->message);
            $this->response($result);
        } else {
            $result->uid = $register_result->uid;
            $result->set_success($register_result->message);
            $this->response($result);
        }
    }
}
