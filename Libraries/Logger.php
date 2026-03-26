<?php

namespace Lukiman\AuthServer\Libraries;

class Logger {
    private static $logFile = __DIR__ . '/../log/logfile-';
    private static $suffixLogFile = '.log';

    public static function log($message, $level = 'info') {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $rootPath = defined('ROOT_PATH') ? realpath(ROOT_PATH) : realpath(__DIR__ . '/..');
        $rootPath = $rootPath !== false ? $rootPath : '';

        $caller = isset($backtrace[1]) ? str_replace($rootPath, '', $backtrace[1]['file']) . ':' . $backtrace[1]['line'] : '';
        
        $message = !empty($caller) ? "[$caller] $message" : $message;

        $logEntry = date('Y-m-d H:i:s') . " [$level] $message" . PHP_EOL;
        $logFilePath = self::$logFile . date('Ymd') . self::$suffixLogFile;
        file_put_contents($logFilePath, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public static function info($message) {
        self::log($message, 'info');
    }

    public static function warning($message) {
        self::log($message, 'warning');
    }

    public static function error($message) {
        self::log($message, 'error');
    }
}
