<?php

namespace dkm\libraries\util;

class RedisFactory {

    /**
     * @return \Cache_redis
     */
    public static function get_dkm_client() {
        $CI =& get_instance();
        $object_name = 'DKMRedisClient';

        $group = ['group_name' => 'dkm'];
        if (!isset($CI->$object_name)) {
            $CI->load->library('cache/cache_redis', $group, $object_name);
        }

        return $CI->$object_name;
    }
}
