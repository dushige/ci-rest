<?php

use dkm\libraries\API_Controller;
use dkm\libraries\service\UserService;

class User extends API_Controller {
    public function get_get() {
        $response = new Result();
        $field = $this->get('field');
        $field_value = $this->get('field_value');
        $userService = UserService::get_instance();
        $get_result = $userService->getUser($field_value, $field);
        if (!$get_result->success) {
            $response->set_error($get_result->message);
            $this->response($response);
        }

        $response->set_success($get_result->message);
        $response->user = $get_result->user;
        $this->response($response);
    }

    public function disable_get() {
        $response = new Result();
        $field = $this->get('field');
        $field_value = $this->get('field_value');
        $userService = UserService::get_instance();
        $disable_result = $userService->disableUser($field_value, $field);
        if (!$disable_result->success) {
            $response->set_error($disable_result->message);
            $this->response($response);
        }

        $response->set_success($disable_result->message);
        $response->uid = $disable_result->uid;
        $this->response($response);
    }
}