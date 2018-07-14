<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\service\RoleService;

class Role extends API_Controller {
    public function index_put() {
        $response = new Result();

        $name = $this->put('name');

        $roleService = RoleService::get_instance();
        $add_result = $roleService->addRole($name);
        if (!$add_result->success) {
            $response->set_error($add_result->message);
            $this->response($response);
        }

        $response->role_id = $add_result->role_id;
        $response->set_success($add_result->message);
        $this->response($response);
    }
}
