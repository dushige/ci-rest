<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LogstashFormatter;

class LogFactory {

    // 本地文件
    const HANDLER_STREAM = 1;
    
    public static $handler_map = [
        self::HANDLER_STREAM => 'StreamHandler'
    ];

    // json
    const FORMAT_JSON = 1;
    // 单行
    const FORMAT_LINE = 2;
    // html
    const FORMAT_HTML = 3;
    // logstash
    const FORMAT_LOGSTASH = 4;

    public static $format_map = [
        self::FORMAT_JSON => 'JsonFormatter',
        self::FORMAT_LINE => 'LineFormatter',
        self::FORMAT_HTML => 'HtmlFormatter',
        self::FORMAT_LOGSTASH => 'LogstashFormatter',
    ];

    private static $app_path = '';

    private static $logger_map = NULL;

    private static $log_config = NULL;

    public function __construct() {
        self::$app_path = realpath(dirname(__FILE__) . '/..') . '/';
    }

    private static function getHandler($handler_name, $name, $level) {
        // TODO
    }

    public static function getLogger($name, $config = []) {
        if (isset(self::$logger_map[$name])) {
            return self::$logger_map[$name];
        }

        self::loadConfig();

        $log_path = self::configItem('log_path');
        $log_handler = self::configItem('log_handler');
        $log_format = self::configItem('log_format');
        $log_level = self:configItem('log_level');

        if (empty($log_path)) {
            $log_path = self::$app_path . 'logs';
            self::$log_config['log_path'] = $log_path;
        }

        if (empty($log_handler)) {
            $log_handler = 'StreamHandler';
            self::$log_config['log_handler'] = $log_handler;
        }

        if (empty($log_format)) {
            $log_format = 'json';
            self::$log_config['log_format'] = $log_format;
        }

        $handler = self::getHandler($log_handler, $name, $log_level);
        // TODO
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
