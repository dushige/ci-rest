<?php

use dkm\libraries\service\UserService;

class User extends DKM_Model {

    protected $table_name = 'user';

    public function get_by_uid($uid) {
        if (!check_id($uid)) {
            return FALSE;
        }
        return $this->get_by_id($uid);
    }

    public function get_by_uids($uids) {
        if (!check_ids($uids)) {
            return FALSE;
        }
        return $this->get_by_ids($uids);
    }

    public function get_by_username($username) {
        if (!check_username($username)) {
            return FALSE;
        }
        return $this->db->get_where($this->table_name, ['username' => $username])->row();
    }

    public function get_by_tel($tel) {
        if (!check_tel($tel)) {
            return FALSE;
        }
        return $this->db->get_where($this->table_name, ['tel' => $tel])->row();
    }

    public function get_by_email($email) {
        if (!check_email($email)) {
            return FALSE;
        }
        return $this->db->get_where($this->table_name, ['email' => $email])->row();
    }

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

        $this->insert($this->table_name, $data_array);
        $affected_rows = $this->affected_rows();
        if (!$affected_rows) {
            return FALSE;
        }

        return $this->insert_id();
    }
}
