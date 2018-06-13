<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2018, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2018, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Redis Caching Class
 *
 * @package	   CodeIgniter
 * @subpackage Libraries
 * @category   Core
 * @author	   Anton Lindqvist <anton@qvister.se>
 * @link
 */
class Cache_redis
{
	/**
	 * Redis connection
	 *
	 * @var	Redis
	 */
	protected $_redis;

    /**
     * CI
     *
     * @var CI_Controller
     */
	protected $_ci;

	// ------------------------------------------------------------------------

	/**
	 * Class constructor
	 *
	 * Setup Redis
	 *
	 * Loads Redis config file if present. Will halt execution
	 * if a Redis connection can't be established.
	 *
     * @param $group
	 * @return	void
     * @throws Exception
	 * @see		Redis::connect()
	 */
	public function __construct(array $group) {
        if (!isset($group['group_name'])) {
            throw new Exception('无效的参数');
        }

		$this->_ci =& get_instance();

        $group_name = $group['group_name'];
		if ($this->_ci->config->load('redis', TRUE, TRUE)) {
			$config = $this->_ci->config->item('redis')[$group_name];
			if (empty($config) || !is_array($config)) {
                throw new Exception("无效的配置:redis['$group_name']'");
            }
		}

		if (!$this->connect($config)) {
		    if (!$this->connect($config)) {
		        $this->connect($config, TRUE);
            }
        }
	}

    // ------------------------------------------------------------------------

