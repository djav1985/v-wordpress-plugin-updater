<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: ErrorManager.php
 * Description: WordPress Update API
 */

namespace App\Core;

use ErrorException;
use Throwable;

class ErrorManager
{
    private static ?self $instance = null;

    private function __construct()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log(string $message, string $type = 'error'): void
    {
        $logFile = defined('LOG_FILE') ? LOG_FILE : (__DIR__ . '/../../php_app.log');
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type]: $message\n";
        error_log($logMessage, 3, $logFile);
    }

    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public function handleException(Throwable $exception): void
    {
        $message = 'Uncaught Exception: ' . $exception->getMessage() .
            ' in ' . $exception->getFile() .
            ' on line ' . $exception->getLine();
        $this->log($message, 'exception');
        http_response_code(500);
        echo 'Something went wrong. Please try again later.';
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            $this->log($message, 'fatal');
            http_response_code(500);
            echo 'A critical error occurred.';
        }
    }

    public static function handle(callable $callback): void
    {
        $manager = self::getInstance();
        try {
            $callback();
        } catch (Throwable $exception) {
            $manager->handleException($exception);
        }
    }
}
