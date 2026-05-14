<?php

/**
 * Last-resort handler for uncaught exceptions on /api/index.php.
 * Logs full details; responds with the same JSON shape as apiError() (no stack traces).
 */
final class ApiExceptionHandler {

    public static function register(): void {
        if (PHP_SAPI === 'cli') {
            return;
        }

        set_exception_handler(function (Throwable $e): void {
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            $trace = $e->getTraceAsString();
            error_log('[ERP API] Uncaught ' . get_class($e) . ": {$msg} @ {$file}:{$line}\n{$trace}");

            if (headers_sent()) {
                error_log('[ERP API] Headers already sent; cannot emit JSON error body cleanly.');
                exit(1);
            }

            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');

            $publicMsg = 'An unexpected error occurred. Please try again later.';
            echo json_encode(['success' => false, 'error' => $publicMsg]);
            exit;
        });
    }
}