    /**
     * Class destructor
     *
     * Closes the connection to Redis if present.
     *
     * @return	void
     */
    public function __destruct() {
        if ($this->_redis) {
            $this->_redis->close();
            $this->_redis = NULL;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * connect to redis cluster or redis
     * @param $config
     * @param $log_error
     * @return boolean
     */
    private function connect($config, $log_error = FALSE){
        if (empty($config['hosts']) || !is_array($config['hosts'])) {
            return FALSE;
        }

        $hosts = $config['hosts'];
        $timeout = isset($config['timeout']) ? $config['timeout'] : 1;
        $read_timeout = isset($config['read_timeout']) ? $config['read_timeout'] : 1;

        shuffle($hosts);

        try {
            if (count($hosts) == 1) {
                $redis = new Redis();
                list($host, $port) = explode(':', $hosts[0]);
                $redis->connect($host, $port, $config['timeout']);
                $redis->auth($config['password']);
            } else {
                $redis = new RedisCluster(NULL, $hosts, $timeout, $read_timeout);
                $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
                $redis->setOption(RedisCluster::OPT_SLAVE_FAILOVER, RedisCluster::REDIS_FAILOVER_DISTRIBUTE);
            }
        }
        catch (Exception $e) {
            if ($log_error) {
                $logger = \dkm\libraries\LogFactory::getLogger('dkm');
                $logger->error('RedisConnectionError', ['error' => $e->getMessage()]);
            }
            return FALSE;
        }

        if (empty($redis)) {
            return FALSE;
        }

        $this->_redis = $redis;

        return TRUE;
    }

    // ------------------------------------------------------------------------

    /**
     * if cache exists
     *
     * @param	string	$key	Cache ID
     * @return	mixed
     */
    public function exists($key) {
        try {
            return $this->_redis->exists($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

	// ------------------------------------------------------------------------

	/**
	 * Get cache
	 *
	 * @param	string	$key	Cache ID
	 * @return	mixed
	 */
	public function get($key) {
        try {
            return $this->_redis->get($key);
        } catch (Exception $e) {
            return FALSE;
        }
	}

	// ------------------------------------------------------------------------

	/**
	 * string key => mixed value
	 *
	 * @param	string	$key	Cache Key
	 * @param	mixed	$value	Data to save
	 * @return	bool	TRUE on success, FALSE on failure
	 */
	public function set($key, $value) {
        try {
            return $this->_redis->set($key, $value);
        } catch (Exception $e) {
            return FALSE;
        }
	}

    // ------------------------------------------------------------------------

    /**
     * set if not exists
     * @param $key
     * @param $value
     * @return bool
     */
    public function setNx($key, $value) {
        try {
            return $this->_redis->setNx($key, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Set key to hold the string value and set key to timeout after a given number of seconds
     * This command is equivalent to executing the following commands:
     *     SET mykey value
     *     EXPIRE mykey seconds
     * @param $key
     * @param $value
     * @param $seconds
     * @return Bool TRUE in case of success, FALSE in case of failure.
     */
    public function setEx($key, $value, $seconds) {
        try {
            return $this->_redis->setEx($key, $seconds, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Get the values of all the specified keys. If one or more keys dont exist, the array will contain FALSE at the position of the key.
     * @param $keys
     * @return array|bool
     */
    public function mGet($keys) {
        try {
            return $this->_redis->mGet($keys);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Sets multiple key-value pairs in one atomic command.
     * @param $pairs array(key => value, ...)
     * @return Bool TRUE in case of success, FALSE in case of failure.
     */
    public function mSet(array $pairs) {
        try {
            return $this->_redis->mSet($pairs);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Remove specified keys
     * @param $key
     * @return boolean
     */
    public function del($key) {
        try {
            return $this->_redis->del($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

	// ------------------------------------------------------------------------

    /**
     * Increment the number stored at key by one.
     * @param $key
     * @return INT the new value
     */
    public function incr($key) {
        try {
            return $this->_redis->incr($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Increment the number stored at key by one. If the second argument is filled,
     * it will be used as the integer value of the increment.
     * @param $key
     * @param $val
     * @return INT the new value
     */
    public function incrBy($key, $val) {
        try {
            return $this->_redis->incrBy($key, $val);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * @param $key
     * @param $field
     * @param $increment
     * @return bool|int
     */
    public function hIncrBy($key, $field, $increment) {
        try {
            return $this->_redis->hIncrBy($key, $field, $increment);
        } catch (Exception $e) {
            return FALSE;
        }
    }

	// ------------------------------------------------------------------------

    /**
     * Decrement the number stored at key by one.
     * @param $key
     * @return INT the new value
     */
    public function decr($key) {
        try {
            return $this->_redis->decr($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * @param $key
     * @param $val
     * @return bool|int
     */
    public function decrBy($key, $val) {
        try {
            return $this->_redis->decrBy($key, $val);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Adds a value to the hash stored at key only if this field isn't already in the hash.
     * @param $key
     * @param $field
     * @param $value
     * @return TRUE if the field was set, FALSE if it was already present.
     */
    public function hSetNx($key, $field, $value) {
        try {
            return $this->_redis->hSetNx($key, $field, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Adds a value to the hash stored at key.
     * @param $key
     * @param $hash_key
     * @param $value
     * @return mixed
     */
    public function hSet($key, $hash_key, $value) {
        try {
            return $this->_redis->hSet($key, $hash_key, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Gets a value from the hash stored at key. If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     * @param $key
     * @param $hash_key
     * @return STRING The value, if the command executed successfully BOOL FALSE in case of failure
     */
    public function hGet($key, $hash_key) {
        try {
            return $this->_redis->hGet($key, $hash_key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Returns the whole hash, as an array of strings indexed by strings.
     * @param $key
     * @return mixed
     */
    public function hGetAll($key) {
        try {
            return $this->_redis->hGetAll($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Returns the whole hash, as an array of strings indexed by strings.
     * @param $key
     * @param $iterator
     * @return mixed
     */
    public function hScan($key, &$iterator) {
        try {
            return $this->_redis->hScan($key, $iterator);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Verify if the specified member exists in a key
     * @param $key
     * @param $hash_key
     * @return BOOL: If the member exists in the hash table, return TRUE, otherwise return FALSE.
     */
    public function hExists($key, $hash_key) {
        try {
            return $this->_redis->hExists($key, $hash_key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Fills in a whole hash. Non-string values are converted to string, using the standard (string) cast.
     * NULL values are stored as empty strings.
     * @param $key
     * @param $map
     * @return mixed
     */
    public function hMset($key, $map) {
        try {
            return $this->_redis->hMset($key, $map);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Retrieve the values associated to the specified fields in the hash.
     * @param $key
     * @param $hash_keys
     * @return mixed
     */
    public function hMget($key, $hash_keys) {
        try {
            return $this->_redis->hMget($key, $hash_keys);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Removes a value from the hash stored at key. If the hash table doesn't exist,
     * or the key doesn't exist, FALSE is returned.
     * @param $key
     * @param $field
     * @return TRUE in case of success, FALSE in case of failure
     */
    public function hDel($key, $field) {
        try {
            return $this->_redis->hDel($key, $field);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Returns the length of a hash, in number of items
     * @param $key
     * @return bool|int
     */
    public function hLen($key) {
        try {
            return $this->_redis->hLen($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Returns the keys in a hash, as an array of strings.
     * @param $key
     * @return mixed An array of elements, the keys of the hash. This works like PHP's array_keys().
     */
    public function hKeys($key) {
        try {
            return $this->_redis->hKeys($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Adds the string value to the head (left) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
     * @param $key
     * @param $value
     * @return mixed The new length of the list in case of success, FALSE in case of Failure.
     */
    public function lPush($key, $value) {
        try {
            if (is_array($value)) {
                array_unshift($value, $key);
                return call_user_func_array(array($this->_redis, 'lPush'), $value);
            } else {
                return $this->_redis->lPush($key, $value);
            }
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Returns and removes the last element of the list.
     * @param $key
     * @return STRING if command executed successfully
     * BOOL FALSE in case of failure (empty list)
     */
    public function rPop($key) {
        try {
            return $this->_redis->rPop($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @return array|bool
     */
    public function smembers($key) {
        try {
            return $this->_redis->smembers($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param int $count
     * @return array|bool|string
     */
    public function srandmember($key, $count = 1) {
        try {
            return $this->_redis->sRandMember($key, $count);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function sismember($key, $value) {
        try {
            return $this->_redis->sIsMember($key, $value);
        } catch (Exception $e) {
            return FALSE;
        }

    }

    /**
     * @param $key
     * @param $content
     * @return bool|int|mixed
     */
    public function sadd($key, $content) {
        try {
            if (is_array($content)) {
                array_unshift($content, $key);
                return call_user_func_array(array($this->_redis, 'sadd'), $content);
            } else {
                return $this->_redis->sadd($key, $content);
            }
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @return bool|int
     */
    public function scard($key) {
        try {
            return $this->_redis->scard($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $content
     * @return bool|int
     */
    public function srem($key, $content) {
        try {
            return $this->_redis->srem($key, $content);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Removes the first count occurences of the value element from the list.  * If count is zero, all the matching
     * elements are removed. If count is negative, elements are removed from tail to head.
     * @param $key
     * @param $value
     * @param $count
     * @return mixed LONG the number of elements to remove
     *         BOOL FALSE if the value identified by key is not a list.
     */
    public function lRem($key, $value, $count = 1) {
        try {
            return $this->_redis->lRem($key, $value, $count);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * If the list didn't exist or is empty, the command returns 0. If the data type identified by Key is not a list,
     * the command return FALSE.
     * @param $key
     * @return mixed LONG The size of the list identified by Key exists.
     *         BOOL FALSE if the data type identified by Key is not list
     */
    public function lSize($key) {
        try {
            return $this->_redis->lLen($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $index
     * @return bool|String
     */
    public function lIndex($key, $index) {
        try {
            return $this->_redis->lIndex($key, $index);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Returns the specified elements of the list stored at the specified key in the range [start, end].
     * start and stop are interpretated as indices: 0 the first element, 1 the second ... -1 the last element,
     * -2 the penultimate ...
     * @param $key
     * @param $start
     * @param $end
     * @return mixed Array containing the values in specified range.
     */
    public function lRange($key, $start, $end) {
        try {
            return $this->_redis->lRange($key, $start, $end);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $index
     * @param $value
     * @return bool
     */
    public function lSet($key, $index, $value) {
        try {
            return $this->_redis->lSet($key, $index, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Set a timeout on key.
     * After the timeout has expired, the key will automatically be deleted. A key with an associated timeout is often
     * said to be volatile in Redis terminology.
     * It is possible to call EXPIRE using as argument a key that already has an existing expire set.
     * In this case the time to live of a key is updated to the new value. There are many useful applications for this,
     * an example is documented in the Navigation session pattern section below.
     * @param $key
     * @param $timeout
     * @return mixed
     */
    public function setTimeout($key, $timeout) {
        try {
            return $this->_redis->expire($key, $timeout);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Remove the expiration timer from a key
     * @param $key
     * @return TRUE if a timeout was removed, FALSE if the key didn’t exist or didn’t have an expiration timer.
     */
    public function persist($key) {
        try {
            return $this->_redis->persist($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @return array|bool
     */
    public function keys($key) {
        try {
            return $this->_redis->keys($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @return bool|int
     */
    public function ttl($key) {
        try {
            return $this->_redis->ttl($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $member
     * @return bool|int
     */
    public function zRem($key, $member) {
        try {
            return $this->_redis->zrem($key, $member);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @return bool|int
     */
    public function zCount($key, $start, $end) {
        try {
            return $this->_redis->zcount($key, $start, $end);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $score
     * @param $member
     * @return bool|int
     */
    public function zAdd($key, $score, $member) {
        try {
            return $this->_redis->zadd($key, $score, $member);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @param bool $withscores
     * @return array|bool
     */
    public function zRevRange($key, $start, $end, $withscores = FALSE) {
        try {
            return $this->_redis->zrevrange($key, $start, $end, $withscores);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @param bool $withscores
     * @return array|bool
     */
    public function zRange($key, $start, $end, $withscores = FALSE) {
        try {
            return $this->_redis->zRange($key, $start, $end, $withscores);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @param  array  $option array('withscores' => TRUE, 'limit' => array($offset, $count))
     * @return mixed
     */
    public function zRangeByScore($key, $start, $end, $option = []) {
        try {
            if (is_array($option) && $option) {
                return $this->_redis->zrangebyscore($key, $start, $end, $option);
            } else {
                return $this->_redis->zrangebyscore($key, $start, $end);
            }
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $value
     * @return bool|float
     */
    public function zScore($key, $value) {
        try {
            return $this->_redis->zScore($key, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $value
     * @return bool|int
     */
    public function zRevRank($key, $value) {
        try {
            return $this->_redis->zRevRank($key, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @return bool|int
     */
    public function zCard($key) {
        try {
            return $this->_redis->zCard($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $score
     * @param $value
     * @return bool|float
     */
    public function zIncrBy($key, $score, $value) {
        try {
            return $this->_redis->zIncrBy($key, $score, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $start
     * @param $stop
     * @return array|bool
     */
    public function ltrim($key, $start, $stop) {
        try {
            return $this->_redis->lTrim($key, $start, $stop);
        } catch (Exception $e) {
            return FALSE;
        }

    }

    /**
     * @return bool|Redis
     */
    public function pipeline() {
        try {
            return $this->_redis->multi(Redis::PIPELINE);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @return bool|string
     */
    public function sPop($key) {
        try {
            return $this->_redis->sPop($key);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $key
     * @param $value
     * @return bool|string
     */
    public function getSet($key, $value) {
        try {
            return $this->_redis->getset($key, $value);
        } catch (Exception $e) {
            return FALSE;
        }
    }

	/**
	 * Get cache driver info
	 *
	 * @param	string	$type	Not supported in Redis.
	 *				Only included in order to offer a
	 *				consistent cache API.
	 * @return  mixed
	 * @see		Redis::info()
	 */
	public function cache_info($type = NULL)
	{
		return $this->_redis->info();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get cache metadata
	 *
	 * @param	string	$key	Cache key
	 * @return	mixed
	 */
	public function get_metadata($key)
	{
		$value = $this->get($key);

		if ($value !== FALSE)
		{
			return array(
				'expire' => time() + $this->_redis->ttl($key),
				'data' => $value
			);
		}

		return FALSE;
	}
}
