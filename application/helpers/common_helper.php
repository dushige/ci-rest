<?php
/**
 * 加载library
 *
 * @param mixed $lib
 * @param string $object_name
 * @param array $params
 * @return Object
 */
function load_lib($lib, $object_name = NULL, $params = NULL) {
    $CI = &get_instance();
    return $CI->load->library($lib, $params, $object_name);
}

/**
 * 加载model
 *
 * @param mixed $model
 * @param string $name
 * @return Object
 */
function load_model($model, $name = NULL) {
    $CI = &get_instance();
    return $CI->load->model($model, $name);
}

/**
 * 加载helper
 *
 * @param mixed $helpers
 * @return Object
 */
function load_helper($helpers) {
    $CI = &get_instance();
    return $CI->load->helper($helpers);
}

/**
 * 判断用户是否登陆
 *
 * @return boolean
 */
function is_login() {
    $CI = &get_instance();

    if (!isset($CI->session)) {
        return FALSE;
    }

    return $CI->session->userdata('_is_login_') == true;
}

/**
 * 直接读取input
 *
 * @return string
 */
function raw_post() {
    return trim(file_get_contents('php://input'));
}

/**
 * 当前uid
 *
 * @return mixed
 */
function current_uid() {
    $CI = &get_instance();

    if (!isset($CI->session)) {
        return FALSE;
    }

    return $CI->session->userdata('_uid_');
}

/**
 * 检查类似id的格式，必须是整型或字符串整数
 *
 * @param int $id
 * @return boolean
 */
function check_id($id) {
    if (empty($id)) {
        return FALSE;
    } elseif (is_int($id) || is_string($id) && ctype_digit($id)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

/**
 * 检查类似ids的格式
 *
 * @param array $ids
 * @return boolean
 */
function check_ids($ids) {
    if (empty($ids) || !is_array($ids)) {
        return FALSE;
    }

    foreach ($ids as $id) {
        if (!check_id($id)) {
            return FALSE;
        }
    }

    return TRUE;
}

/**
 * result to array
 *
 * @param mixed $result
 * @param string $field
 * @return array
 */
function result_to_array($result, $field = 'id') {
    $ary = array();
    if (!$result || !is_array($result)) {
        return $ary;
    }

    foreach ($result as $entry) {
        if (is_array($entry)) {
            $ary[] = $entry[$field];
        } else if (is_object($entry)) {
            $ary[] = $entry->$field;
        }
    }
    return $ary;
}

/**
 * result to map
 *
 * @param mixed $result
 * @param string $field
 * @return array
 */
function result_to_map($result, $field = 'id') {
    $map = array();
    if (!$result || !is_array($result)) {
        return $map;
    }

    foreach ($result as $entry) {
        if (is_array($entry)) {
            $map[$entry[$field]] = $entry;
        } else {
            $map[$entry->$field] = $entry;
        }
    }
    return $map;
}

/**
 * result to complex map
 *
 * @param mixed $result
 * @param string $field
 * @return array
 */
function result_to_complex_map($result, $field = 'id') {
    $map = array();
    if (!$result || !is_array($result)) {
        return $map;
    }

    foreach ($result as $entry) {
        if (is_array($entry)) {
            if (isset($map[$entry[$field]])) {
                $map[$entry[$field]][] = $entry;
            } else {
                $map[$entry[$field]] = [$entry];
            }
        } else {
            if (isset($map[$entry->$field])) {
                $map[$entry->$field][] = $entry;
            } else {
                $map[$entry->$field] = [$entry];
            }
        }
    }
    return $map;
}

/**
 * 检查username格式
 *
 * @param string $username
 * @return boolean
 */
function check_username($username) {
    $pattern = '/^[\w\_\.]{3,20}$/u';
    if (!preg_match($pattern, $username)) {
        return FALSE;
    }
    return TRUE;
}

/**
 * 检查password格式
 *
 * @param string $password
 * @return boolean
 */
function check_password($password) {
    $pattern = '/^(?![a-zA-Z]+$)(?![A-Z0-9]+$)(?![A-Z\W_!@#$%^&*`~()-+=]+$)(?![a-z0-9]+$)(?![a-z\W_!@#$%^&*`~()-+=]+$)(?![0-9\W_!@#$%^&*`~()-+=]+$)[a-zA-Z0-9\W_!@#$%^&*`~()-+=]{8,30}$/';
    if (!preg_match($pattern, $password)) {
        return FALSE;
    }
    return TRUE;
}

/**
 * 检查email格式
 *
 * @param string $email
 * @return boolean
 */
function check_email($email) {
    $pattern = '/^[a-z]([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?$/i';
    if (!preg_match($pattern, $email)) {
        return FALSE;
    }
    return TRUE;
}

/**
 * 检查姓名格式
 *
 * @param string $name
 * @return boolean
 */
function check_name($name) {
    $pattern = '/^([a-zA-Z0-9\x{4e00}-\x{9fa5}\·\. ]{1,10})$/u';
    if (!preg_match($pattern, $name)) {
        return FALSE;
    }
    return TRUE;
}

/**
 * 检查手机号码格式
 *
 * @param string $tel
 * @return boolean
 */
function check_tel($tel) {
    $pattern = '/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\\d{8}$/';
    if (!preg_match($pattern, $tel)) {
        return FALSE;
    }
    return TRUE;
}

/**
 * 检查url格式
 *
 * @param string $url
 * @return boolean
 */
function check_url($url) {
    $pattern = "/^((https|http|ftp|rtsp|mms)?\:\/\/)?(([0-9a-z_!~*'().&=+$%-]+\: )?[0-9a-z_!~*'().&=+$%-]+@)?(([0-9]{1,3}\\.){3}[0-9]{1,3}|([0-9a-z_!~*'()-]+\\.)*([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\\.[a-z]{2,6})(\:[0-9]{1,4})?((\/?)|(\/[0-9a-z_!~*'().;?\:@&=+$,%#-]+)+\/?)$/";
    if (!preg_match($pattern, $url)) {
        return FALSE;
    }
    return TRUE;
}

/**
 * 检查md5格式
 *
 * @param $md5
 * @return boolean
 */
function check_md5($md5) {
    $pattern = '/^([a-fA-F0-9]{32})$/';
    if (!preg_match($pattern, $md5)) {
        return FALSE;
    }
    return TRUE;
}

/**
 * 检查图片大小，最大20M
 *
 * @param $size
 * @return boolean
 */
function check_img_size($size) {
    if (!is_int($size)) {
        return FALSE;
    }
    if ($size > 20 * 1024 * 1024) {
        return FALSE;
    }

    return TRUE;
}