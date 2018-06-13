<?php

namespace dkm\libraries\base;

/**
 * Class BaseService
 * @package dkm\libraries
 * @property \DKM_Controller $CI
 */
abstract class BaseService {
    protected static $instance_map = NULL;
    protected $CI = NULL;
    
    // 不允许实例化，子类禁止覆盖
    final protected function __construct() {
    }

    final protected function __clone() {
    }
    
    protected function init() {
    }

    /**
     * 获取Service的实例
     *
     * @return self
     */
    public static function get_instance() {
        $class = get_called_class();
        if (!isset(self::$instance_map[$class])) {
            $instance = new $class();
            $instance->CI = &get_instance();
            $instance->init();
            self::$instance_map[$class] = $instance;
            return $instance;
        }
        return self::$instance_map[$class];
    }
}
