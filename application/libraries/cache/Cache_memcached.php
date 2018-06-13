<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Cache_memcached {

    /**
     * @var string
     */
    protected $region;

    /**
     * @var array
     */
    protected $local_cache = [];

    /**
     * @var CI_Controller
     */
    protected $ci;

    /**
     * @var array
     */
    protected $errors = [];

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
     * @param $region
     * @throws Exception
     */
    public function __construct($region) {
        $this->region = $region;
        $this->ci =& get_instance();
        if ($this->ci->load->config('memcached', TRUE, TRUE)) {
            $config = $this->ci->config->item('memcached')[$region];
            if (empty($config) || !is_array($config)) {
                throw new Exception("无效的配置:memcached['$region']'");
            }
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

    public function get($key) {
        $key = $this->genKeyName($key);
        if ($this->m) {
            if (isset($this->local_cache[$key])) {
                return $this->local_cache[$key];
            }

            try {
                return @$this->m->get($key);
            } catch (Exception $e) {
                return FALSE;
            }
        }
        return FALSE;
    }

    public function add($key, $value, $expiration = NULL) {
        $key = $this->genKeyName($key);

        if ($this->m) {
            if (isset($this->local_cache[$key])) {
                return $this->local_cache[$key];
            }

            try {
                $result = @$this->m->add($key, $value, $expiration);
                if ($result) {
                    $this->local_cache[$key] = $value;
                }
                return $result;
            } catch (Exception $e) {
                return FALSE;
            }
        }

        return FALSE;
    }

    public function put($region, $key, $value, $expiration = NULL, $ignore_local_cache = FALSE) {
        $this->connect($region);

        if (!isset($this->config['region']) || !key_exists($region, $this->config['region'])) {
            return FALSE;
        }
        $region_conf = $this->config['region'][$region];
        list($_region, $_expiration) = array_values($region_conf);
        if ($expiration !== NULL) {
            $_expiration = $expiration;
        }
        $genKeyName = $this->genKeyName($_region . $key);
        if ($_expiration === NULL || !is_numeric($_expiration)) {
            $_expiration = $this->config['config']['expiration'];
        }
        if ($ignore_local_cache === FALSE) {
            $this->local_cache[$genKeyName] = $value;
        }

        return @$this->m->set($genKeyName, $value, $_expiration);
    }

    public function increment($region, $key) {
        $this->connect($region);

        if (!isset($this->config['region']) || !key_exists($region, $this->config['region'])) {
            return FALSE;
        }
        $region_conf = $this->config['region'][$region];
        list($_region,) = array_values($region_conf);

        return @$this->m->increment($this->genKeyName($_region . $key));
    }

    public function decrement($region, $key) {
        $this->connect($region);

        if (!isset($this->config['region']) || !key_exists($region, $this->config['region'])) {
            return FALSE;
        }
        $region_conf = $this->config['region'][$region];
        list($_region, ) = array_values($region_conf);

        return @$this->m->decrement($this->genKeyName($_region . $key));
    }

    /*
    +-------------------------------------+
        Name: delete
        Purpose: deletes a single or multiple data elements from the memached servers
        @param return : none
    +-------------------------------------+
    */
    public function delete($region, $key, $expiration = NULL) {
        $this->connect($region);

        if (is_null($key)) {
            $this->errors[] = 'The key value cannot be NULL';
            return FALSE;
        }

        if (is_null($expiration)) {
            $expiration = $this->config['config']['delete_expiration'];
        }

        if (is_array($key)) {
            foreach ($key as $multi) {
                $this->delete($region, $multi, $expiration);
            }
        } else {
            $region_conf = $this->config['region'][$region];
            unset($this->local_cache[$this->genKeyName($region_conf['region'] . $key)]);
            return @$this->m->delete($this->genKeyName($region_conf['region'] . $key), $expiration);
        }
    }

    /**
     * 此方法用于一次获取多个key，用之前请细看每行代码，key的数量请自己保证，建议不超过30
     * @param $region
     * @param null $keys array('key1', 'key2')
     * @return array $ret_arr = [
     *                  'in_cache' => [],
     *                  'no_cache' => $keys
     *               ];
     */
    public function getMulti($region, $keys = NULL) {
        $ret_arr = [
            'in_cache' => [],
            'no_cache' => $keys
        ];

        $this->connect($region);

        if (empty($region) || !key_exists($region, $this->config['region'])) {
            $this->errors[] = 'Undefined region';
            return $ret_arr;
        }

        if (!$this->m || !method_exists($this->m, 'getMulti') || empty($keys) || !is_array($keys)) {
            return $ret_arr;
        }
        $region_conf = $this->config['region'][$region];
        $mc_keys = [];
        foreach ($keys as $_key) {
            if (is_null($_key)) {
                $this->errors[] = 'The key value cannot be NULL';
                return $ret_arr;
            }

            $mc_keys[$_key] = $this->genKeyName($region_conf['region'] . $_key);
        }
        $mc_values = [];
        if (!empty($mc_keys)) {
            $mc_values = $this->m->getMulti($mc_keys);
        }

        if (empty($mc_values)) {
            return $ret_arr;
        } else {
            $ret_arr['no_cache'] = array_flip($ret_arr['no_cache']);
            foreach ($mc_keys as $_key => $_value) {
                if (isset($mc_values[$_value])) {
                    $ret_arr['in_cache'][$_key] = $mc_values[$_value];
                    unset($ret_arr['no_cache'][$_key]);
                }
            }
            $ret_arr['no_cache'] = array_keys($ret_arr['no_cache']);

            return $ret_arr;
        }
    }

    /**
     * 此方法用于批量向memcache中set key,用之前请细看每行代码，key的数量请自己保证，建议不超过30
     * @param $region
     * @param $keys array('key1' => 'value1', 'key2' => 'value2');
     * @param null $expiration 超时，单位秒
     * @param bool $ignore_local_cache
     * @return bool
     */
    public function putMulti($region, $keys, $expiration = NULL, $ignore_local_cache = FALSE) {
        $this->connect($region);

        if (!isset($this->config['region']) || !key_exists($region, $this->config['region'])) {
            return FALSE;
        }

        $region_conf = $this->config['region'][$region];
        list($_region, $_expiration) = array_values($region_conf);
        if ($expiration !== NULL) {
            $_expiration = $expiration;
        }
        if ($_expiration === NULL || !is_numeric($_expiration)) {
            $_expiration = $this->config['config']['expiration'];
        }

        $set_keys = [];
        foreach ($keys as $_key => $_val) {
            $genKeyName = $this->genKeyName($_region . $_key);
            $set_keys[$genKeyName] = $_val;
            if ($ignore_local_cache === FALSE) {
                $this->local_cache[$genKeyName] = $_val;
            }
        }

        try {
            $set_result = $this->m->setMulti($set_keys, $_expiration);
        } catch (Exception $e) {
            $set_result = FALSE;
        }

        return $set_result;
    }

    public function get_result_code($region = NULL) {
        $this->connect($region);

        return $this->m->getResultCode();
    }

    /*
    +-------------------------------------+
        Name: flush
        Purpose: flushes all items from cache
        @param return : none
    +-------------------------------------+
    */
    public function flush($region = NULL) {
        $this->connect($region);

        return @$this->m->flush();
    }

    /*
    +-------------------------------------+
        Name: getversion
        Purpose: Get Server Vesion Number
        @param Returns a string of server version number or FALSE on failure.
    +-------------------------------------+
    */
    public function getversion($region = NULL) {
        $this->connect($region);

        return @$this->m->getVersion();
    }

    /*
    +-------------------------------------+
        Name: getstats
        Purpose: Get Server Stats
        Possible: "reset, malloc, maps, cachedump, slabs, items, sizes"
        @param returns an associative array with server's statistics. Array keys correspond to stats parameters and values to parameter's values.
    +-------------------------------------+
    */
    public function getstats($region = NULL) {
        $this->connect($region);

        return @$this->m->getStats();
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

        return strtolower($this->config['config']['prefix'] . $key);
    }
}
/* End of file memcached_library.php */
/* Location: ./application/libraries/memcached_library.php */
