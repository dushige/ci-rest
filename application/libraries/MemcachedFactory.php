<?php

namespace dkm\libraries;

class MemcachedFactory {

    /**
     * @return \Cache_memcached
     */
    public static function get_dkm_memcached_client() {
        $CI =& get_instance();
        $object_name = 'DKMMemcachedClient';

        $region = ['region' => 'dkm'];
        if (!isset($CI->$object_name)) {
            $CI->load->library('cache/cache_memcached', $region, $object_name);
        }

        return $CI->$object_name;
    }
}