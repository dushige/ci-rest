<?php

namespace dkm\libraries\service;

use dkm\libraries\BaseService;

/**
 * Class UserService
 * @package dkm\libraries\service
 * @method static UserService get_instance()
 */
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
     * @return \Result
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
     * @return \Result
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
     * @param \Result $result
     * @return \Result
     */
    private function doRegist(&$result) {
        $username = $result->username;
        $password = $result->password;
        $name = $result->name;
        $email = $result->email;
        $tel = $result->tel;
        $status = $result->status;

        load_model('user');
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
     * @param \Result $result
     * @return \Result
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
     * @return \Result
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

    /**
     * 写Session
     *
     * @param int $uid
     * @param string $email
     * @param string $name
     * @param string $username
     * @return void
     */
    protected function writeSession($uid, $email, $name, $username) {
        $this->CI->session->set_userdata('_username_', $username);
        $this->CI->session->set_userdata('_name_', $name);
        $this->CI->session->set_userdata('_email_', $email);
        $this->CI->session->set_userdata('_uid_', $uid);
        $this->CI->session->set_userdata('_is_login_', TRUE);
    }

    /**
     * 禁用用户，field可取'uid', 'username', 'email', 'tel'
     *
     * @param mixed $field_value
     * @param string $field
     * @return \Result
     */
    public function disableUser($field_value, $field = 'uid') {
        $result = $this->beforeDisableUser($field_value, $field);
        if (!$result->success) {
            return $result;
        }

        $this->doDisableUser($result);
        if (!$result->success) {
            return $result;
        }

        $this->afterDisableUser($result);
        return $result;
    }

    /**
     * 禁用用户前参数检查
     *
     * @param mixed $field_value
     * @param string $field
     * @return \Result
     */
    private function beforeDisableUser($field_value, $field = 'uid') {
        $result = new \Result();

        $get_user_result = $this->getUser($field_value, $field);
        if (!$get_user_result->success) {
            $result->set_error($get_user_result->message);
            return $result;
        }

        $user = $get_user_result->user;
        if (isset($user->status) && in_array($user->status, [self::USER_STATUS_DISABLE, self::USER_STATUS_DELETE])) {
            $result->set_error('用户已被禁用或已被删除');
            return $result;
        }

        $result->uid = $user->id;
        $result->set_success('禁用成功');
        return $result;
    }

    /**
     * 禁用用户
     *
     * @param \Result $result
     * @return \Result
     */
    private function doDisableUser(&$result) {
        $uid = $result->uid;

        $disable_result = $this->CI->user->update_by_uid($uid, ['status' => self::USER_STATUS_DISABLE]);
        if (empty($disable_result)) {
            $result->set_error('禁用失败');
            return $result;
        }

        $result->set_success('禁用成功');
        return $result;
    }

    /**
     * after disable user
     *
     * @param \Result $result
     * @return \Result
     */
    private function afterDisableUser(&$result) {
        // 暂时啥也不做
        return $result;
    }

    /**
     * get user by uid or email or username or tel
     *
     * @param $field_value
     * @param string $field
     * @return \Result
     */
    public function getUser($field_value, $field = 'uid') {
        $result = new \Result();
        load_model('user');

        if ($field == 'uid') {
            if (!check_id($field_value)) {
                $result->set_error('参数错误:uid');
                return $result;
            } else {
                $user = $this->CI->user->get_by_uid($field_value);
            }
        } elseif ($field == 'email') {
            if (!check_email($field_value)) {
                $result->set_error('参数错误:email');
                return $result;
            } else {
                $user = $this->CI->user->get_by_email($field_value);
            }
        } elseif ($field == 'tel') {
            if (!check_tel($field_value)) {
                $result->set_error('参数错误:tel');
                return $result;
            } else {
                $user = $this->CI->user->get_by_tel($field_value);
            }
        } elseif ($field == 'username') {
            if (!check_username($field_value)) {
                $result->set_error('参数错误:username');
                return $result;
            } else {
                $user = $this->CI->user->get_by_username($field_value);
            }
        } else {
            $result->set_error('参数错误:field');
            return $result;
        }

        if (empty($user)) {
            $result->set_error('用户不存在');
            return $result;
        }

        $result->set_success('获取成功');
        $result->user = $user;
        return $result;
    }
}
