<?php

namespace dkm\models;
use dkm\libraries\service\RoleService;

/**
 * Class Role_model
 * @package dkm\models
 */
class Role_model extends \DKM_Model {

    protected $table_name = 'role';

    /**
     * @param $name
     * @param $permission_ids
     * @param int $status
     * @return bool
     */
    public function add($name, $permission_ids, $status = RoleService::ROLE_STATUS_NORMAL) {
        $now = time();

        $data_array = [
            'name' => $name,
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
