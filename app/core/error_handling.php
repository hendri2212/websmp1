<?php

function send_generic_error_response(int $status = 500, string $message = 'Terjadi gangguan pada sistem. Silakan coba lagi nanti.'): void
{
    while (ob_get_level() > 0) {
        if (!@ob_end_clean()) {
            break;
        }
    }
    if (!headers_sent()) {
        header_remove('X-Powered-By');
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
    }
    echo $message;
}

function configure_web_error_handling(): void
{
    static $configured = false;
    if ($configured) {
        return;
    }
    $configured = true;
    if (!headers_sent()) {
        header_remove('X-Powered-By');
    }
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');

    set_exception_handler(static function (Throwable $exception): void {
        // Exception messages and trace arguments can contain credentials or SQL values.
        error_log(sprintf('Unhandled %s in %s:%d', get_class($exception), $exception->getFile(), $exception->getLine()));
        if ($exception instanceof MediaRecordNotFoundException) {
            send_generic_error_response(404, 'Data tidak ditemukan.');
            return;
        }
        send_generic_error_response();
    });
    register_shutdown_function(static function (): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
            send_generic_error_response();
        }
    });
}
