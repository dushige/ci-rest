<?php
/**
 * 基于 memcached 实现的 lock
 * memcached 错误码：http://cn.php.net/manual/zh/memcached.getresultcode.php
 */
class Lock {

    // memcache 实例
    private $memcached;

    // 已经获取的锁
    private $locks = [];

    // 获取锁失败后尝试的次数，3次，从0开始计数
    private $try_times = 2;

    private $region = 'lock';

    private $key_prefix = 'prefix_memcached_lock_key_';

    // 错误码
    public static $ERR_MEMCACHED_NOT_EXISTS = 1;
    public static $ERR_MEMCACHED_ERROR = 2;
    public static $ERR_LOCK_ALREAY_EXISTS = 3;

    public function __construct() {
        $this->memcached = NULL;
    }

    private function init() {
        if ($this->memcached) {
            return;
        }
        if (!function_exists('get_instance')) {
            return;
        }
        load_lib('cache_common:memcached_library');
        $CI = & get_instance();
        $this->memcached = $CI->memcached_library;
    }

    private function get_key($key) {
        return $this->key_prefix . $key;
    }

    /**
     * 获取锁
     * @param $key 键名
     * @param $retry 失败是否重试
     * @param $expiration 过期时间，单位秒
     */
    public function get($key, $retry = FALSE, $expiration = 10) {
        if (isset($this->locks[$key])) {
            return TRUE;
        }
        $this->init();
        if (!$this->memcached) {
            return self::$ERR_MEMCACHED_NOT_EXISTS;
        }
        $cache_key = $this->get_key($key);
        $try_times = $this->try_times;
        $err = TRUE;
        do {
            $succ = $this->memcached->add($this->region, $cache_key, 1, $expiration);
            if ($succ === TRUE) {
                $this->locks[$key] = $key;
                return TRUE;
            }
            $err = $this->memcached->get_result_code();
            if ($err === Memcached::RES_NOTSTORED) {
                // 锁已经存在
                $err = self::$ERR_LOCK_ALREAY_EXISTS;
            } else {
                // memcache 错误
                $err = self::$ERR_MEMCACHED_ERROR;
            }
            if ($retry && $try_times--) {
                usleep(200); // 微秒
            } else {
                break;
            }
        } while (TRUE);
        return $err;
    }

    /**
     * 释放锁，可以不用主动调用，交给php析构函数
     * @param $key 键名
     */
    public function release($key) {
        $this->init();
        if (!$this->memcached) {
            return self::$ERR_MEMCACHED_NOT_EXISTS;
        }
        $cache_key = $this->get_key($key);
        $succ = $this->memcached->delete($this->region, $cache_key);
        if ($succ) {
            unset($this->locks[$key]);
        }
        return $succ;
    }

    public function __destruct() {
        $this->init();
        if (!$this->memcached) {
            return;
        }
        foreach ($this->locks as $lock) {
            $this->release($lock);
        }
    }
}
