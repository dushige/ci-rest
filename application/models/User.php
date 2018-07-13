<?php

namespace dkm\models;
use dkm\libraries\service\UserService;

/**
 * Class User_model
 * @package dkm\models
 */
class User_model extends \DKM_Model {

    protected $table_name = 'user';

    /**
     * get user by uid
     *
     * @param mixed $uid
     * @return mixed
     */
    public function get_by_uid($uid) {
        if (!check_id($uid)) {
            return FALSE;
        }
        return $this->get_by_id($uid);
    }

    /**
     * get users by uids
     *
     * @param array $uids
     * @return mixed
     */
    public function get_by_uids($uids) {
        if (!check_ids($uids)) {
            return FALSE;
        }
        return $this->get_by_ids($uids);
    }

    /**
     * get user by username
     *
     * @param string $username
     * @return mixed
     */
    public function get_by_username($username) {
        if (!check_username($username)) {
            return FALSE;
        }
        return $this->db->get_where($this->table_name, ['username' => $username])->row();
    }

    /**
     * get user by tel
     *
     * @param string $tel
     * @return mixed
     */
    public function get_by_tel($tel) {
        if (!check_tel($tel)) {
            return FALSE;
        }
        return $this->db->get_where($this->table_name, ['tel' => $tel])->row();
    }

    /**
     * get user by email
     *
     * @param string $email
     * @return mixed
     */
    public function get_by_email($email) {
        if (!check_email($email)) {
            return FALSE;
        }
        return $this->db->get_where($this->table_name, ['email' => $email])->row();
    }

    /**
     * update user by uid
     *
     * @param int $uid
     * @param array $field_array
     * @return mixed
     */
    public function update_by_uid($uid, $field_array) {
        if (!check_id($uid) || !is_array($field_array)) {
            return FALSE;
        }

        $this->update_by_id($uid, $field_array);
        return $this->affected_rows();
    }

    /**
     * add user
     *
     * @param string $username
     * @param string $password
     * @param string $name
     * @param string $email
     * @param string $tel
     * @param int $status
     * @return mixed
     */
    public function add($username, $password, $name, $email, $tel, $status = UserService::USER_STATUS_NORMAL) {
        $now = time();

        $data_array = [
            'name' => $name,
            'email' => $email,
            'tel' => $tel,
            'username' => $username,
            'password' => $password,
            'status' => $status,
            'gmt_create' => $now,
            'gmt_delete' => 0,
            'gmt_update' => $now
        ];

        $this->insert($data_array);
        $affected_rows = $this->affected_rows();
        if (!$affected_rows) {
            return FALSE;
        }

        return $this->insert_id();
    }
}
