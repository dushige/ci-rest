<?php

namespace dkm\libraries\util;

use dkm\libraries\RedisFactory;

/**
 * Class RedisLocker
 * @package dkm\libraries\util
 */
class RedisLocker {
    /**
     * @param $lock_name
     * @param int $time_out
     * @return bool
     */
    public static function getLock($lock_name, $time_out = 60) {
        $redis_client = RedisFactory::get_dkm_redis_client();

        $lock_name = self::generateLockName($lock_name);
        $ret = $redis_client->incr($lock_name);
        if ($ret === 1) {
            $redis_client->setTimeout($lock_name, $time_out);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param $lock_name
     * @return bool
     */
    public static function releaseLock($lock_name) {
        $redis_client = RedisFactory::get_dkm_redis_client();
        if (!$redis_client) {
            return FALSE;
        }
        $lock_name = self::generateLockName($lock_name);
        return $redis_client->del($lock_name);
    }

    /**
     * @param $lock_name
     * @return string
     */
    private static function generateLockName($lock_name) {
        return RedisKey::REDIS_LOCK . $lock_name;
    }
}