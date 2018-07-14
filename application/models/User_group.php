<?php

namespace dkm\models;
use dkm\libraries\service\UserGroupService;

class User_group_model extends \DKM_Model {

    protected $table_name = 'user_group';

    /**
     * @param $name
     * @param $parent_id
     * @param $role_ids
     * @param $permission_ids
     * @param int $status
     * @return mixed
     */
    public function add($name, $parent_id, $role_ids, $permission_ids, $status = UserGroupService::USER_GROUP_STATUS_NORMAL) {
        $now = time();

        $data_array = [
            'name' => $name,
            'parent_id' => $parent_id,
            'role_ids' => $role_ids,
            'permission_ids' => $permission_ids,
            'status' => $status,
            'gmt_create' => $now,
            'gmt_update' => $now,
            'gmt_delete' => 0
        ];

        $this->insert($data_array);
        $affected_rows = $this->affected_rows();
        if (!$affected_rows) {
            return FALSE;
        }

        return $this->insert_id();
    }
}
