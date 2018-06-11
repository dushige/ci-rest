<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Formatter\JsonFormatter;

class LogFactory {

    private static $app_path = '';

    private static $logger_map = NULL;

    private static $log_config = NULL;

    public function __construct() {
        self::$app_path = realpath(dirname(__FILE__) . '/..') . '/';
    }

    private function getHandler() {
        // TODO
        return;
    }

    public static function getLogger($name, $config = []) {
        if (isset(self::$logger_map[$name])) {
            return self::$logger_map[$name];
        }

        self::loadConfig();

        $log_path = self::configItem('log_path');
        $log_format = self::configItem('log_format');

        if (empty($log_path)) {
            $log_path = self::$app_path . 'logs';
            self::$log_config['log_path'] = $log_path;
        }

        if (empty($log_format)) {
            $log_format = 'json';
            self::$log_config['log_format'] = $log_format;
        }


    }

    private static function loadConfig() {
        if (!empty(self::$log_config)) {
            return;
        }
        $config_file = self::$app_path . 'config/log.php';
        if (file_exists(self::$app_path . '../development.lock')) {
            $config_file = self::$app_path . 'config/development/log.php';
        }
        if (file_exists($config_file)) {
            require($config_file);
        } else {
            return;
        }
        if (isset($config)) {
            self::$log_config = $config;
        }
    }

    private static function configItem($key) {
        if (isset(self::$log_config[$key])) {
            return self::$log_config[$key];
        }
        return NULL;
    }
}