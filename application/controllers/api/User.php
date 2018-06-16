<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\service\UserService;

class User extends API_Controller {
    public function index_get() {
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

    public function disable_post() {
        $response = new Result();
        $field = $this->post('field');
        $field_value = $this->post('field_value');
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

    public function index_delete() {
        $response = new Result();
        $field = $this->delete('field');
        $field_value = $this->delete('field_value');
        $userService = UserService::get_instance();
        $disable_result = $userService->deleteUser($field_value, $field);
        if (!$disable_result->success) {
            $response->set_error($disable_result->message);
            $this->response($response);
        }

        $response->set_success($disable_result->message);
        $response->uid = $disable_result->uid;
        $this->response($response);
    }
}
