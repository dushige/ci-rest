<?php

use dkm\libraries\API_Controller;
use dkm\libraries\service\UserService;

class Login extends API_Controller {
    public function index_post() {
        $username = $this->post('username');
        $password = $this->post('password');
        $result = new Result();

        if (is_login()) {
            $result->uid = current_uid();
            $result->set_success('已登陆');
        } else {
            $this->session->sess_regenerate();
            $userService = UserService::get_instance();
            $login_result = $userService->login($username, $password);
            if ($login_result->success) {
                $result->uid = $login_result->uid;
                $result->set_success($login_result->message);
            } else {
                $result->set_error($login_result->message);
            }
        }

        $this->response($result);
    }
}
