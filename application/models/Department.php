<?php

namespace dkm\models;
use dkm\libraries\service\DepartmentService;

/**
 * Class Department_model
 * @package dkm\models
 */
class Department_model extends \DKM_Model {

    protected $table_name = 'department';

    /**
     * @param $name
     * @param $parent_id
     * @param $permission_ids
     * @param int $status
     * @return bool
     */
    public function add($name, $parent_id, $permission_ids, $status = DepartmentService::DEPARTMENT_STATUS_NORMAL) {
        $now = time();

        $data_array = [
            'name' => $name,
            'parent_id' => $parent_id,
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
