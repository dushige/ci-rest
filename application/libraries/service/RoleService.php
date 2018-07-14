<?php

namespace dkm\libraries\service;
use dkm\libraries\base\BaseService;

/**
 * Class RoleService
 * @package dkm\libraries\service
 * @method static RoleService get_instance()
 */
class RoleService extends BaseService {
    const ROLE_STATUS_NORMAL = 1;
    const ROLE_STATUS_DISABLE = 2;
    const ROLE_STATUS_DELETE = 3;

    public static $ROLE_STATUS_MAP = [
        self::ROLE_STATUS_NORMAL => '正常',
        self::ROLE_STATUS_DISABLE => '禁用',
        self::ROLE_STATUS_DELETE => '删除'
    ];

    /**
     * @param $name
     * @param $permission_ids
     * @param int $status
     * @return \Result
     */
    public function addRole($name, $permission_ids = [], $status = self::ROLE_STATUS_NORMAL) {
        $result = $this->beforeAddRole($name, $permission_ids, $status);
        if (!$result->success) {
            return $result;
        }

        $this->doAddRole($result);
        if (!$result->success) {
            return $result;
        }

        $this->afterAddRole($result);
        return $result;
    }

    /**
     * @param $name
     * @param $permission_ids
     * @param $status
     * @return \Result
     */
    private function beforeAddRole($name, $permission_ids, $status) {
        $result = new \Result();

        if (empty($name) || strlen($name) > 20) {
            $result->set_error('参数错误:name');
            return $result;
        }

        if (!check_ids($permission_ids, TRUE)) {
            $result->set_error('参数错误:permission_ids');
            return $result;
        }

        if (!isset(self::$ROLE_STATUS_MAP[$status])) {
            $result->set_error('参数错误:status');
            return $result;
        }

        // TODO 查询数据库permission_ids
        load_helper('dbquery');
        load_model('role');
        $qc = createMixedQueryCondition();
        $qc->where('name', WHERE_OPERATOR_EQUAL, $name);
        $qc->where('status', WHERE_OPERATOR_NOT_EQUAL, self::ROLE_STATUS_DELETE);
        $db_role = $this->CI->role->safe_list_by_page_where($qc, 1, 1);
        if (!empty($db_role)) {
            $result->set_error('角色名已存在');
            return $result;
        }

        $result->name = $name;
        $result->permission_ids = implode(',', $permission_ids);
        $result->status = $status;

        $result->set_success();
        return $result;
    }

    /**
     * @param \Result $result
     * @return \Result
     */
    private function doAddRole(&$result) {
        $name = $result->name;
        $permission_ids = $result->permission_ids;
        $status = $result->status;

        load_model('role');
        $role_id = $this->CI->role->add($name, $permission_ids, $status);
        if (empty($role_id)) {
            $result->set_error('网络繁忙');
            return $result;
        }

        $result->role_id = $role_id;
        $result->set_success('添加成功');
        return $result;
    }

    /**
     * @param \Result $result
     * @return \Result
     */
    private function afterAddRole(&$result) {
        // 暂时什么也不做
        return $result;
    }
}
