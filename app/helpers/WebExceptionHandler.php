<?php

/**
 * Registers a last-resort handler for uncaught exceptions on the main web entry (index.php).
 * Logs full details server-side; shows a generic page to the user (no stack traces).
 */
final class WebExceptionHandler {

    public static function register(): void {
        if (PHP_SAPI === 'cli') {
            return;
        }

        set_exception_handler(function (Throwable $e): void {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $trace = $e->getTraceAsString();
            error_log('[ERP] Uncaught ' . get_class($e) . ": {$msg} @ {$file}:{$line}\n{$trace}");

            if (headers_sent()) {
                return;
            }

            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');

            $view = __DIR__ . '/../views/errors/500.php';
            if (is_readable($view)) {
                include $view;
            } else {
                echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head>'
                    . '<body><p>An unexpected error occurred. Please try again later.</p></body></html>';
            }
            exit;
        });
    }
}
