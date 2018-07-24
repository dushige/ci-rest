<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\service\UserGroupService;

class User_group extends API_Controller {
    public function index_put() {
        $response = new Result();

        $name = $this->put('name');
        $parent_id = $this->put('parent_id');

        $userGroupService = UserGroupService::get_instance();
        $add_result = $userGroupService->addUserGroup($name, $parent_id);
        if (!$add_result->success) {
            $response->set_error($add_result->message);
            $this->response($response);
        }

        $response->user_group_id = $add_result->user_group_id;
        $response->set_success($add_result->message);
        $this->response($response);
    }
}
