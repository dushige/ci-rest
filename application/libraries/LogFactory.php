<?php

namespace dkm\libraries;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LogstashFormatter;

class LogFactory {

    // 本地文件
    const HANDLER_STREAM = 1;
    
    public static $HANDLER_MAP = [
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

    public static $FORMAT_MAP = [
        self::FORMAT_JSON => 'JsonFormatter',
        self::FORMAT_LINE => 'LineFormatter',
        self::FORMAT_HTML => 'HtmlFormatter',
        self::FORMAT_LOGSTASH => 'LogstashFormatter',
    ];

    const LEVEL_DEBUG = Logger::DEBUG;
    const LEVEL_INFO = Logger::INFO;
    const LEVEL_NOTICE = Logger::NOTICE;
    const LEVEL_WARNING = Logger::WARNING;
    const LEVEL_ERROR = Logger::ERROR;
    const LEVEL_CRITICAL = Logger::CRITICAL;
    const LEVEL_ALERT = Logger::ALERT;
    const LEVEL_EMERGENCY = Logger::EMERGENCY;

    public static $LEVEL_MAP = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_NOTICE => 'NOTICE',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL',
        self::LEVEL_ALERT => 'ALERT',
        self::LEVEL_EMERGENCY => 'EMERGENCY',
    ];

    private static $logger_map = NULL;

    private static $log_config = NULL;

    /**
     * @param $log_handler
     * @param $name
     * @param $log_path
     * @param $log_extension
     * @param $level
     * @return StreamHandler
     */
    private static function getHandler($log_handler, $name, $log_path, $log_extension, $level) {
        if ($log_handler == self::HANDLER_STREAM) {
            $log_file = $log_path . "$name-" . strtolower(self::$LEVEL_MAP[$level]) . $log_extension;
            return new StreamHandler($log_file, $level);
        }

        // 默认是StreamHandler
        $log_file = $log_path . "$name-" . strtolower(self::$LEVEL_MAP[$level]) . $log_extension;
        return new StreamHandler($log_file, $level);
    }

    /**
     * get formatter
     *
     * @param $log_format
     * @param $name
     * @return HtmlFormatter|JsonFormatter|LineFormatter|LogstashFormatter
     */
    private static function getFormatter($log_format, $name) {
        if ($log_format == self::FORMAT_JSON) {
            return new JsonFormatter();
        }
        if ($log_format == self::FORMAT_HTML) {
            return new HtmlFormatter();
        }
        if ($log_format == self::FORMAT_LINE) {
            return new LineFormatter();
        }
        if ($log_format == self::FORMAT_LOGSTASH) {
            return new LogstashFormatter($name);
        }
        return new JsonFormatter();
    }

    /**
     * get logger
     *
     * @param $name
     * @param array $config
     * @return Logger
     */
    public static function getLogger($name, $config = []) {
        if (isset(self::$logger_map[$name])) {
            return self::$logger_map[$name];
        }

        if (empty(self::$log_config)) {
            self::loadConfig();
        }

        $log_path = self::configItem('log_path');
        $log_handlers = self::configItem('log_handlers');
        $log_format = self::configItem('log_format');
        $log_level = self::configItem('log_level');
        $log_extension = self::configItem('log_extension');

        foreach ($config as $key => $value) {
            $$key = $value;
        }

        if (empty($log_path)) {
            $log_path = APPPATH . 'logs/';
            self::$log_config['log_path'] = $log_path;
        }

        if (empty($log_handlers)) {
            $log_handlers = [self::HANDLER_STREAM];
            self::$log_config['log_handlers'] = $log_handlers;
        }

        if (empty($log_format)) {
            $log_format = self::FORMAT_JSON;
            self::$log_config['log_format'] = $log_format;
        }

        if (empty($log_level)) {
            $log_level = self::LEVEL_INFO;
            self::$log_config['log_level'] = $log_level;
        }

        if (empty($log_extension)) {
            $log_extension = '.log';
            self::$log_config['log_extension'] = $log_extension;
        }

        $logger = new Logger($name);
        $formatter = self::getFormatter($log_format, $name);
        foreach ($log_handlers as $log_handler) {
            $handler = self::getHandler($log_handler, $name, $log_path, $log_extension, $log_level);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        }
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(new IntrospectionProcessor($log_level));
        self::$logger_map[$name] = $logger;
        return $logger;
    }

    /**
     * load config from app config or dev config
     */
    private static function loadConfig() {
        if (!empty(self::$log_config)) {
            return;
        }
        $config_file = APPPATH . 'config/log.php';
        if (file_exists(APPPATH . '../development.lock')) {
            $config_file = APPPATH . 'config/development/log.php';
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

    /**
     * @param $key
     * @return mixed
     */
    private static function configItem($key) {
        if (isset(self::$log_config[$key])) {
            return self::$log_config[$key];
        }
        return NULL;
    }
}
