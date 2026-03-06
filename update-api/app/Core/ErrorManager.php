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

    /**
     * Register PHP error, exception, and shutdown handlers.
     */
    private function __construct()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Return the singleton ErrorManager instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute a callable inside the error-handling context.
     *
     * Any Throwable thrown by $callback is forwarded to handleException().
     *
     * @param callable $callback The code to execute.
     * @return void
     */
    public static function handle(callable $callback): void
    {
        $manager = self::getInstance();
        try {
            $callback();
        } catch (Throwable $exception) {
            $manager->handleException($exception);
        }
    }
    
    /**
     * Write a timestamped message to the application log file.
     *
     * @param string $message Message to log.
     * @param string $type    Severity label (e.g. 'error', 'info', 'exception', 'fatal').
     * @return void
     */
    public function log(string $message, string $type = 'error'): void
    {
        $logFile = defined('LOG_FILE') ? LOG_FILE : (__DIR__ . '/../../php_app.log');
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type]: $message\n";
        error_log($logMessage, 3, $logFile);
    }

    /**
     * Convert a PHP error into an ErrorException.
     *
     * Registered as the global error handler in __construct().
     *
     * @param int    $errno   Error level constant (E_WARNING, E_NOTICE, etc.).
     * @param string $errstr  Error message.
     * @param string $errfile File where the error occurred.
     * @param int    $errline Line number where the error occurred.
     * @return bool False when the error code is not included in error_reporting().
     * @throws \ErrorException Always, when the error code is active.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Handle an uncaught exception.
     *
     * Logs the exception details, then either writes to STDERR (CLI) or emits
     * a 500 response and exits (HTTP context).
     *
     * @param \Throwable $exception The uncaught exception or error.
     * @return void
     */
    public function handleException(Throwable $exception): void
    {
        $message = 'Uncaught Exception: ' . $exception->getMessage() .
            ' in ' . $exception->getFile() .
            ' on line ' . $exception->getLine();
        $this->log($message, 'exception');

        if (PHP_SAPI === 'cli') {
            if (defined('STDERR')) {
                fwrite(STDERR, $message . "\n");
            } else {
                echo $message . "\n";
            }
            exit(1);
        }

        http_response_code(500);
        echo 'Something went wrong. Please try again later.';
        exit(1);
    }

    /**
     * Handle fatal errors detected after script shutdown.
     *
     * Registered via register_shutdown_function(). Checks error_get_last() for
     * E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, and E_PARSE. Logs the error and
     * emits a 500 response when a fatal error is found.
     *
     * @return void
     */
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
}
