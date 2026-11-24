<?php

declare(strict_types=1);

use App\Auth;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH.'/app/helpers.php';

load_env(BASE_PATH.'/.env');

date_default_timezone_set('UTC');
error_reporting(E_ALL);

if (env_bool('APP_DEBUG', false)) {
    ini_set('display_errors', '1');
    ini_set('error_reporting', (string) E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH.'/storage/logs/php-error.log');
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BASE_PATH.'/app/'.$relative.'.php';

    if (is_file($file)) {
        require $file;
    }
});

set_exception_handler(function (Throwable $e): void {
    if ($e instanceof \App\ValidationException) {
        $_SESSION['_errors'] = $e->errors;
        $_SESSION['_old'] = $e->input;
        flash_set('error', 'Please fix the highlighted fields and try again.');
        back('/');
    }

    http_response_code(500);

    error_log('[unhandled] '.$e::class.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());

    if (env_bool('APP_DEBUG', false)) {
        echo '<!doctype html><meta charset="utf-8"><title>Error</title>';
        echo '<body style="font:14px/1.6 ui-monospace,monospace;padding:2rem;background:#fdfaf4;color:#1c1812">';
        echo '<h1 style="font-size:1.25rem">'.$e::class.'</h1>';
        echo '<p><strong>'.e($e->getMessage()).'</strong></p>';
        echo '<p>'.e($e->getFile()).':'.$e->getLine().'</p>';
        echo '<pre style="overflow:auto;background:#f7f0e4;padding:1rem;border-radius:12px">'.e($e->getTraceAsString()).'</pre>';
        echo '</body>';
        exit;
    }

    require BASE_PATH.'/views/errors/500.php';
    exit;
});

session_name((string) env('SESSION_NAME', 'event_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

// One-request-old input and flashed messages.
$GLOBALS['__old'] = $_SESSION['_old'] ?? [];
$GLOBALS['__errors'] = $_SESSION['_errors'] ?? [];
$GLOBALS['__flash'] = $_SESSION['_flash'] ?? [];
unset($_SESSION['_old'], $_SESSION['_errors'], $_SESSION['_flash']);

if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

// The user cached for this request (null when signed out).
Auth::boot();

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
