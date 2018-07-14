<?php

namespace dkm\libraries\service;
use dkm\libraries\base\BaseService;

/**
 * Class UserGroupService
 * @package dkm\libraries\service
 * @method static UserGroupService get_instance()
 */
class UserGroupService extends BaseService {
    const USER_GROUP_STATUS_NORMAL = 1;
    const USER_GROUP_STATUS_DISABLE = 2;
    const USER_GROUP_STATUS_DELETE = 3;

    public static $USER_GROUP_STATUS_MAP = [
        self::USER_GROUP_STATUS_NORMAL => '正常',
        self::USER_GROUP_STATUS_DISABLE => '禁用',
        self::USER_GROUP_STATUS_DELETE => '删除'
    ];

    /**
     * @param $name
     * @param $parent_id
     * @param $role_ids
     * @param $permission_ids
     * @param int $status
     * @return \Result
     */
    public function addUserGroup($name, $parent_id, $role_ids = [], $permission_ids = [], $status = self::USER_GROUP_STATUS_NORMAL) {
        $result = $this->beforeAddUserGroup($name ,$parent_id, $role_ids, $permission_ids, $status);
        if (!$result->success) {
            return $result;
        }

        $this->doAddUserGroup($result);
        if (!$result->success) {
            return $result;
        }

        $this->afterAddUserGroup($result);
        return $result;
    }

    /**
     * @param $name
     * @param $parent_id
     * @param $role_ids
     * @param $permission_ids
     * @param $status
     * @return \Result
     */
    private function beforeAddUserGroup($name, $parent_id, $role_ids, $permission_ids, $status) {
        $result = new \Result();

        if (empty($name) || strlen($name) > 20) {
            $result->set_error('参数错误:name');
            return $result;
        }

        if (!check_id($parent_id, TRUE)) {
            $result->set_error('参数错误:parent_id');
            return $result;
        }

        if (!check_ids($role_ids, TRUE)) {
            $result->set_error('参数错误:role_ids');
            return $result;
        }

        if (!check_ids($permission_ids, TRUE)) {
            $result->set_error('参数错误:permission_ids');
            return $result;
        }

        if (!isset(self::$USER_GROUP_STATUS_MAP[$status])) {
            $result->set_error('参数错误:status');
            return $result;
        }

        // TODO 检查数据库中role_ids和permission_ids
        load_model('user_group');
        load_helper('dbquery');
        $qc = createMixedQueryCondition();
        $qc->where('name', WHERE_OPERATOR_EQUAL, $name);
        $qc->where('status', WHERE_OPERATOR_NOT_EQUAL, self::USER_GROUP_STATUS_DELETE);
        $db_user_group = $this->CI->user_group->safe_list_by_page_where($qc, 1, 1);
        if (!empty($db_user_group)) {
            $result->set_error('用户组名已存在');
            return $result;
        }

        $result->name = $name;
        $result->parent_id = $parent_id;
        $result->role_ids = implode(',', array_unique($role_ids));
        $result->permission_ids = implode(',', array_unique($permission_ids));
        $result->status = $status;

        $result->set_success();
        return $result;
    }

    /**
     * @param \Result $result
     * @return \Result
     */
    private function doAddUserGroup(&$result) {
        $name = $result->name;
        $parent_id = $result->parent_id;
        $role_ids = $result->role_ids;
        $permission_ids = $result->permission_ids;
        $status = $result->status;

        load_model('user_group');
        $user_group_id = $this->CI->user_group->add($name, $parent_id, $role_ids, $permission_ids, $status);
        if (empty($user_group_id)) {
            $result->set_error('网络繁忙');
            return $result;
        }

        $result->user_group_id = $user_group_id;
        $result->set_success('添加成功');
        return $result;
    }

    /**
     * @param \Result $result
     * @return \Result
     */
    private function afterAddUserGroup(&$result) {
        // 暂时什么也不做
        return $result;
    }
}
