<?php

namespace dkm\libraries\util;

/**
 * Class MemcachedLocker
 * @package dkm\libraries\util
 */
class MemcachedLocker {
    /**
     * @param $lock_name
     * @param int $time_out
     * @return bool
     */
    public static function getLock($lock_name, $expiration = 60) {
        $m_client = MemcachedFactory::get_dkm_client();

        $lock_name = self::generateLockName($lock_name);
        if ($m_client->add($lock_name, 1, $expiration)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param $lock_name
     * @return bool
     */
    public static function releaseLock($lock_name) {
        $m_client = MemcachedFactory::get_dkm_client();
        if (!$m_client) {
            return FALSE;
        }
        $lock_name = self::generateLockName($lock_name);
        return $m_client->del($lock_name);
    }

    /**
     * @param $lock_name
     * @return string
     */
    private static function generateLockName($lock_name) {
        return MemcachedKey::MEMCACHED_LOCK . $lock_name;
    }
}
