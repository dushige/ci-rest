<?php

namespace dkm\libraries\service;

class UserService extends BaseService {
    const USER_STATUS_NORMAL = 1;
    const USER_STATUS_DELETE = 2;
    const USER_STATUS_DISABLE = 3;

    public static $USER_STATUS_MAP = [
        self::USER_STATUS_NORMAL => '正常',
        self::USER_STATUS_DELETE => '删除',
        self::USER_STATUS_DISABLE => '禁用'
    ];

    /**
     * 用户注册
     *
     * @param string $username
     * @param string $password
     * @param string $name
     * @param string $email
     * @param string $tel
     * @param int $status
     * @return Result
     */
    public function regist($username, $password, $name, $email, $tel, $status = self::USER_STATUS_NORMAL) {
        $result = $this->beforeRegist($username, $password, $name, $email, $tel, $status);
        if (!$result->success) {
            return $result;
        }

        $this->doRegist($result);
        if (!$result->success) {
            return $result;
        }

        $this->afterRegist($result);
        return $result;
    }

    /**
     * 注册前检查参数
     *
     * @param string $username
     * @param string $password
     * @param string $name
     * @param string $email
     * @param string $tel
     * @param string $status
     * @return Result
     */
    private function beforeRegist($username, $password, $name, $email, $tel, $status) {
        $result = new \Result();

        if (!check_username($username)) {
            $result->set_error('用户名格式错误');
            return $result;
        }

        if (!check_password($password)) {
            $result->set_error('密码格式错误');
            return $result;
        }

        if (!check_name($name)) {
            $result->set_error('姓名格式错误');
            return $result;
        }

        if (!check_email($email)) {
            $result->set_error('邮箱格式错误');
            return $result;
        }

        if (!check_tel($tel)) {
            $result->set_error('手机号码格式错误');
            return $result;
        }

        if (!isset(self::$USER_STATUS_MAP[$status])) {
            $result->set_error('状态错误');
            return $result;
        }

        load_helper('dbquery');
        load_model('user');
        $qc = createMixedQueryCondition();
        $qc->where('username', WHERE_OPERATOR_EQUAL, $username);
        $qc->or_where('email', WHERE_OPERATOR_EQUAL, $email);
        $qc->or_where('tel', WHERE_OPERATOR_EQUAL, $tel);
        $db_user = $this->CI->user->safe_list_by_page_where($qc, 1, 1);
        if (!empty($db_user)) {
            $db_user = current($db_user);
            if ($username == $db_user->username) {
                $result->set_error('用户名已存在');
                return $result;
            } elseif ($email == $db_user->email) {
                $result->set_error('邮箱已注册');
                return $result;
            } elseif ($tel == $db_user->tel) {
                $result->set_error('手机号码已注册');
                return $result;
            }
        }

        load_lib('encryption');
        $result->password = $this->CI->encryption->encrypt($password);
        $result->username = $username;
        $result->name = $name;
        $result->email = $email;
        $result->tel = $tel;
        $result->status = $status;

        $result->set_success();
        return $result;
    }

    /**
     * 注册
     *
     * @param Result $result
     * @return Result
     */
    private function doRegist(&$result) {
        $username = $result->username;
        $password = $result->password;
        $name = $result->name;
        $email = $result->email;
        $tel = $result->tel;
        $status = $result->status;

        $this->CI->load->model('user');
        $uid = $this->CI->user->add($username, $password, $name, $email, $tel, $status);
        if (empty($uid)) {
            $result->set_error('网络繁忙');
            return $result;
        }

        $result->uid = $uid;
        $result->set_success('注册成功');
        return $result;
    }

    /**
     * 注册后
     *
     * @param Result $result
     * @return void
     */
    private function afterRegist(&$result) {
        // 暂时不做什么
        return $result;
    }

    /**
     * 用户登陆，支持username, tel, email登陆
     *
     * @param string $username
     * @param string $password
     * @param string $tel
     * @param string $email
     * @return boolean
     */
    public function login($field, $password) {
        $result = new \Result();
        if (!check_password($password)) {
            $result->set_error('信息有误，尝试重新输入');
            return $result;
        }

        load_model('user');
        if (check_username($field)) {
            $user = $this->CI->user->get_by_username($field);
        } elseif (check_tel($field)) {
            $user = $this->CI->user->get_by_tel($field);
        } elseif (check_email($field)) {
            $user = $this->CI->user->get_by_email($field);
        } else {
            $result->set_error('信息有误，尝试重新输入');
            return $result;
        }

        load_lib('encryption');
        if ($password != $this->CI->encryption->decrypt($user->password)) {
            $result->set_error('信息有误，尝试重新输入');
            return $result;
        } else {
            $this->writeSession($user->id, $user->email, $user->name, $user->username);
            $result->uid = $user->id;
            $result->set_success('登陆成功');
            return $result;
        }
    }

    protected function writeSession($uid, $email, $name, $username) {
        $this->CI->session->set_userdata('_username_', $username);
        $this->CI->session->set_userdata('_name_', $name);
        $this->CI->session->set_userdata('_email_', $email);
        $this->CI->session->set_userdata('_uid_', $uid);
        $this->CI->session->set_userdata('_is_login_', TRUE);
    }
}
