<?php

/**
 * Class Cache_memcached
 */
class Cache_memcached {

    /**
     * @var string
     */
    protected $region;

    /**
     * @var CI_Controller
     */
    protected $ci;

    /**
     * current memcached instance
     * @var Memcached
     */
    protected $m;

    /**
     * default timeout  milliseconds
     * @var int
     */
    protected $default_timeout = 100;

    /**
     * Cache_memcached constructor.
     * @param array $region
     * @throws Exception
     */
    public function __construct(array $region) {
        if (!isset($region['region'])) {
            throw new Exception('无效的参数');
        }

        $this->ci =& get_instance();
        if ($this->ci->load->config('memcached', TRUE, TRUE)) {
            $config = $this->ci->config->item('memcached')[$region['region']];
            if (empty($config) || !is_array($config)) {
                throw new Exception("无效的配置:memcached['$region']'");
            }
            $this->region = $region['region'];
        }

        if (!$this->connect($config)) {
            if (!$this->connect($config)) {
                $this->connect($config, TRUE);
            }
        }
    }

    /**
     * @param $config
     * @param bool $log_error
     * @return bool
     */
    public function connect($config, $log_error = FALSE) {
        if (empty($config) || !is_array($config)) {
            return FALSE;
        }

        $timeout = $this->default_timeout;
        if (isset($config['timeout'])) {
            $timeout = (int) $config['timeout'];
        }
        if ($timeout < $this->default_timeout) {
            $timeout = $this->default_timeout;
        }

        try {
            $m = new Memcached();
            $m->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $timeout);
            $m->setOption(\Memcached::OPT_POLL_TIMEOUT, $timeout);
            $m->addServer($config['host'], $config['port'], $config['weight']);
        } catch (Exception $e) {
            if ($log_error) {
                $logger = \dkm\libraries\LogFactory::getLogger('dkm');
                $logger->error('MemcachedConnectionError', ['error' => $e->getMessage()]);
            }
            return FALSE;
        }

        $this->m = $m;

        return TRUE;
    }

    /**
     * @param $key
     * @return bool|mixed
     */
    public function get($key) {
        $key = $this->genKeyName($key);
        if ($this->m) {
            try {
                return @$this->m->get($key);
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * @param $key
     * @param $value
     * @param null $expiration
     * @return bool
     */
    public function add($key, $value, $expiration = NULL) {
        $key = $this->genKeyName($key);

        if ($this->m) {
            try {
                return @$this->m->add($key, $value, $expiration);
            } catch (Exception $e) {
                return FALSE;
            }
        }

        return FALSE;
    }

    /**
     * @param $key
     * @param $value
     * @param null $expiration
     * @return bool
     */
    public function set($key, $value, $expiration = NULL) {
        $key = $this->genKeyName($key);

        if ($this->m) {
            try {
                return @$this->m->set($key, $value, $expiration);
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * @param $key
     * @param int $offset
     * @param int $initial_value
     * @param int $expiration
     * @return bool|int
     */
    public function incr($key, $offset = 1, $initial_value = 0, $expiration = 0) {
        $key = $this->genKeyName($key);

        if ($this->m) {
            try {
                return @$this->m->increment($key, $offset, $initial_value, $expiration);
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * @param $key
     * @param int $offset
     * @param int $initial_value
     * @param int $expiration
     * @return bool|int
     */
    public function decr($key, $offset = 1, $initial_value = 0, $expiration = 0) {
        $key = $this->genKeyName($key);

        if ($this->m) {
            try {
                return @$this->m->decrement($key, $offset, $initial_value, $expiration);
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * @param $key
     * @param int $time
     * @return bool
     */
    public function delete($key, $time = 0) {
        $key = $this->genKeyName($key);

        if ($this->m) {
            try {
                return @$this->m->delete($key, $time);
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * @param $keys
     * @return array|bool|mixed
     */
    public function getMulti($keys) {
        if (!is_array($keys)) {
            return FALSE;
        }

        if (empty($keys)) {
            return [];
        }

        foreach ($keys as $k => $key) {
            $keys[$k] = $this->genKeyName($key);
        }

        if ($this->m) {
            try {
                return @$this->m->getMulti($keys);
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * @param $items
     * @param null $expiration
     * @return array|bool
     */
    public function putMulti($items, $expiration = NULL) {
        if (!is_array($items)) {
            return FALSE;
        }

        if (empty($items)) {
            return [];
        }

        $items_ = [];
        foreach ($items as $key => $value) {
            $items_[$this->genKeyName($key)] = $value;
        }

        if ($this->m) {
            try {
                return @$this->m->setMulti($items_, $expiration);
            } catch (Exception $e) {
                return FALSE;
            }
        }

        return FALSE;
    }

    /**
     * @return bool|int
     */
    public function getResultCode() {
        if ($this->m) {
            try {
                return @$this->m->getResultCode();
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * @return array|bool
     */
    public function getVersion() {
        if ($this->m) {
            try {
                return @$this->m->getVersion();
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * @return array|bool
     */
    public function getStats() {
        if ($this->m) {
            try {
                return @$this->m->getStats();
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    /**
     * generate key name
     * @param  string|array $key cache region join key
     * @return string      md5 key name
     */
    private function genKeyName($key) {
        if (is_array($key)) {
            $key = json_encode($key);
        }

        return strtolower($this->region . '.' . $key);
    }
}
