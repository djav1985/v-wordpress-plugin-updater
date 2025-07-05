<?php

/**
 * Class ErrorHandler
 * Handles error and exception logging and display.
 */
class ErrorHandler // @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * ErrorHandler constructor.
     * Registers error, exception, and shutdown handlers.
     */
    public function __construct()
    {
        self::register();
    }

    /**
     * Registers error, exception, and shutdown handlers.
     */
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handles PHP errors by converting them to exceptions.
     *
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int $errline The line number the error was raised at.
     * @return bool
     * @throws ErrorException
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Handles uncaught exceptions.
     *
     * @param Throwable $exception The uncaught exception.
     */
    public static function handleException(Throwable $exception): void
    {
        $message = "Uncaught Exception: " . $exception->getMessage() .
            " in " . $exception->getFile() .
            " on line " . $exception->getLine();
        self::logMessage($message, 'exception');
        http_response_code(500);
        echo "Something went wrong. Please try again later.";
    }

    /**
     * Handles fatal errors on shutdown.
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            self::logMessage($message, 'fatal');
            http_response_code(500);
            echo "A critical error occurred.";
        }
    }

    /**
     * Logs error messages to a log file.
     *
     * @param string $message The error message to log.
     * @param string $type The type of error (default is 'error').
     */
    public static function logMessage(string $message, string $type = 'error'): void
    {
        $logFile = __DIR__ . '/../php_app.log';
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[$timestamp] [$type]: $message\n";
        error_log($logMessage, 3, $logFile);
    }
}
