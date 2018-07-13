<?php

namespace dkm\libraries\service;
use dkm\libraries\base\BaseService;

/**
 * Class DepartmentService
 * @package dkm\libraries\service
 * @method static DepartmentService get_instance()
 */
class DepartmentService extends BaseService {
    const DEPARTMENT_STATUS_NORMAL = 1;
    const DEPARTMENT_STATUS_DISABLE = 2;
    const DEPARTMENT_STATUS_DELETE = 3;

    public static $DEPARTMENT_STATUS_MAP = [
        self::DEPARTMENT_STATUS_NORMAL => '正常',
        self::DEPARTMENT_STATUS_DISABLE => '禁用',
        self::DEPARTMENT_STATUS_DELETE => '删除'
    ];

    /**
     * @param $name
     * @param $parent_id
     * @param $permission_ids
     * @param int $status
     * @return \Result
     */
    public function addDepartment($name, $parent_id, $permission_ids, $status = self::DEPARTMENT_STATUS_NORMAL) {
        $result = $this->beforeAddDepartment($name, $parent_id, $permission_ids, $status);
        if (!$result->success) {
            return $result;
        }

        $this->doAddDepartment($result);
        if (!$result->success) {
            return $result;
        }

        $this->afterAddDepartment($result);
        return $result;
    }

    /**
     * @param $name
     * @param $parent_id
     * @param $permission_ids
     * @param $status
     * @return \Result
     */
    private function beforeAddDepartment($name, $parent_id, $permission_ids, $status) {
        $result = new \Result();

        if (empty($name) || strlen($name) > 20) {
            $result->set_error('参数错误:name');
            return $result;
        }

        if (!check_id($parent_id, TRUE)) {
            $result->set_error('参数错误:parent_id');
            return $result;
        }

        if (!check_ids($permission_ids, TRUE)) {
            $result->set_error('参数错误:permission_ids');
            return $result;
        }

        if (!isset(self::$DEPARTMENT_STATUS_MAP[$status])) {
            $result->set_error('参数错误:status');
            return $result;
        }

        // TODO 从数据库中读取权限
        load_helper('dbquery');
        load_model('department');
        $qc = createMixedQueryCondition();
        $qc->where('name', WHERE_OPERATOR_EQUAL, $name);
        $qc->where('status', WHERE_OPERATOR_NOT_EQUAL, self::DEPARTMENT_STATUS_DELETE);
        $db_department = $this->CI->department->safe_list_by_page_where($qc, 1, 1);
        if (!empty($db_department)) {
            $result->set_error('部门名已存在');
            return $result;
        }

        $result->name = $name;
        $result->parent_id = $parent_id;
        $result->permission_ids = implode(',', array_unique($permission_ids));
        $result->status = $status;

        $result->set_success();
        return $result;
    }

    /**
     * @param \Result $result
     * @return \Result
     */
    private function doAddDepartment(&$result) {
        $name = $result->name;
        $parent_id = $result->parent_id;
        $permission_ids = $result->permission_ids;
        $status = $result->status;

        load_model('department');
        $department_id = $this->CI->department->add($name, $parent_id, $permission_ids, $status);
        if (empty($department_id)) {
            $result->set_error('网络繁忙');
            return $result;
        }

        $result->department_id = $department_id;
        $result->set_success('添加成功');
        return $result;
    }

    /**
     * @param \Result $result
     * @return \Result
     */
    private function afterAddDepartment(&$result) {
        // 暂时什么都不做
        return $result;
    }
}
